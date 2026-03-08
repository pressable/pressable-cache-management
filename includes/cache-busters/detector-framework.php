<?php
/**
 * Cache Busters detector framework (Pillar 2).
 *
 * @package PressableCacheManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Feature flag for cache buster detection.
 *
 * Depends on Cacheability Advisor data collection.
 *
 * @return bool
 */
function pcm_cache_busters_is_enabled() {
    $enabled = function_exists( 'pcm_cacheability_advisor_is_enabled' ) && pcm_cacheability_advisor_is_enabled();

    return (bool) apply_filters( 'pcm_enable_cache_busters', $enabled );
}

/**
 * Normalized cache-buster event value object.
 */
class PCM_Cache_Buster_Event {
    /** @var string */
    public $category;

    /** @var string */
    public $signature;

    /** @var string */
    public $confidence;

    /** @var int */
    public $count;

    /** @var string */
    public $likely_source;

    /** @var array */
    public $affected_urls;

    /** @var array */
    public $evidence_samples;

    /**
     * @param array $args Event args.
     */
    public function __construct( $args = array() ) {
        $this->category         = isset( $args['category'] ) ? sanitize_key( $args['category'] ) : '';
        $this->signature        = isset( $args['signature'] ) ? sanitize_text_field( $args['signature'] ) : '';
        $this->confidence       = isset( $args['confidence'] ) ? sanitize_key( $args['confidence'] ) : 'low';
        $this->count            = isset( $args['count'] ) ? absint( $args['count'] ) : 0;
        $this->likely_source    = isset( $args['likely_source'] ) ? sanitize_text_field( $args['likely_source'] ) : 'unknown';
        $this->affected_urls    = isset( $args['affected_urls'] ) && is_array( $args['affected_urls'] ) ? $args['affected_urls'] : array();
        $this->evidence_samples = isset( $args['evidence_samples'] ) && is_array( $args['evidence_samples'] ) ? $args['evidence_samples'] : array();
    }

    /**
     * @return array
     */
    public function to_array() {
        return array(
            'category'         => $this->category,
            'signature'        => $this->signature,
            'confidence'       => $this->confidence,
            'count'            => $this->count,
            'likely_source'    => $this->likely_source,
            'affected_urls'    => array_values( array_unique( array_map( 'esc_url_raw', $this->affected_urls ) ) ),
            'evidence_samples' => $this->evidence_samples,
        );
    }
}

/**
 * Detector contract.
 */
interface PCM_Cache_Buster_Detector_Interface {
    /**
     * Unique detector key.
     *
     * @return string
     */
    public function get_key();

    /**
     * Detect cache busters from one snapshot.
     *
     * @param array $snapshot Snapshot payload.
     *
     * @return PCM_Cache_Buster_Event[]
     */
    public function detect( $snapshot );
}

/**
 * Detector registry and execution.
 */
class PCM_Cache_Buster_Detector_Registry {
    /** @var array */
    protected $detectors = array();

    /**
     * @param PCM_Cache_Buster_Detector_Interface $detector Detector instance.
     *
     * @return void
     */
    public function register( PCM_Cache_Buster_Detector_Interface $detector ) {
        $this->detectors[ $detector->get_key() ] = $detector;
    }

    /**
     * @return array
     */
    public function get_detectors() {
        return $this->detectors;
    }

    /**
     * @param array $snapshot Snapshot payload.
     *
     * @return array
     */
    public function run_all( $snapshot ) {
        $events = array();

        foreach ( $this->detectors as $detector ) {
            $detector_events = $detector->detect( $snapshot );

            if ( ! is_array( $detector_events ) ) {
                continue;
            }

            foreach ( $detector_events as $event ) {
                if ( $event instanceof PCM_Cache_Buster_Event ) {
                    $events[] = $event->to_array();
                }
            }
        }

        return $events;
    }
}

/**
 * Snapshot provider (latest scan run).
 */
class PCM_Cache_Buster_Snapshot_Provider {
    /**
     * @return array
     */
    public function get_latest_snapshot() {
        global $wpdb;

        $runs_table     = $wpdb->prefix . 'pcm_scan_runs';
        $urls_table     = $wpdb->prefix . 'pcm_scan_urls';
        $findings_table = $wpdb->prefix . 'pcm_findings';

        $run = $wpdb->get_row( "SELECT * FROM {$runs_table} WHERE status = 'completed' ORDER BY id DESC LIMIT 1", ARRAY_A );

        if ( ! $run ) {
            return array(
                'run'      => null,
                'urls'     => array(),
                'findings' => array(),
            );
        }

        $run_id = absint( $run['id'] );

        $urls_query = $wpdb->prepare( "SELECT * FROM {$urls_table} WHERE run_id = %d", $run_id );
        $urls       = $wpdb->get_results( $urls_query, ARRAY_A );

        $findings_query = $wpdb->prepare( "SELECT * FROM {$findings_table} WHERE run_id = %d", $run_id );
        $findings       = $wpdb->get_results( $findings_query, ARRAY_A );

        foreach ( $findings as $index => $finding ) {
            $decoded = ! empty( $finding['evidence_json'] ) ? json_decode( $finding['evidence_json'], true ) : array();

            $findings[ $index ]['evidence'] = is_array( $decoded ) ? $decoded : array();
            unset( $findings[ $index ]['evidence_json'] );
        }

        return array(
            'run'      => $run,
            'urls'     => $urls,
            'findings' => $findings,
        );
    }
}

/**
 * Base detector helpers.
 */
abstract class PCM_Cache_Buster_Base_Detector implements PCM_Cache_Buster_Detector_Interface {
    /**
     * @param string $header_value Header value.
     *
     * @return array
     */
    protected function split_header_csv( $header_value ) {
        if ( ! is_string( $header_value ) || '' === $header_value ) {
            return array();
        }

        $parts = array_map( 'trim', explode( ',', $header_value ) );

        return array_filter( $parts );
    }

    /**
     * @param string $cookie_line Set-Cookie value.
     *
     * @return string
     */
    protected function get_cookie_name_only( $cookie_line ) {
        $first_part = strtok( (string) $cookie_line, ';' );
        $pair       = explode( '=', (string) $first_part, 2 );

        return sanitize_key( isset( $pair[0] ) ? trim( $pair[0] ) : '' );
    }

    /**
     * @param string $url URL.
     *
     * @return string
     */
    protected function normalize_url_for_report( $url ) {
        $parts = wp_parse_url( $url );

        if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
            return esc_url_raw( $url );
        }

        $path = isset( $parts['path'] ) ? $parts['path'] : '/';

        return esc_url_raw( $parts['scheme'] . '://' . $parts['host'] . $path );
    }
}

/**
 * Detects anonymous Set-Cookie cache busters.
 */
class PCM_Cache_Buster_Cookie_Detector extends PCM_Cache_Buster_Base_Detector {
    public function get_key() {
        return 'cookie';
    }

    public function detect( $snapshot ) {
        $events = array();

        if ( empty( $snapshot['findings'] ) || ! is_array( $snapshot['findings'] ) ) {
            return $events;
        }

        $cookie_urls = array();

        foreach ( $snapshot['findings'] as $finding ) {
            $headers = isset( $finding['evidence']['headers'] ) && is_array( $finding['evidence']['headers'] ) ? $finding['evidence']['headers'] : array();

            if ( empty( $headers['set-cookie'] ) ) {
                continue;
            }

            $cookie_lines = is_array( $headers['set-cookie'] ) ? $headers['set-cookie'] : array( $headers['set-cookie'] );

            foreach ( $cookie_lines as $cookie_line ) {
                $cookie_name = $this->get_cookie_name_only( $cookie_line );
                if ( '' === $cookie_name ) {
                    continue;
                }

                if ( ! isset( $cookie_urls[ $cookie_name ] ) ) {
                    $cookie_urls[ $cookie_name ] = array();
                }

                $cookie_urls[ $cookie_name ][] = isset( $finding['url'] ) ? $finding['url'] : '';
            }
        }

        foreach ( $cookie_urls as $cookie_name => $urls ) {
            $events[] = new PCM_Cache_Buster_Event(
                array(
                    'category'      => 'cookies',
                    'signature'     => 'set-cookie:' . $cookie_name,
                    'confidence'    => 'high',
                    'count'         => count( $urls ),
                    'likely_source' => 'runtime-header',
                    'affected_urls' => array_values( array_unique( $urls ) ),
                    'evidence_samples' => array(
                        array(
                            'cookie_name' => $cookie_name,
                        ),
                    ),
                )
            );
        }

        return $events;
    }
}

/**
 * Detect noisy query parameter fragmentation.
 */
class PCM_Cache_Buster_Query_Detector extends PCM_Cache_Buster_Base_Detector {
    public function get_key() {
        return 'query';
    }

    public function detect( $snapshot ) {
        $events = array();

        if ( empty( $snapshot['urls'] ) || ! is_array( $snapshot['urls'] ) ) {
            return $events;
        }

        $tracked_keys = array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'msclkid' );
        $key_to_urls  = array();

        foreach ( $snapshot['urls'] as $url_row ) {
            if ( empty( $url_row['url'] ) ) {
                continue;
            }

            $query_string = wp_parse_url( $url_row['url'], PHP_URL_QUERY );
            if ( empty( $query_string ) ) {
                continue;
            }

            parse_str( $query_string, $params );
            if ( empty( $params ) ) {
                continue;
            }

            foreach ( array_keys( $params ) as $param_key ) {
                $normalized_key = sanitize_key( $param_key );
                if ( ! in_array( $normalized_key, $tracked_keys, true ) ) {
                    continue;
                }

                if ( ! isset( $key_to_urls[ $normalized_key ] ) ) {
                    $key_to_urls[ $normalized_key ] = array();
                }

                $key_to_urls[ $normalized_key ][] = $this->normalize_url_for_report( $url_row['url'] );
            }
        }

        foreach ( $key_to_urls as $key => $urls ) {
            $events[] = new PCM_Cache_Buster_Event(
                array(
                    'category'      => 'query_params',
                    'signature'     => 'query-param:' . $key,
                    'confidence'    => 'high',
                    'count'         => count( $urls ),
                    'likely_source' => 'tracking-query-params',
                    'affected_urls' => array_values( array_unique( $urls ) ),
                    'evidence_samples' => array(
                        array( 'param' => $key ),
                    ),
                )
            );
        }

        return $events;
    }
}

/**
 * Detect high-cardinality Vary headers.
 */
class PCM_Cache_Buster_Vary_Detector extends PCM_Cache_Buster_Base_Detector {
    public function get_key() {
        return 'vary';
    }

    public function detect( $snapshot ) {
        $events = array();

        if ( empty( $snapshot['findings'] ) || ! is_array( $snapshot['findings'] ) ) {
            return $events;
        }

        $watch_headers = array( 'cookie', 'user-agent', 'origin', 'accept-language' );
        $vary_urls      = array();

        foreach ( $snapshot['findings'] as $finding ) {
            $headers = isset( $finding['evidence']['headers'] ) && is_array( $finding['evidence']['headers'] ) ? $finding['evidence']['headers'] : array();
            $vary    = isset( $headers['vary'] ) ? strtolower( (string) $headers['vary'] ) : '';

            if ( '' === $vary ) {
                continue;
            }

            $values = $this->split_header_csv( $vary );
            foreach ( $values as $value ) {
                $normalized = sanitize_key( $value );
                if ( ! in_array( $normalized, $watch_headers, true ) ) {
                    continue;
                }

                if ( ! isset( $vary_urls[ $normalized ] ) ) {
                    $vary_urls[ $normalized ] = array();
                }

                $vary_urls[ $normalized ][] = isset( $finding['url'] ) ? $finding['url'] : '';
            }
        }

        foreach ( $vary_urls as $header => $urls ) {
            $events[] = new PCM_Cache_Buster_Event(
                array(
                    'category'      => 'vary',
                    'signature'     => 'vary:' . $header,
                    'confidence'    => 'medium',
                    'count'         => count( $urls ),
                    'likely_source' => 'response-header',
                    'affected_urls' => array_values( array_unique( $urls ) ),
                    'evidence_samples' => array(
                        array( 'vary_header' => $header ),
                    ),
                )
            );
        }

        return $events;
    }
}

/**
 * Detect no-cache directives on public pages.
 */
class PCM_Cache_Buster_No_Cache_Detector extends PCM_Cache_Buster_Base_Detector {
    public function get_key() {
        return 'no_cache';
    }

    public function detect( $snapshot ) {
        $events = array();

        if ( empty( $snapshot['findings'] ) || ! is_array( $snapshot['findings'] ) ) {
            return $events;
        }

        $directives = array( 'no-store', 'private', 'max-age=0' );
        $directive_urls = array();

        foreach ( $snapshot['findings'] as $finding ) {
            $headers       = isset( $finding['evidence']['headers'] ) && is_array( $finding['evidence']['headers'] ) ? $finding['evidence']['headers'] : array();
            $cache_control = isset( $headers['cache-control'] ) ? strtolower( (string) $headers['cache-control'] ) : '';

            if ( '' === $cache_control ) {
                continue;
            }

            foreach ( $directives as $directive ) {
                if ( false === strpos( $cache_control, $directive ) ) {
                    continue;
                }

                if ( ! isset( $directive_urls[ $directive ] ) ) {
                    $directive_urls[ $directive ] = array();
                }

                $directive_urls[ $directive ][] = isset( $finding['url'] ) ? $finding['url'] : '';
            }
        }

        foreach ( $directive_urls as $directive => $urls ) {
            $events[] = new PCM_Cache_Buster_Event(
                array(
                    'category'      => 'no_cache',
                    'signature'     => 'cache-control:' . $directive,
                    'confidence'    => 'high',
                    'count'         => count( $urls ),
                    'likely_source' => 'cache-control-header',
                    'affected_urls' => array_values( array_unique( $urls ) ),
                    'evidence_samples' => array(
                        array( 'directive' => $directive ),
                    ),
                )
            );
        }

        return $events;
    }
}

/**
 * Detect frequent full-site purge events using known timestamps.
 */
class PCM_Cache_Buster_Purge_Detector extends PCM_Cache_Buster_Base_Detector {
    public function get_key() {
        return 'purge';
    }

    public function detect( $snapshot ) {
        unset( $snapshot );

        $known_timestamps = array(
            get_option( 'flush-object-cache-time-stamp', '' ),
            get_option( 'edge-cache-purge-time-stamp', '' ),
        );

        $known_timestamps = array_filter( array_map( 'sanitize_text_field', $known_timestamps ) );

        if ( count( $known_timestamps ) < 2 ) {
            return array();
        }

        return array(
            new PCM_Cache_Buster_Event(
                array(
                    'category'      => 'purge_patterns',
                    'signature'     => 'repeated-global-purges',
                    'confidence'    => 'low',
                    'count'         => count( $known_timestamps ),
                    'likely_source' => 'manual-or-automated-flush',
                    'affected_urls' => array( home_url( '/' ) ),
                    'evidence_samples' => array(
                        array( 'timestamps' => array_values( $known_timestamps ) ),
                    ),
                )
            ),
        );
    }
}

/**
 * Engine service for single-pass detector execution.
 */
class PCM_Cache_Buster_Detector_Engine {
    /** @var PCM_Cache_Buster_Detector_Registry */
    protected $registry;

    /** @var PCM_Cache_Buster_Snapshot_Provider */
    protected $snapshot_provider;

    /**
     * @param PCM_Cache_Buster_Detector_Registry|null $registry Registry dependency.
     * @param PCM_Cache_Buster_Snapshot_Provider|null $snapshot_provider Snapshot provider dependency.
     */
    public function __construct( $registry = null, $snapshot_provider = null ) {
        $this->registry          = $registry ? $registry : new PCM_Cache_Buster_Detector_Registry();
        $this->snapshot_provider = $snapshot_provider ? $snapshot_provider : new PCM_Cache_Buster_Snapshot_Provider();

        $this->registry->register( new PCM_Cache_Buster_Cookie_Detector() );
        $this->registry->register( new PCM_Cache_Buster_Query_Detector() );
        $this->registry->register( new PCM_Cache_Buster_Vary_Detector() );
        $this->registry->register( new PCM_Cache_Buster_No_Cache_Detector() );
        $this->registry->register( new PCM_Cache_Buster_Purge_Detector() );
    }

    /**
     * Execute detectors once using latest completed scan snapshot.
     *
     * @return array
     */
    public function detect_latest() {
        if ( ! pcm_cache_busters_is_enabled() ) {
            return array();
        }

        $snapshot = $this->snapshot_provider->get_latest_snapshot();
        $events   = $this->registry->run_all( $snapshot );

        $storage = new PCM_Cache_Buster_Event_Storage();
        $run_id  = isset( $snapshot['run']['id'] ) ? absint( $snapshot['run']['id'] ) : 0;
        $storage->persist_events( $events, $run_id );

        return $events;
    }
}

/**
 * Persistent storage for detected cache-buster events (A2.1).
 */
class PCM_Cache_Buster_Event_Storage {
    /** @var string */
    protected $key = 'pcm_cache_buster_events_v1';

    /** @var int */
    protected $max_rows = 4000;

    /**
     * Persist detector events with timestamp and run context.
     *
     * @param array $events Events.
     * @param int   $run_id Run ID.
     *
     * @return array
     */
    public function persist_events( $events, $run_id = 0 ) {
        $rows = $this->all();
        $now  = current_time( 'mysql', true );

        foreach ( (array) $events as $event ) {
            if ( ! is_array( $event ) ) {
                continue;
            }

            $rows[] = array(
                'event_id'         => 'cbe_' . wp_generate_uuid4(),
                'run_id'           => absint( $run_id ),
                'category'         => isset( $event['category'] ) ? sanitize_key( $event['category'] ) : 'unknown',
                'signature'        => isset( $event['signature'] ) ? sanitize_text_field( $event['signature'] ) : 'unknown',
                'confidence'       => isset( $event['confidence'] ) ? sanitize_key( $event['confidence'] ) : 'low',
                'count'            => isset( $event['count'] ) ? absint( $event['count'] ) : 0,
                'likely_source'    => isset( $event['likely_source'] ) ? sanitize_text_field( $event['likely_source'] ) : 'unknown',
                'affected_urls'    => isset( $event['affected_urls'] ) ? (array) $event['affected_urls'] : array(),
                'evidence_samples' => isset( $event['evidence_samples'] ) ? (array) $event['evidence_samples'] : array(),
                'detected_at'      => $now,
            );
        }

        $rows = array_slice( $rows, -1 * $this->max_rows );
        update_option( $this->key, $rows, false );

        return $rows;
    }

    /**
     * @return array
     */
    public function all() {
        $rows = get_option( $this->key, array() );

        return is_array( $rows ) ? $rows : array();
    }

    /**
     * @param string $range 24h|7d|30d
     *
     * @return array
     */
    public function query_by_range( $range = '7d' ) {
        $rows = $this->all();

        $days_by_range = array(
            '24h' => 1,
            '7d'  => 7,
            '30d' => 30,
        );

        $days      = isset( $days_by_range[ $range ] ) ? $days_by_range[ $range ] : 7;
        $cutoff_ts = time() - ( DAY_IN_SECONDS * $days );

        return array_values(
            array_filter(
                $rows,
                static function ( $row ) use ( $cutoff_ts ) {
                    $ts = isset( $row['detected_at'] ) ? strtotime( $row['detected_at'] ) : 0;

                    return $ts >= $cutoff_ts;
                }
            )
        );
    }
}

/**
 * Query service for leaderboard + trends (A2.2).
 */
class PCM_Cache_Buster_Insights_Service {
    /** @var PCM_Cache_Buster_Event_Storage */
    protected $storage;

    /**
     * @param PCM_Cache_Buster_Event_Storage|null $storage Storage.
     */
    public function __construct( $storage = null ) {
        $this->storage = $storage ? $storage : new PCM_Cache_Buster_Event_Storage();
    }

    /**
     * @param string $range Range.
     * @param int    $limit Limit.
     *
     * @return array
     */
    public function top_sources( $range = '7d', $limit = 10 ) {
        $rows = $this->storage->query_by_range( $range );
        $agg  = array();

        foreach ( $rows as $row ) {
            $key = ( isset( $row['category'] ) ? $row['category'] : 'unknown' ) . '|' . ( isset( $row['signature'] ) ? $row['signature'] : 'unknown' );
            if ( ! isset( $agg[ $key ] ) ) {
                $agg[ $key ] = array(
                    'category'      => isset( $row['category'] ) ? $row['category'] : 'unknown',
                    'signature'     => isset( $row['signature'] ) ? $row['signature'] : 'unknown',
                    'likely_source' => isset( $row['likely_source'] ) ? $row['likely_source'] : 'unknown',
                    'confidence'    => isset( $row['confidence'] ) ? $row['confidence'] : 'low',
                    'event_count'   => 0,
                    'incidence'     => 0,
                );
            }

            $agg[ $key ]['event_count'] += 1;
            $agg[ $key ]['incidence'] += isset( $row['count'] ) ? absint( $row['count'] ) : 0;
        }

        $items = array_values( $agg );
        usort(
            $items,
            static function ( $a, $b ) {
                if ( $a['incidence'] === $b['incidence'] ) {
                    return $b['event_count'] <=> $a['event_count'];
                }

                return $b['incidence'] <=> $a['incidence'];
            }
        );

        return array_slice( $items, 0, max( 1, min( 100, absint( $limit ) ) ) );
    }

    /**
     * @param string $range Range.
     *
     * @return array
     */
    public function trend_points( $range = '7d' ) {
        $rows = $this->storage->query_by_range( $range );
        $agg  = array();

        foreach ( $rows as $row ) {
            $bucket = isset( $row['detected_at'] ) ? gmdate( 'Y-m-d', strtotime( $row['detected_at'] ) ) : gmdate( 'Y-m-d' );
            if ( ! isset( $agg[ $bucket ] ) ) {
                $agg[ $bucket ] = 0;
            }
            $agg[ $bucket ] += isset( $row['count'] ) ? absint( $row['count'] ) : 0;
        }

        ksort( $agg );

        $points = array();
        foreach ( $agg as $day => $incidence ) {
            $points[] = array(
                'bucket_start' => $day . ' 00:00:00',
                'incidence'    => $incidence,
            );
        }

        return $points;
    }

    /**
     * @param string $range Range.
     *
     * @return int
     */
    public function total_incidence( $range = '7d' ) {
        $rows = $this->storage->query_by_range( $range );
        $sum  = 0;

        foreach ( $rows as $row ) {
            $sum += isset( $row['count'] ) ? absint( $row['count'] ) : 0;
        }

        return $sum;
    }
}

/**
 * Helper for reporting integration (A2.4).
 *
 * @param string $range Range key.
 *
 * @return int
 */
function pcm_cache_busters_get_total_incidence( $range = '7d' ) {
    if ( ! pcm_cache_busters_is_enabled() ) {
        return 0;
    }

    $engine = new PCM_Cache_Buster_Detector_Engine();
    $engine->detect_latest();

    $insights = new PCM_Cache_Buster_Insights_Service();

    return $insights->total_incidence( $range );
}

/**
 * AJAX endpoint: top cache-busting sources leaderboard.
 *
 * @return void
 */
function pcm_ajax_cache_busters_top_sources() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_view_diagnostics' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( function_exists( 'pcm_current_user_can' ) ) {
            $can = pcm_current_user_can( 'pcm_view_diagnostics' );
        } else {
            $can = current_user_can( 'manage_options' );
        }

        if ( ! $can ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $range   = isset( $_REQUEST['range'] ) ? sanitize_key( wp_unslash( $_REQUEST['range'] ) ) : '7d';
    $limit   = isset( $_REQUEST['limit'] ) ? absint( wp_unslash( $_REQUEST['limit'] ) ) : 10;
    $service = new PCM_Cache_Buster_Insights_Service();

    wp_send_json_success(
        array(
            'range'       => $range,
            'leaderboard' => $service->top_sources( $range, $limit ),
        )
    );
}
add_action( 'wp_ajax_pcm_cache_busters_top_sources', 'pcm_ajax_cache_busters_top_sources' );

/**
 * AJAX endpoint: cache-buster incidence trends.
 *
 * @return void
 */
function pcm_ajax_cache_busters_trends() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_view_diagnostics' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( function_exists( 'pcm_current_user_can' ) ) {
            $can = pcm_current_user_can( 'pcm_view_diagnostics' );
        } else {
            $can = current_user_can( 'manage_options' );
        }

        if ( ! $can ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $range   = isset( $_REQUEST['range'] ) ? sanitize_key( wp_unslash( $_REQUEST['range'] ) ) : '7d';
    $service = new PCM_Cache_Buster_Insights_Service();

    wp_send_json_success(
        array(
            'range'  => $range,
            'points' => $service->trend_points( $range ),
        )
    );
}
add_action( 'wp_ajax_pcm_cache_busters_trends', 'pcm_ajax_cache_busters_trends' );
