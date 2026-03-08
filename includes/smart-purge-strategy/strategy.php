<?php
/**
 * Smart Purge Strategy (Pillar 6).
 *
 * Starts in shadow mode: recommends and queues, without changing purge behavior.
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
 * Active mode gate. False keeps execution in shadow/log mode.
 *
 * @return bool
 */
function pcm_smart_purge_is_active_mode() {
    $active = false;

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

            if ( $status !== 'queued' ) {
                continue;
            }

            if ( isset( $job['dedupe_key'] ) && hash_equals( $job['dedupe_key'], $dedupe_key ) && ( $now - $scheduled ) <= $cooldown ) {
                $jobs[ $index ]['batched_events'][] = isset( $event['event_id'] ) ? $event['event_id'] : '';
                $this->storage->save_jobs( $jobs );

                return $jobs[ $index ];
            }
        }

        $job = array(
            'job_id'          => 'job_' . wp_generate_uuid4(),
            'scope'           => isset( $recommendation['scope'] ) ? sanitize_key( $recommendation['scope'] ) : 'global',
            'targets_json'    => $normalized_targets,
            'reason'          => isset( $recommendation['reason'] ) ? sanitize_text_field( $recommendation['reason'] ) : '',
            'estimated_impact'=> isset( $recommendation['estimated_impact'] ) ? sanitize_key( $recommendation['estimated_impact'] ) : 'unknown',
            'status'          => 'queued',
            'scheduled_at'    => gmdate( 'Y-m-d H:i:s', $now ),
            'scheduled_ts'    => $now,
            'executed_at'     => null,
            'dedupe_key'      => $dedupe_key,
            'batched_events'  => array( isset( $event['event_id'] ) ? $event['event_id'] : '' ),
        );

        $jobs[] = $job;
        $this->storage->save_jobs( $jobs );

        return $job;
    }
}

/**
 * Queue runner (shadow first).
 */
class PCM_Smart_Purge_Queue_Runner {
    /** @var PCM_Smart_Purge_Storage */
    protected $storage;

    public function __construct( $storage = null ) {
        $this->storage = $storage ? $storage : new PCM_Smart_Purge_Storage();
    }

    public function run() {
        $jobs = $this->storage->get_jobs();

        foreach ( $jobs as $index => $job ) {
            if ( ! isset( $job['status'] ) || 'queued' !== $job['status'] ) {
                continue;
            }

            $jobs[ $index ]['status']      = pcm_smart_purge_is_active_mode() ? 'executed' : 'shadowed';
            $jobs[ $index ]['executed_at'] = current_time( 'mysql', true );

            $this->storage->add_outcome(
                array(
                    'job_id'            => $job['job_id'],
                    'estimated_impact'  => isset( $job['estimated_impact'] ) ? $job['estimated_impact'] : 'unknown',
                    'observed_impact'   => 'not_measured',
                    'notes'             => pcm_smart_purge_is_active_mode()
                        ? 'Active mode enabled; purge execution hook point reached.'
                        : 'Shadow mode only; job recorded without changing current purge behavior.',
                    'timestamp'         => current_time( 'mysql', true ),
                )
            );
        }

        $this->storage->save_jobs( $jobs );
    }
}

/**
 * Capture post/comment events and enqueue smart-purge jobs.
 */
function pcm_smart_purge_capture_post_event( $post_id ) {
    if ( ! pcm_smart_purge_is_enabled() || wp_is_post_revision( $post_id ) ) {
        return;
    }

    $normalizer = new PCM_Smart_Purge_Event_Normalizer();
    $engine     = new PCM_Smart_Purge_Recommendation_Engine();
    $queue      = new PCM_Smart_Purge_Job_Queue();

    $event          = $normalizer->record_event( 'save_post', 'post', $post_id );
    $recommendation = $engine->recommend( $event );
    $queue->enqueue( $event, $recommendation );
}
add_action( 'save_post', 'pcm_smart_purge_capture_post_event', 20, 1 );

/**
 * @param int|string $comment_id Comment id.
 */
function pcm_smart_purge_capture_comment_event( $comment_id ) {
    if ( ! pcm_smart_purge_is_enabled() ) {
        return;
    }

    $normalizer = new PCM_Smart_Purge_Event_Normalizer();
    $engine     = new PCM_Smart_Purge_Recommendation_Engine();
    $queue      = new PCM_Smart_Purge_Job_Queue();

    $event          = $normalizer->record_event( 'comment_post', 'comment', absint( $comment_id ) );
    $recommendation = $engine->recommend( $event );
    $queue->enqueue( $event, $recommendation );
}
add_action( 'comment_post', 'pcm_smart_purge_capture_comment_event', 20, 1 );

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
