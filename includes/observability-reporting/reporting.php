<?php
/**
 * Observability & Reporting (Pillar 7).
 *
 * @package PressableCacheManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Feature flag for reporting.
 *
 * @return bool
 */
function pcm_reporting_is_enabled() {
    $enabled = false;

    return (bool) apply_filters( 'pcm_enable_observability_reporting', $enabled );
}

/**
 * Canonical metrics registry.
 */
class PCM_Metric_Registry {
    /**
     * @return array
     */
    public function get_catalog() {
        return array(
            'cacheability_score'        => array( 'unit' => 'score', 'source' => 'cacheability_advisor' ),
            'cache_buster_incidence'    => array( 'unit' => 'count', 'source' => 'cache_busters' ),
            'purge_frequency_by_scope'  => array( 'unit' => 'count', 'source' => 'smart_purge' ),
            'object_cache_hit_ratio'    => array( 'unit' => 'percent', 'source' => 'object_cache_intelligence' ),
            'object_cache_evictions'    => array( 'unit' => 'count', 'source' => 'object_cache_intelligence' ),
            'opcache_memory_pressure'   => array( 'unit' => 'percent', 'source' => 'opcache_awareness' ),
            'opcache_restarts'          => array( 'unit' => 'count', 'source' => 'opcache_awareness' ),
            'batcache_hits'             => array( 'unit' => 'count', 'source' => 'runtime_headers' ),
        );
    }

    /**
     * @param string $metric_key Metric key.
     *
     * @return bool
     */
    public function has_metric( $metric_key ) {
        $catalog = $this->get_catalog();

        return isset( $catalog[ $metric_key ] );
    }
}

/**
 * Lightweight rollup storage using options in v1 scaffolding.
 */
class PCM_Metric_Rollup_Storage {
    /** @var string */
    protected $key = 'pcm_metric_rollups_v1';

    /** @var int */
    protected $max_rows = 2000;

    /**
     * @param array $row Rollup row.
     *
     * @return void
     */
    public function append_rollup( $row ) {
        $rows   = $this->get_rollups();
        $rows[] = $row;
        update_option( $this->key, array_slice( $rows, -1 * $this->max_rows ), false );
    }

    /**
     * @return array
     */
    public function get_rollups() {
        $rows = get_option( $this->key, array() );

        return is_array( $rows ) ? $rows : array();
    }

    /**
     * @param int $retention_days Retention period.
     *
     * @return void
     */
    public function cleanup( $retention_days = 90 ) {
        $rows       = $this->get_rollups();
        $retention  = max( 7, min( 365, absint( $retention_days ) ) );
        $cutoff_ts  = time() - ( DAY_IN_SECONDS * $retention );

        $rows = array_values(
            array_filter(
                $rows,
                static function ( $row ) use ( $cutoff_ts ) {
                    $bucket_start = isset( $row['bucket_start'] ) ? strtotime( $row['bucket_start'] ) : 0;

                    return $bucket_start >= $cutoff_ts;
                }
            )
        );

        update_option( $this->key, $rows, false );
    }
}

/**
 * Rollup service.
 */
class PCM_Metric_Rollup_Service {
    /** @var PCM_Metric_Registry */
    protected $registry;

    /** @var PCM_Metric_Rollup_Storage */
    protected $storage;

    public function __construct( $registry = null, $storage = null ) {
        $this->registry = $registry ? $registry : new PCM_Metric_Registry();
        $this->storage  = $storage ? $storage : new PCM_Metric_Rollup_Storage();
    }

    /**
     * @param string $metric_key Metric key.
     * @param float  $value Metric value.
     * @param array  $dimensions Dimensions.
     * @param string $bucket_size Bucket size label.
     *
     * @return bool
     */
    public function write_rollup( $metric_key, $value, $dimensions = array(), $bucket_size = '1d' ) {
        $metric_key = sanitize_key( $metric_key );

        if ( ! $this->registry->has_metric( $metric_key ) ) {
            return false;
        }

        $row = array(
            'metric_key'      => $metric_key,
            'bucket_start'    => gmdate( 'Y-m-d 00:00:00' ),
            'bucket_size'     => sanitize_text_field( $bucket_size ),
            'value'           => round( (float) $value, 4 ),
            'dimensions_json' => (array) $dimensions,
        );

        $this->storage->append_rollup( $row );

        return true;
    }

    /**
     * @param string $range 24h|7d|30d.
     * @param array  $metric_keys Metric keys.
     *
     * @return array
     */
    public function query_trends( $range = '7d', $metric_keys = array() ) {
        $range_days = array(
            '24h' => 1,
            '7d'  => 7,
            '30d' => 30,
        );

        $days     = isset( $range_days[ $range ] ) ? $range_days[ $range ] : 7;
        $cutoff   = time() - ( DAY_IN_SECONDS * $days );
        $keys     = array_map( 'sanitize_key', (array) $metric_keys );
        $rollups  = $this->storage->get_rollups();

        return array_values(
            array_filter(
                $rollups,
                static function ( $row ) use ( $cutoff, $keys ) {
                    $bucket_ts = isset( $row['bucket_start'] ) ? strtotime( $row['bucket_start'] ) : 0;
                    if ( $bucket_ts < $cutoff ) {
                        return false;
                    }

                    if ( empty( $keys ) ) {
                        return true;
                    }

                    return isset( $row['metric_key'] ) && in_array( $row['metric_key'], $keys, true );
                }
            )
        );
    }
}

/**
 * Export service for JSON / CSV with capability checks and basic redaction.
 */
class PCM_Report_Export_Service {
    /**
     * @var PCM_Metric_Rollup_Service
     */
    protected $rollup_service;

    public function __construct( $rollup_service = null ) {
        $this->rollup_service = $rollup_service ? $rollup_service : new PCM_Metric_Rollup_Service();
    }

    /**
     * @param string $format json|csv
     * @param string $range Date range key.
     * @param array  $metric_keys Filters.
     *
     * @return array
     */
    public function export( $format = 'json', $range = '7d', $metric_keys = array() ) {
        if ( function_exists( 'pcm_get_privacy_settings' ) ) {
            $privacy = pcm_get_privacy_settings();
            if ( 'admin_only' === $privacy['export_restrictions'] && ! current_user_can( 'manage_options' ) ) {
                return array(
                    'success' => false,
                    'error'   => 'permission_denied',
                );
            }
        }

        if ( function_exists( 'pcm_current_user_can' ) ) {
            $can_export = pcm_current_user_can( 'pcm_export_reports' );
        } else {
            $can_export = current_user_can( 'manage_options' );
        }

        if ( ! $can_export ) {
            return array(
                'success' => false,
                'error'   => 'permission_denied',
            );
        }

        $rows = $this->rollup_service->query_trends( $range, $metric_keys );
        $rows = $this->redact_rows( $rows );

        if ( 'csv' === $format ) {
            return array(
                'success' => true,
                'format'  => 'csv',
                'content' => $this->to_csv( $rows ),
            );
        }

        return array(
            'success' => true,
            'format'  => 'json',
            'content' => wp_json_encode( $rows ),
        );
    }

    /**
     * @param array $rows Rows.
     *
     * @return array
     */
    protected function redact_rows( $rows ) {
        foreach ( $rows as $index => $row ) {
            $dimensions = isset( $row['dimensions_json'] ) ? (array) $row['dimensions_json'] : array();

            if ( function_exists( 'pcm_privacy_redact_value' ) ) {
                $rows[ $index ]['dimensions_json'] = pcm_privacy_redact_value( $dimensions );
                continue;
            }

            if ( isset( $dimensions['email'] ) ) {
                unset( $dimensions['email'] );
            }
            if ( isset( $dimensions['user_login'] ) ) {
                unset( $dimensions['user_login'] );
            }

            $rows[ $index ]['dimensions_json'] = $dimensions;
        }

        return $rows;
    }

    /**
     * @param array $rows Rows.
     *
     * @return string
     */
    protected function to_csv( $rows ) {
        $fp = fopen( 'php://temp', 'r+' );
        fputcsv( $fp, array( 'metric_key', 'bucket_start', 'bucket_size', 'value', 'dimensions_json' ) );

        foreach ( $rows as $row ) {
            fputcsv(
                $fp,
                array(
                    isset( $row['metric_key'] ) ? $row['metric_key'] : '',
                    isset( $row['bucket_start'] ) ? $row['bucket_start'] : '',
                    isset( $row['bucket_size'] ) ? $row['bucket_size'] : '',
                    isset( $row['value'] ) ? $row['value'] : '',
                    wp_json_encode( isset( $row['dimensions_json'] ) ? $row['dimensions_json'] : array() ),
                )
            );
        }

        rewind( $fp );
        $csv = stream_get_contents( $fp );
        fclose( $fp );

        return (string) $csv;
    }
}

/**
 * Weekly digest scheduler and sender.
 */
class PCM_Report_Digest_Service {
    /** @var PCM_Metric_Rollup_Service */
    protected $rollup_service;

    public function __construct( $rollup_service = null ) {
        $this->rollup_service = $rollup_service ? $rollup_service : new PCM_Metric_Rollup_Service();
    }

    /**
     * @return void
     */
    public function send_weekly_digest() {
        if ( ! pcm_reporting_is_enabled() ) {
            return;
        }

        $recipients = get_option( 'pcm_report_digest_recipients', get_option( 'admin_email' ) );
        $recipients = array_filter( array_map( 'sanitize_email', array_map( 'trim', explode( ',', (string) $recipients ) ) ) );

        if ( empty( $recipients ) ) {
            return;
        }

        $rows = $this->rollup_service->query_trends( '7d' );

        $subject = __( '[Pressable Cache Management] Weekly cache report', 'pressable_cache_management' );
        $body    = $this->build_digest_body( $rows );

        foreach ( $recipients as $email ) {
            wp_mail( $email, $subject, $body );
        }
    }

    /**
     * @param array $rows Rows.
     *
     * @return string
     */
    protected function build_digest_body( $rows ) {
        $lines = array();
        $lines[] = 'Pressable Cache Management Weekly Digest';
        $lines[] = 'Generated: ' . gmdate( 'Y-m-d H:i:s' ) . ' UTC';
        $lines[] = '';
        $lines[] = 'Top metric snapshots (last 7 days):';

        $top = array_slice( $rows, -10 );
        foreach ( $top as $row ) {
            $lines[] = sprintf(
                '- %s @ %s = %s',
                isset( $row['metric_key'] ) ? $row['metric_key'] : 'unknown_metric',
                isset( $row['bucket_start'] ) ? $row['bucket_start'] : 'unknown_time',
                isset( $row['value'] ) ? $row['value'] : 'n/a'
            );
        }

        $lines[] = '';
        $lines[] = 'Review full diagnostics in WP Admin > Pressable Cache Management > Reports.';

        return implode( "\n", $lines );
    }
}

/**
 * Register schedules.
 *
 * @param array $schedules Existing schedules.
 *
 * @return array
 */
function pcm_reporting_register_schedules( $schedules ) {
    if ( ! isset( $schedules['pcm_daily'] ) ) {
        $schedules['pcm_daily'] = array(
            'interval' => DAY_IN_SECONDS,
            'display'  => __( 'Once Daily (PCM Reporting)', 'pressable_cache_management' ),
        );
    }

    if ( ! isset( $schedules['pcm_weekly'] ) ) {
        $schedules['pcm_weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display'  => __( 'Once Weekly (PCM Reporting)', 'pressable_cache_management' ),
        );
    }

    return $schedules;
}
add_filter( 'cron_schedules', 'pcm_reporting_register_schedules' );

/**
 * Schedule jobs when feature is enabled.
 *
 * @return void
 */
function pcm_reporting_maybe_schedule_jobs() {
    if ( ! pcm_reporting_is_enabled() ) {
        return;
    }

    if ( ! wp_next_scheduled( 'pcm_reporting_daily_rollup' ) ) {
        wp_schedule_event( time() + 120, 'pcm_daily', 'pcm_reporting_daily_rollup' );
    }

    if ( ! wp_next_scheduled( 'pcm_reporting_weekly_digest' ) ) {
        wp_schedule_event( time() + 300, 'pcm_weekly', 'pcm_reporting_weekly_digest' );
    }
}
add_action( 'init', 'pcm_reporting_maybe_schedule_jobs' );

/**
 * Daily rollup aggregation hook.
 *
 * @return void
 */
function pcm_reporting_daily_rollup() {
    if ( ! pcm_reporting_is_enabled() ) {
        return;
    }

    $rollups = new PCM_Metric_Rollup_Service();

    $cacheability_score = pcm_reporting_latest_cacheability_score();
    $rollups->write_rollup( 'cacheability_score', $cacheability_score );

    $cache_buster_incidence = function_exists( 'pcm_cache_busters_get_total_incidence' ) ? (float) pcm_cache_busters_get_total_incidence( '7d' ) : 0.0;
    $rollups->write_rollup( 'cache_buster_incidence', $cache_buster_incidence );

    $purge_frequency = pcm_reporting_latest_purge_frequency();
    $rollups->write_rollup( 'purge_frequency_by_scope', $purge_frequency );

    $object_snapshot = function_exists( 'pcm_object_cache_collect_and_store_snapshot' ) ? pcm_object_cache_collect_and_store_snapshot() : array();
    $object_hit_ratio = isset( $object_snapshot['hit_ratio'] ) ? (float) $object_snapshot['hit_ratio'] : (float) get_option( 'pcm_latest_object_cache_hit_ratio', 0 );
    $object_evictions = isset( $object_snapshot['evictions'] ) ? (float) $object_snapshot['evictions'] : (float) get_option( 'pcm_latest_object_cache_evictions', 0 );

    $rollups->write_rollup( 'object_cache_hit_ratio', $object_hit_ratio );
    $rollups->write_rollup( 'object_cache_evictions', $object_evictions );

    $opcache_snapshot = function_exists( 'pcm_opcache_collect_and_store_snapshot' ) ? pcm_opcache_collect_and_store_snapshot() : array();
    $opcache_memory_pressure = isset( $opcache_snapshot['memory']['used_memory'], $opcache_snapshot['memory']['free_memory'], $opcache_snapshot['memory']['wasted_memory'] )
        ? (float) pcm_opcache_percent(
            $opcache_snapshot['memory']['used_memory'] + $opcache_snapshot['memory']['wasted_memory'],
            $opcache_snapshot['memory']['used_memory'] + $opcache_snapshot['memory']['free_memory'] + $opcache_snapshot['memory']['wasted_memory']
        )
        : (float) get_option( 'pcm_latest_opcache_memory_pressure', 0 );
    $opcache_restarts = isset( $opcache_snapshot['statistics']['restart_total'] )
        ? (float) $opcache_snapshot['statistics']['restart_total']
        : (float) get_option( 'pcm_latest_opcache_restarts', 0 );

    $rollups->write_rollup( 'opcache_memory_pressure', $opcache_memory_pressure );
    $rollups->write_rollup( 'opcache_restarts', $opcache_restarts );

    $rollups->write_rollup( 'batcache_hits', (float) pcm_reporting_latest_batcache_hits() );

    $storage = new PCM_Metric_Rollup_Storage();
    $storage->cleanup( (int) get_option( 'pcm_reporting_retention_days', 90 ) );
}
add_action( 'pcm_reporting_daily_rollup', 'pcm_reporting_daily_rollup' );

/**
 * Weekly digest hook.
 *
 * @return void
 */
function pcm_reporting_weekly_digest() {
    $service = new PCM_Report_Digest_Service();
    $service->send_weekly_digest();
}
add_action( 'pcm_reporting_weekly_digest', 'pcm_reporting_weekly_digest' );


/**
 * @return float
 */
function pcm_reporting_latest_cacheability_score() {
    global $wpdb;

    $runs_table = $wpdb->prefix . 'pcm_scan_runs';
    $urls_table = $wpdb->prefix . 'pcm_scan_urls';

    $run_id = (int) $wpdb->get_var( "SELECT id FROM {$runs_table} WHERE status = 'completed' ORDER BY id DESC LIMIT 1" );
    if ( $run_id <= 0 ) {
        return (float) get_option( 'pcm_latest_cacheability_score', 0 );
    }

    $avg = $wpdb->get_var( $wpdb->prepare( "SELECT AVG(score) FROM {$urls_table} WHERE run_id = %d", $run_id ) );

    return null !== $avg ? (float) round( (float) $avg, 2 ) : 0.0;
}

/**
 * @return float
 */
function pcm_reporting_latest_purge_frequency() {
    $rows = get_option( 'pcm_smart_purge_jobs_v1', array() );
    if ( ! is_array( $rows ) ) {
        return 0.0;
    }

    $cutoff = time() - DAY_IN_SECONDS;
    $count  = 0;

    foreach ( $rows as $job ) {
        $scheduled = isset( $job['scheduled_at'] ) ? strtotime( (string) $job['scheduled_at'] ) : 0;
        if ( $scheduled >= $cutoff ) {
            $count++;
        }
    }

    return (float) $count;
}

/**
 * @return int
 */
function pcm_reporting_latest_batcache_hits() {
    return (int) get_option( 'pcm_batcache_hits_24h', 0 );
}

/**
 * AJAX: query reporting trends.
 *
 * @return void
 */
function pcm_ajax_reporting_trends() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_view_diagnostics' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        $can_view = function_exists( 'pcm_current_user_can' ) ? pcm_current_user_can( 'pcm_view_diagnostics' ) : current_user_can( 'manage_options' );
        if ( ! $can_view ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $range       = isset( $_REQUEST['range'] ) ? sanitize_key( wp_unslash( $_REQUEST['range'] ) ) : '7d';
    $metric_keys = isset( $_REQUEST['metric_keys'] ) ? (array) wp_unslash( $_REQUEST['metric_keys'] ) : array();

    $service = new PCM_Metric_Rollup_Service();

    wp_send_json_success(
        array(
            'range'  => $range,
            'rows'   => $service->query_trends( $range, $metric_keys ),
        )
    );
}
add_action( 'wp_ajax_pcm_reporting_trends', 'pcm_ajax_reporting_trends' );

/**
 * AJAX: secure export endpoint for JSON/CSV.
 *
 * @return void
 */
function pcm_ajax_reporting_export() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_export_reports' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );
    }

    $format      = isset( $_REQUEST['format'] ) ? sanitize_key( wp_unslash( $_REQUEST['format'] ) ) : 'json';
    $range       = isset( $_REQUEST['range'] ) ? sanitize_key( wp_unslash( $_REQUEST['range'] ) ) : '7d';
    $metric_keys = isset( $_REQUEST['metric_keys'] ) ? (array) wp_unslash( $_REQUEST['metric_keys'] ) : array();

    $exporter = new PCM_Report_Export_Service();
    $result   = $exporter->export( $format, $range, $metric_keys );

    if ( empty( $result['success'] ) ) {
        wp_send_json_error( $result, 403 );
    }

    wp_send_json_success( $result );
}
add_action( 'wp_ajax_pcm_reporting_export', 'pcm_ajax_reporting_export' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
    /**
     * WP-CLI reporting commands (A7.4).
     */
    class PCM_Reporting_CLI_Command {
        /**
         * Trigger daily rollup.
         *
         * ## EXAMPLES
         *     wp pcm-report rollup
         */
        public function rollup() {
            pcm_reporting_daily_rollup();
            \WP_CLI::success( 'Daily rollup executed.' );
        }

        /**
         * Show trend rows.
         *
         * ## OPTIONS
         * [--range=<range>]
         * : 24h, 7d, or 30d.
         *
         * ## EXAMPLES
         *     wp pcm-report trends --range=7d
         */
        public function trends( $args, $assoc_args ) {
            unset( $args );

            $range = isset( $assoc_args['range'] ) ? sanitize_key( $assoc_args['range'] ) : '7d';
            $service = new PCM_Metric_Rollup_Service();
            $rows = $service->query_trends( $range );

            \WP_CLI::line( wp_json_encode( $rows ) );
        }

        /**
         * Export reporting rows.
         *
         * ## OPTIONS
         * [--format=<format>]
         * : json or csv
         * [--range=<range>]
         * : 24h, 7d, or 30d.
         *
         * ## EXAMPLES
         *     wp pcm-report export --format=csv --range=7d
         */
        public function export( $args, $assoc_args ) {
            unset( $args );

            $format = isset( $assoc_args['format'] ) ? sanitize_key( $assoc_args['format'] ) : 'json';
            $range  = isset( $assoc_args['range'] ) ? sanitize_key( $assoc_args['range'] ) : '7d';

            $service = new PCM_Report_Export_Service();
            $result  = $service->export( $format, $range );

            if ( empty( $result['success'] ) ) {
                \WP_CLI::error( 'Export failed: ' . ( isset( $result['error'] ) ? $result['error'] : 'unknown' ) );
                return;
            }

            \WP_CLI::line( isset( $result['content'] ) ? (string) $result['content'] : '' );
        }
    }

    \WP_CLI::add_command( 'pcm-report', 'PCM_Reporting_CLI_Command' );
}
