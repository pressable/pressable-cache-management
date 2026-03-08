<?php
/**
 * Object Cache + Memcached Intelligence (Pillar 3).
 *
 * Read-only diagnostics designed for WPCloud safety.
 *
 * @package PressableCacheManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Feature flag for object cache intelligence.
 *
 * @return bool
 */
function pcm_object_cache_intelligence_is_enabled() {
    $enabled = false;

    return (bool) apply_filters( 'pcm_enable_object_cache_intelligence', $enabled );
}

/**
 * Unified adapter contract.
 */
interface PCM_Object_Cache_Stats_Provider_Interface {
    /**
     * Return a normalized metrics payload.
     *
     * @return array
     */
    public function get_metrics();

    /**
     * Identifier for active provider.
     *
     * @return string
     */
    public function get_provider_key();
}

/**
 * Provider that attempts to read stats from the object cache drop-in.
 */
class PCM_Object_Cache_Dropin_Stats_Provider implements PCM_Object_Cache_Stats_Provider_Interface {
    /**
     * @return string
     */
    public function get_provider_key() {
        return 'dropin';
    }

    /**
     * @return array
     */
    public function get_metrics() {
        global $wp_object_cache;

        if ( ! is_object( $wp_object_cache ) ) {
            return array();
        }

        $hits   = property_exists( $wp_object_cache, 'cache_hits' ) ? absint( $wp_object_cache->cache_hits ) : null;
        $misses = property_exists( $wp_object_cache, 'cache_misses' ) ? absint( $wp_object_cache->cache_misses ) : null;

        $evictions = null;
        $bytes     = null;
        $limit     = null;

        if ( method_exists( $wp_object_cache, 'stats' ) ) {
            ob_start();
            $wp_object_cache->stats();
            $stats_text = (string) ob_get_clean();

            $parsed = $this->parse_dropin_stats_text( $stats_text );
            $hits   = null !== $parsed['hits'] ? $parsed['hits'] : $hits;
            $misses = null !== $parsed['misses'] ? $parsed['misses'] : $misses;
            $evictions = $parsed['evictions'];
            $bytes     = $parsed['bytes_used'];
            $limit     = $parsed['bytes_limit'];
        }

        return array(
            'provider'      => $this->get_provider_key(),
            'status'        => 'connected',
            'hits'          => $hits,
            'misses'        => $misses,
            'hit_ratio'     => pcm_calculate_hit_ratio( $hits, $misses ),
            'evictions'     => $evictions,
            'bytes_used'    => $bytes,
            'bytes_limit'   => $limit,
            'meta'          => array(),
        );
    }

    /**
     * Parse plain-text output from drop-in stats methods.
     *
     * @param string $stats_text Raw output.
     *
     * @return array
     */
    protected function parse_dropin_stats_text( $stats_text ) {
        $output = array(
            'hits'       => null,
            'misses'     => null,
            'evictions'  => null,
            'bytes_used' => null,
            'bytes_limit'=> null,
        );

        if ( '' === trim( $stats_text ) ) {
            return $output;
        }

        if ( preg_match( '/\bget_hits\D+(\d+)/i', $stats_text, $matches ) ) {
            $output['hits'] = absint( $matches[1] );
        }

        if ( preg_match( '/\bget_misses\D+(\d+)/i', $stats_text, $matches ) ) {
            $output['misses'] = absint( $matches[1] );
        }

        if ( preg_match( '/\bevictions\D+(\d+)/i', $stats_text, $matches ) ) {
            $output['evictions'] = absint( $matches[1] );
        }

        if ( preg_match( '/\bbytes\D+(\d+)/i', $stats_text, $matches ) ) {
            $output['bytes_used'] = absint( $matches[1] );
        }

        if ( preg_match( '/\blimit_maxbytes\D+(\d+)/i', $stats_text, $matches ) ) {
            $output['bytes_limit'] = absint( $matches[1] );
        }

        return $output;
    }
}

/**
 * Provider that uses PHP Memcached extension stats when available.
 */
class PCM_Object_Cache_Memcached_Extension_Stats_Provider implements PCM_Object_Cache_Stats_Provider_Interface {
    /**
     * @return string
     */
    public function get_provider_key() {
        return 'memcached_extension';
    }

    /**
     * @return array
     */
    public function get_metrics() {
        if ( ! class_exists( 'Memcached' ) ) {
            return array();
        }

        $memcached = new Memcached();
        $server_ok = false;

        if ( defined( 'WP_MEMCACHED_SERVERS' ) && is_array( WP_MEMCACHED_SERVERS ) ) {
            foreach ( WP_MEMCACHED_SERVERS as $server_group ) {
                if ( ! is_array( $server_group ) ) {
                    continue;
                }
                foreach ( $server_group as $server_def ) {
                    $parts = explode( ':', (string) $server_def );
                    $host  = isset( $parts[0] ) ? sanitize_text_field( $parts[0] ) : '';
                    $port  = isset( $parts[1] ) ? absint( $parts[1] ) : 11211;
                    if ( '' === $host ) {
                        continue;
                    }
                    $memcached->addServer( $host, $port );
                    $server_ok = true;
                }
            }
        }

        if ( ! $server_ok ) {
            return array();
        }

        $all_stats = $memcached->getStats();
        if ( ! is_array( $all_stats ) || empty( $all_stats ) ) {
            return array();
        }

        $hits       = 0;
        $misses     = 0;
        $evictions  = 0;
        $bytes_used = 0;
        $max_bytes  = 0;

        foreach ( $all_stats as $server_stats ) {
            if ( ! is_array( $server_stats ) ) {
                continue;
            }

            $hits       += isset( $server_stats['get_hits'] ) ? absint( $server_stats['get_hits'] ) : 0;
            $misses     += isset( $server_stats['get_misses'] ) ? absint( $server_stats['get_misses'] ) : 0;
            $evictions  += isset( $server_stats['evictions'] ) ? absint( $server_stats['evictions'] ) : 0;
            $bytes_used += isset( $server_stats['bytes'] ) ? absint( $server_stats['bytes'] ) : 0;
            $max_bytes  += isset( $server_stats['limit_maxbytes'] ) ? absint( $server_stats['limit_maxbytes'] ) : 0;
        }

        return array(
            'provider'      => $this->get_provider_key(),
            'status'        => 'connected',
            'hits'          => $hits,
            'misses'        => $misses,
            'hit_ratio'     => pcm_calculate_hit_ratio( $hits, $misses ),
            'evictions'     => $evictions,
            'bytes_used'    => $bytes_used,
            'bytes_limit'   => $max_bytes,
            'meta'          => array(),
        );
    }
}

/**
 * Fallback provider when metrics are unavailable.
 */
class PCM_Object_Cache_Null_Stats_Provider implements PCM_Object_Cache_Stats_Provider_Interface {
    /**
     * @return string
     */
    public function get_provider_key() {
        return 'none';
    }

    /**
     * @return array
     */
    public function get_metrics() {
        return array(
            'provider'      => $this->get_provider_key(),
            'status'        => 'offline',
            'hits'          => null,
            'misses'        => null,
            'hit_ratio'     => null,
            'evictions'     => null,
            'bytes_used'    => null,
            'bytes_limit'   => null,
            'meta'          => array(
                'reason' => 'stats_unavailable',
            ),
        );
    }
}

/**
 * Select best available provider.
 */
class PCM_Object_Cache_Stats_Provider_Resolver {
    /**
     * @return PCM_Object_Cache_Stats_Provider_Interface
     */
    public function resolve() {
        $providers = array(
            new PCM_Object_Cache_Dropin_Stats_Provider(),
            new PCM_Object_Cache_Memcached_Extension_Stats_Provider(),
        );

        foreach ( $providers as $provider ) {
            $metrics = $provider->get_metrics();
            if ( ! empty( $metrics ) ) {
                return $provider;
            }
        }

        return new PCM_Object_Cache_Null_Stats_Provider();
    }
}

/**
 * Evaluate health heuristics and generate recommendations.
 */
class PCM_Object_Cache_Health_Evaluator {
    /**
     * @param array $metrics Metrics payload.
     *
     * @return array
     */
    public function evaluate( $metrics ) {
        $health = 'connected';
        $recommendations = array();

        if ( empty( $metrics ) || 'offline' === $metrics['status'] ) {
            return array(
                'health' => 'offline',
                'recommendations' => array(
                    array(
                        'rule_id'  => 'memcache_unavailable',
                        'severity' => 'critical',
                        'message'  => 'Object cache statistics are unavailable. Verify object-cache drop-in and Memcached connectivity.',
                        'checklist'=> array(
                            'Confirm object-cache.php drop-in exists and loads without warnings.',
                            'Confirm Memcached service endpoint is reachable from WP runtime.',
                            'Re-check stats after infrastructure validation.',
                        ),
                    ),
                ),
            );
        }

        $hit_ratio = isset( $metrics['hit_ratio'] ) ? $metrics['hit_ratio'] : null;
        $evictions = isset( $metrics['evictions'] ) ? absint( $metrics['evictions'] ) : 0;

        if ( null !== $hit_ratio && $hit_ratio < 70 ) {
            $health = 'degraded';
            $recommendations[] = array(
                'rule_id'  => 'low_hit_ratio',
                'severity' => 'warning',
                'message'  => sprintf( 'Hit ratio is %s%% (below 70%% warning threshold). Investigate short TTLs and high key churn.', $hit_ratio ),
                'checklist'=> array(
                    'Review frequent cache flush triggers in plugin/theme workflow.',
                    'Verify cache key normalization for anonymous requests.',
                    'Measure hit ratio again after configuration changes.',
                ),
            );
        }

        if ( $evictions > 0 ) {
            $memory_pressure = pcm_calculate_memory_pressure( $metrics );

            if ( $evictions >= 100 || $memory_pressure >= 90 ) {
                $health = 'degraded';
                $recommendations[] = array(
                    'rule_id'  => 'high_evictions_or_pressure',
                    'severity' => 'critical',
                    'message'  => sprintf( 'Evictions (%d) and/or memory pressure (%s%%) indicate cache churn and likely reduced effectiveness.', $evictions, $memory_pressure ),
                    'checklist'=> array(
                        'Check object cache memory allocation and slab usage.',
                        'Reduce oversized or low-value cache entries where possible.',
                        'Verify lower eviction trend after adjustments.',
                    ),
                );
            }
        }

        return array(
            'health'          => $health,
            'recommendations' => $recommendations,
        );
    }
}

/**
 * Facade service used by admin UI/scheduler in future slices.
 */
class PCM_Object_Cache_Intelligence_Service {
    /** @var PCM_Object_Cache_Stats_Provider_Resolver */
    protected $resolver;

    /** @var PCM_Object_Cache_Health_Evaluator */
    protected $evaluator;

    /**
     * @param PCM_Object_Cache_Stats_Provider_Resolver|null $resolver Resolver dependency.
     * @param PCM_Object_Cache_Health_Evaluator|null        $evaluator Evaluator dependency.
     */
    public function __construct( $resolver = null, $evaluator = null ) {
        $this->resolver  = $resolver ? $resolver : new PCM_Object_Cache_Stats_Provider_Resolver();
        $this->evaluator = $evaluator ? $evaluator : new PCM_Object_Cache_Health_Evaluator();
    }

    /**
     * Collect one normalized intelligence payload.
     *
     * @return array
     */
    public function collect_snapshot() {
        if ( ! pcm_object_cache_intelligence_is_enabled() ) {
            return array();
        }

        $provider = $this->resolver->resolve();
        $metrics  = $provider->get_metrics();
        $derived  = $this->evaluator->evaluate( $metrics );

        return array(
            'taken_at'         => current_time( 'mysql', true ),
            'provider'         => $provider->get_provider_key(),
            'status'           => isset( $metrics['status'] ) ? $metrics['status'] : 'offline',
            'health'           => $derived['health'],
            'hit_ratio'        => isset( $metrics['hit_ratio'] ) ? $metrics['hit_ratio'] : null,
            'evictions'        => isset( $metrics['evictions'] ) ? $metrics['evictions'] : null,
            'bytes_used'       => isset( $metrics['bytes_used'] ) ? $metrics['bytes_used'] : null,
            'bytes_limit'      => isset( $metrics['bytes_limit'] ) ? $metrics['bytes_limit'] : null,
            'recommendations'  => $derived['recommendations'],
            'meta'             => isset( $metrics['meta'] ) ? $metrics['meta'] : array(),
        );
    }
}

/**
 * @param int|null $hits Hits.
 * @param int|null $misses Misses.
 *
 * @return float|null
 */
function pcm_calculate_hit_ratio( $hits, $misses ) {
    if ( null === $hits || null === $misses ) {
        return null;
    }

    $total = absint( $hits ) + absint( $misses );
    if ( 0 === $total ) {
        return null;
    }

    return round( ( absint( $hits ) / $total ) * 100, 2 );
}

/**
 * @param array $metrics Metrics payload.
 *
 * @return float
 */
function pcm_calculate_memory_pressure( $metrics ) {
    $used  = isset( $metrics['bytes_used'] ) ? absint( $metrics['bytes_used'] ) : 0;
    $limit = isset( $metrics['bytes_limit'] ) ? absint( $metrics['bytes_limit'] ) : 0;

    if ( $limit <= 0 ) {
        return 0.0;
    }

    return round( ( $used / $limit ) * 100, 2 );
}
