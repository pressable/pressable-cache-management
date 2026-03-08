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

        return $this->registry->run_all( $snapshot );
    }
}
