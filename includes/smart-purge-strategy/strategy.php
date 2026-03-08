<?php
/**
 * Smart Purge Strategy (Pillar 6).
 *
 * @package PressableCacheManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Feature gate for smart purge strategy.
 *
 * @return bool
 */
function pcm_smart_purge_is_enabled() {
    $enabled = false;

    return (bool) apply_filters( 'pcm_enable_smart_purge_strategy', $enabled );
}

/**
 * Active mode gate.
 *
 * @return bool
 */
function pcm_smart_purge_is_active_mode() {
    $active = (bool) get_option( 'pcm_smart_purge_active_mode', false );

    return (bool) apply_filters( 'pcm_enable_smart_purge_active_mode', $active );
}

/**
 * Cooldown window in seconds for dedupe.
 *
 * @return int
 */
function pcm_smart_purge_cooldown_window() {
    $seconds = (int) get_option( 'pcm_smart_purge_cooldown_seconds', 120 );

    return max( 15, min( 3600, $seconds ) );
}

/**
 * Deferred execution window in seconds.
 *
 * @return int
 */
function pcm_smart_purge_defer_window() {
    $seconds = (int) get_option( 'pcm_smart_purge_defer_seconds', 60 );

    return max( 0, min( 3600, $seconds ) );
}

/**
 * Event + queue storage abstraction.
 */
class PCM_Smart_Purge_Storage {
    /** @var string */
    protected $events_key = 'pcm_smart_purge_events_v1';

    /** @var string */
    protected $jobs_key = 'pcm_smart_purge_jobs_v1';

    /** @var string */
    protected $outcomes_key = 'pcm_smart_purge_outcomes_v1';

    /** @var int */
    protected $max_rows = 500;

    public function add_event( $event ) {
        $rows   = $this->get_events();
        $rows[] = $event;
        update_option( $this->events_key, array_slice( $rows, -1 * $this->max_rows ), false );
    }

    public function get_events() {
        $rows = get_option( $this->events_key, array() );

        return is_array( $rows ) ? $rows : array();
    }

    public function save_jobs( $jobs ) {
        update_option( $this->jobs_key, array_values( (array) $jobs ), false );
    }

    public function get_jobs() {
        $rows = get_option( $this->jobs_key, array() );

        return is_array( $rows ) ? $rows : array();
    }

    public function add_outcome( $outcome ) {
        $rows   = get_option( $this->outcomes_key, array() );
        $rows[] = $outcome;
        update_option( $this->outcomes_key, array_slice( $rows, -1 * $this->max_rows ), false );
    }

    public function get_outcomes() {
        $rows = get_option( $this->outcomes_key, array() );

        return is_array( $rows ) ? $rows : array();
    }
}

/**
 * Event normalizer for existing invalidation triggers.
 */
class PCM_Smart_Purge_Event_Normalizer {
    /** @var PCM_Smart_Purge_Storage */
    protected $storage;

    public function __construct( $storage = null ) {
        $this->storage = $storage ? $storage : new PCM_Smart_Purge_Storage();
    }

    public function record_event( $source_action, $object_type, $object_id, $context = array() ) {
        $event = array(
            'event_id'      => 'evt_' . wp_generate_uuid4(),
            'source_action' => sanitize_key( $source_action ),
            'object_type'   => sanitize_key( $object_type ),
            'object_id'     => absint( $object_id ),
            'actor'         => get_current_user_id(),
            'timestamp'     => current_time( 'mysql', true ),
            'context'       => is_array( $context ) ? $context : array(),
        );

        $this->storage->add_event( $event );

        return $event;
    }
}

/**
 * Scope recommendation engine.
 */
class PCM_Smart_Purge_Recommendation_Engine {
    public function recommend( $event ) {
        $object_type = isset( $event['object_type'] ) ? $event['object_type'] : 'unknown';
        $object_id   = isset( $event['object_id'] ) ? absint( $event['object_id'] ) : 0;

        $recommendation = array(
            'scope'            => 'global',
            'targets'          => array( home_url( '/' ) ),
            'reason'           => 'Fallback to global scope for unknown event type.',
            'estimated_impact' => 'high',
        );

        if ( 'post' === $object_type && $object_id > 0 ) {
            $url = get_permalink( $object_id );

            $recommendation = array(
                'scope'            => 'related_urls',
                'targets'          => array_filter( array( $url, home_url( '/' ) ) ),
                'reason'           => 'Post update detected. Purge post URL and homepage before escalating to global.',
                'estimated_impact' => 'medium',
            );
        }

        if ( 'comment' === $object_type && $object_id > 0 ) {
            $comment = get_comment( $object_id );
            $post_id = $comment ? (int) $comment->comment_post_ID : 0;
            $target  = $post_id ? get_permalink( $post_id ) : home_url( '/' );

            $recommendation = array(
                'scope'            => 'single_url',
                'targets'          => array_filter( array( $target ) ),
                'reason'           => 'Comment mutation detected. Purge the affected post URL only.',
                'estimated_impact' => 'low',
            );
        }

        return $recommendation;
    }
}

/**
 * Queue + dedupe manager.
 */
class PCM_Smart_Purge_Job_Queue {
    /** @var PCM_Smart_Purge_Storage */
    protected $storage;

    public function __construct( $storage = null ) {
        $this->storage = $storage ? $storage : new PCM_Smart_Purge_Storage();
    }

    public function enqueue( $event, $recommendation ) {
        $jobs      = $this->storage->get_jobs();
        $targets   = isset( $recommendation['targets'] ) ? (array) $recommendation['targets'] : array();
        $normalized_targets = array_values( array_unique( array_map( 'esc_url_raw', $targets ) ) );
        sort( $normalized_targets );

        $dedupe_key = hash( 'sha256', wp_json_encode( array(
            'scope'   => isset( $recommendation['scope'] ) ? $recommendation['scope'] : 'global',
            'targets' => $normalized_targets,
        ) ) );

        $now      = time();
        $cooldown = pcm_smart_purge_cooldown_window();

        foreach ( $jobs as $index => $job ) {
            $scheduled = isset( $job['scheduled_ts'] ) ? absint( $job['scheduled_ts'] ) : 0;
            $status    = isset( $job['status'] ) ? $job['status'] : 'queued';

            if ( 'queued' !== $status ) {
                continue;
            }

            if ( isset( $job['dedupe_key'] ) && hash_equals( $job['dedupe_key'], $dedupe_key ) && ( $now - $scheduled ) <= $cooldown ) {
                $jobs[ $index ]['batched_events'][] = isset( $event['event_id'] ) ? $event['event_id'] : '';
                $this->storage->save_jobs( $jobs );

                return $jobs[ $index ];
            }
        }

        $scheduled_ts = $now + pcm_smart_purge_defer_window();

        $job = array(
            'job_id'           => 'job_' . wp_generate_uuid4(),
            'scope'            => isset( $recommendation['scope'] ) ? sanitize_key( $recommendation['scope'] ) : 'global',
            'targets_json'     => $normalized_targets,
            'reason'           => isset( $recommendation['reason'] ) ? sanitize_text_field( $recommendation['reason'] ) : '',
            'estimated_impact' => isset( $recommendation['estimated_impact'] ) ? sanitize_key( $recommendation['estimated_impact'] ) : 'unknown',
            'status'           => 'queued',
            'scheduled_at'     => gmdate( 'Y-m-d H:i:s', $scheduled_ts ),
            'scheduled_ts'     => $scheduled_ts,
            'executed_at'      => null,
            'dedupe_key'       => $dedupe_key,
            'batched_events'   => array( isset( $event['event_id'] ) ? $event['event_id'] : '' ),
        );

        $jobs[] = $job;
        $this->storage->save_jobs( $jobs );

        return $job;
    }
}

/**
 * Queue runner.
 */
class PCM_Smart_Purge_Queue_Runner {
    /** @var PCM_Smart_Purge_Storage */
    protected $storage;

    public function __construct( $storage = null ) {
        $this->storage = $storage ? $storage : new PCM_Smart_Purge_Storage();
    }

    public function run() {
        $jobs = $this->storage->get_jobs();
        $now  = time();

        foreach ( $jobs as $index => $job ) {
            if ( ! isset( $job['status'] ) || 'queued' !== $job['status'] ) {
                continue;
            }

            $scheduled_ts = isset( $job['scheduled_ts'] ) ? absint( $job['scheduled_ts'] ) : 0;
            if ( $scheduled_ts > $now ) {
                continue;
            }

            $pre_impact = $this->capture_impact_baseline();

            if ( pcm_smart_purge_is_active_mode() ) {
                $this->execute_job( $job );
                $jobs[ $index ]['status'] = 'executed';
            } else {
                $jobs[ $index ]['status'] = 'shadowed';
            }

            $jobs[ $index ]['executed_at'] = current_time( 'mysql', true );

            $post_impact = $this->capture_impact_baseline();

            $this->storage->add_outcome(
                array(
                    'job_id'            => isset( $job['job_id'] ) ? $job['job_id'] : '',
                    'estimated_impact'  => isset( $job['estimated_impact'] ) ? $job['estimated_impact'] : 'unknown',
                    'observed_impact'   => $this->calculate_observed_impact( $pre_impact, $post_impact ),
                    'impact_baseline'   => $pre_impact,
                    'impact_after'      => $post_impact,
                    'notes'             => pcm_smart_purge_is_active_mode()
                        ? 'Active mode executed scoped purge hooks.'
                        : 'Shadow mode only; job recorded without changing current purge behavior.',
                    'timestamp'         => current_time( 'mysql', true ),
                )
            );
        }

        $this->storage->save_jobs( $jobs );
    }

    /**
     * @param array $job Job payload.
     *
     * @return void
     */
    protected function execute_job( $job ) {
        $scope   = isset( $job['scope'] ) ? sanitize_key( $job['scope'] ) : 'global';
        $targets = isset( $job['targets_json'] ) && is_array( $job['targets_json'] ) ? $job['targets_json'] : array();

        do_action( 'pcm_smart_purge_before_execute_job', $job );

        if ( 'global' === $scope ) {
            if ( function_exists( 'wp_cache_flush' ) ) {
                wp_cache_flush();
            }

            do_action( 'pcm_after_object_cache_flush' );
            do_action( 'pcm_after_edge_cache_purge' );
        } else {
            foreach ( $targets as $target_url ) {
                $target_url = esc_url_raw( $target_url );
                if ( '' === $target_url ) {
                    continue;
                }

                do_action( 'pcm_smart_purge_single_url', $target_url, $job );
            }
        }

        do_action( 'pcm_smart_purge_after_execute_job', $job );
    }

    /**
     * @return array
     */
    protected function capture_impact_baseline() {
        $hit_ratio = (float) get_option( 'pcm_latest_object_cache_hit_ratio', 0 );
        $evictions = (float) get_option( 'pcm_latest_object_cache_evictions', 0 );

        if ( function_exists( 'pcm_object_cache_collect_and_store_snapshot' ) ) {
            $snapshot = pcm_object_cache_collect_and_store_snapshot();
            if ( is_array( $snapshot ) && ! empty( $snapshot ) ) {
                $hit_ratio = isset( $snapshot['hit_ratio'] ) ? (float) $snapshot['hit_ratio'] : $hit_ratio;
                $evictions = isset( $snapshot['evictions'] ) ? (float) $snapshot['evictions'] : $evictions;
            }
        }

        return array(
            'object_cache_hit_ratio' => $hit_ratio,
            'object_cache_evictions' => $evictions,
            'captured_at'            => current_time( 'mysql', true ),
        );
    }

    /**
     * @param array $pre Pre metrics.
     * @param array $post Post metrics.
     *
     * @return array
     */
    protected function calculate_observed_impact( $pre, $post ) {
        $pre_ratio  = isset( $pre['object_cache_hit_ratio'] ) ? (float) $pre['object_cache_hit_ratio'] : 0;
        $post_ratio = isset( $post['object_cache_hit_ratio'] ) ? (float) $post['object_cache_hit_ratio'] : 0;

        $pre_evict  = isset( $pre['object_cache_evictions'] ) ? (float) $pre['object_cache_evictions'] : 0;
        $post_evict = isset( $post['object_cache_evictions'] ) ? (float) $post['object_cache_evictions'] : 0;

        return array(
            'hit_ratio_delta' => round( $post_ratio - $pre_ratio, 4 ),
            'evictions_delta' => round( $post_evict - $pre_evict, 4 ),
        );
    }
}

/**
 * Helper: record and enqueue smart purge event.
 *
 * @param string $source_action Action source.
 * @param string $object_type Object type.
 * @param int    $object_id Object id.
 * @param array  $context Context.
 *
 * @return void
 */
function pcm_smart_purge_record_and_enqueue( $source_action, $object_type, $object_id = 0, $context = array() ) {
    if ( ! pcm_smart_purge_is_enabled() ) {
        return;
    }

    $normalizer = new PCM_Smart_Purge_Event_Normalizer();
    $engine     = new PCM_Smart_Purge_Recommendation_Engine();
    $queue      = new PCM_Smart_Purge_Job_Queue();

    $event          = $normalizer->record_event( $source_action, $object_type, $object_id, $context );
    $recommendation = $engine->recommend( $event );
    $queue->enqueue( $event, $recommendation );
}

/**
 * Capture post/comment events and enqueue smart-purge jobs.
 */
function pcm_smart_purge_capture_post_event( $post_id ) {
    if ( ! pcm_smart_purge_is_enabled() || wp_is_post_revision( $post_id ) ) {
        return;
    }

    pcm_smart_purge_record_and_enqueue( 'save_post', 'post', $post_id );
}
add_action( 'save_post', 'pcm_smart_purge_capture_post_event', 20, 1 );

/**
 * @param int|string $comment_id Comment id.
 */
function pcm_smart_purge_capture_comment_event( $comment_id ) {
    if ( ! pcm_smart_purge_is_enabled() ) {
        return;
    }

    pcm_smart_purge_record_and_enqueue( 'comment_post', 'comment', absint( $comment_id ) );
}
add_action( 'comment_post', 'pcm_smart_purge_capture_comment_event', 20, 1 );

/**
 * Extra source events for bulk/import/update/programmatic triggers (A6.2).
 */
function pcm_smart_purge_capture_post_delete_event( $post_id ) {
    if ( ! pcm_smart_purge_is_enabled() ) {
        return;
    }

    pcm_smart_purge_record_and_enqueue( 'delete_post', 'post', absint( $post_id ), array( 'lifecycle' => 'delete' ) );
}
add_action( 'deleted_post', 'pcm_smart_purge_capture_post_delete_event', 20, 1 );

function pcm_smart_purge_capture_upgrader_event( $upgrader, $options ) {
    unset( $upgrader );

    if ( ! pcm_smart_purge_is_enabled() || ! is_array( $options ) ) {
        return;
    }

    $type   = isset( $options['type'] ) ? sanitize_key( $options['type'] ) : 'unknown';
    $action = isset( $options['action'] ) ? sanitize_key( $options['action'] ) : 'unknown';

    if ( ! in_array( $type, array( 'plugin', 'theme', 'translation', 'core' ), true ) ) {
        return;
    }

    pcm_smart_purge_record_and_enqueue( 'upgrader_process_complete', 'update', 0, array( 'type' => $type, 'action' => $action ) );
}
add_action( 'upgrader_process_complete', 'pcm_smart_purge_capture_upgrader_event', 20, 2 );

function pcm_smart_purge_capture_manual_flush_event() {
    if ( ! pcm_smart_purge_is_enabled() ) {
        return;
    }

    pcm_smart_purge_record_and_enqueue( 'manual_flush', 'flush', 0, array( 'source' => current_filter() ) );
}
add_action( 'pcm_after_object_cache_flush', 'pcm_smart_purge_capture_manual_flush_event' );
add_action( 'pcm_after_edge_cache_purge', 'pcm_smart_purge_capture_manual_flush_event' );

/**
 * Register cron cadence and schedule runner.
 *
 * @param array $schedules Schedules.
 *
 * @return array
 */
function pcm_smart_purge_register_schedule( $schedules ) {
    if ( ! isset( $schedules['pcm_every_2_minutes'] ) ) {
        $schedules['pcm_every_2_minutes'] = array(
            'interval' => 120,
            'display'  => __( 'Every 2 Minutes (PCM Smart Purge)', 'pressable_cache_management' ),
        );
    }

    return $schedules;
}
add_filter( 'cron_schedules', 'pcm_smart_purge_register_schedule' );

/**
 * Ensure cron is scheduled.
 */
function pcm_smart_purge_maybe_schedule_runner() {
    if ( ! pcm_smart_purge_is_enabled() ) {
        return;
    }

    if ( ! wp_next_scheduled( 'pcm_smart_purge_run_queue' ) ) {
        wp_schedule_event( time() + 60, 'pcm_every_2_minutes', 'pcm_smart_purge_run_queue' );
    }
}
add_action( 'init', 'pcm_smart_purge_maybe_schedule_runner' );

/**
 * Save smart purge settings from admin form (A6.4).
 *
 * @return void
 */
function pcm_smart_purge_handle_settings_post() {
    if ( ! is_admin() || empty( $_POST['pcm_smart_purge_settings_submit'] ) ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    check_admin_referer( 'pcm_smart_purge_settings_action', 'pcm_smart_purge_settings_nonce' );

    $active   = ! empty( $_POST['pcm_smart_purge_active_mode'] );
    $cooldown = isset( $_POST['pcm_smart_purge_cooldown_seconds'] ) ? absint( wp_unslash( $_POST['pcm_smart_purge_cooldown_seconds'] ) ) : 120;
    $defer    = isset( $_POST['pcm_smart_purge_defer_seconds'] ) ? absint( wp_unslash( $_POST['pcm_smart_purge_defer_seconds'] ) ) : 60;

    update_option( 'pcm_smart_purge_active_mode', $active ? 1 : 0, false );
    update_option( 'pcm_smart_purge_cooldown_seconds', max( 15, min( 3600, $cooldown ) ), false );
    update_option( 'pcm_smart_purge_defer_seconds', max( 0, min( 3600, $defer ) ), false );
}
add_action( 'admin_init', 'pcm_smart_purge_handle_settings_post' );

/**
 * Run queued jobs.
 */
function pcm_smart_purge_run_queue() {
    if ( ! pcm_smart_purge_is_enabled() ) {
        return;
    }

    $runner = new PCM_Smart_Purge_Queue_Runner();
    $runner->run();
}
add_action( 'pcm_smart_purge_run_queue', 'pcm_smart_purge_run_queue' );
