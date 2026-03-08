<?php
/**
 * PHP OPcache Awareness (Pillar 4).
 *
 * @package PressableCacheManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Feature flag for OPcache diagnostics.
 *
 * Default aligns with spec rollout: enabled for admins only.
 *
 * @return bool
 */
function pcm_opcache_awareness_is_enabled() {
    $enabled = current_user_can( 'manage_options' );

    return (bool) apply_filters( 'pcm_enable_opcache_awareness', $enabled );
}

/**
 * Collector service for OPcache runtime + ini snapshots.
 */
class PCM_OPcache_Collector_Service {
    /**
     * @return array
     */
    public function collect() {
        if ( ! pcm_opcache_awareness_is_enabled() ) {
            return array();
        }

        $status = $this->safe_get_status();
        $ini    = $this->collect_ini();

        if ( empty( $status ) ) {
            return array(
                'taken_at'        => current_time( 'mysql', true ),
                'enabled'         => false,
                'health'          => 'disabled',
                'memory'          => array(),
                'statistics'      => array(),
                'ini'             => $ini,
                'recommendations' => array(
                    array(
                        'rule_id'   => 'opcache_unavailable',
                        'severity'  => 'warning',
                        'message'   => 'OPcache status is unavailable. Confirm OPcache is enabled in this PHP runtime.',
                        'checklist' => array(
                            'Verify opcache.enable is set to 1.',
                            'Confirm opcache extension is loaded for the web SAPI.',
                            'Re-check OPcache diagnostics after PHP runtime changes.',
                        ),
                    ),
                ),
            );
        }

        $snapshot        = $this->normalize_status( $status );
        $snapshot['ini'] = $ini;

        return $snapshot;
    }

    /**
     * @return array
     */
    protected function safe_get_status() {
        if ( ! function_exists( 'opcache_get_status' ) ) {
            return array();
        }

        $result = @opcache_get_status( false );

        if ( ! is_array( $result ) ) {
            return array();
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function collect_ini() {
        $keys = array(
            'opcache.enable',
            'opcache.memory_consumption',
            'opcache.max_accelerated_files',
            'opcache.revalidate_freq',
            'opcache.validate_timestamps',
            'opcache.max_wasted_percentage',
        );

        $output = array();

        foreach ( $keys as $key ) {
            $value = ini_get( $key );
            if ( false === $value ) {
                continue;
            }

            $output[ $key ] = sanitize_text_field( (string) $value );
        }

        return $output;
    }

    /**
     * @param array $raw_status Raw OPcache payload.
     *
     * @return array
     */
    protected function normalize_status( $raw_status ) {
        $opcache_enabled = ! empty( $raw_status['opcache_enabled'] );
        $memory_usage    = isset( $raw_status['memory_usage'] ) && is_array( $raw_status['memory_usage'] ) ? $raw_status['memory_usage'] : array();
        $statistics      = isset( $raw_status['opcache_statistics'] ) && is_array( $raw_status['opcache_statistics'] ) ? $raw_status['opcache_statistics'] : array();

        $used_memory   = isset( $memory_usage['used_memory'] ) ? absint( $memory_usage['used_memory'] ) : 0;
        $free_memory   = isset( $memory_usage['free_memory'] ) ? absint( $memory_usage['free_memory'] ) : 0;
        $wasted_memory = isset( $memory_usage['wasted_memory'] ) ? absint( $memory_usage['wasted_memory'] ) : 0;

        $restarts = array(
            'oom_restarts'       => isset( $statistics['oom_restarts'] ) ? absint( $statistics['oom_restarts'] ) : 0,
            'hash_restarts'      => isset( $statistics['hash_restarts'] ) ? absint( $statistics['hash_restarts'] ) : 0,
            'manual_restarts'    => isset( $statistics['manual_restarts'] ) ? absint( $statistics['manual_restarts'] ) : 0,
        );

        $snapshot = array(
            'taken_at'   => current_time( 'mysql', true ),
            'enabled'    => $opcache_enabled,
            'memory'     => array(
                'used_memory'         => $used_memory,
                'free_memory'         => $free_memory,
                'wasted_memory'       => $wasted_memory,
                'free_memory_percent' => pcm_opcache_percent( $free_memory, $used_memory + $free_memory + $wasted_memory ),
                'wasted_percent'      => pcm_opcache_percent( $wasted_memory, $used_memory + $free_memory + $wasted_memory ),
            ),
            'statistics' => array(
                'num_cached_scripts' => isset( $statistics['num_cached_scripts'] ) ? absint( $statistics['num_cached_scripts'] ) : 0,
                'max_cached_keys'    => isset( $statistics['max_cached_keys'] ) ? absint( $statistics['max_cached_keys'] ) : 0,
                'opcache_hit_rate'   => isset( $statistics['opcache_hit_rate'] ) ? round( (float) $statistics['opcache_hit_rate'], 2 ) : null,
                'restarts'           => $restarts,
                'restart_total'      => array_sum( $restarts ),
            ),
        );

        $recommendation_engine = new PCM_OPcache_Recommendation_Engine();
        $evaluated             = $recommendation_engine->evaluate( $snapshot );

        return array_merge( $snapshot, $evaluated );
    }
}

/**
 * Recommendation engine for OPcache snapshots.
 */
class PCM_OPcache_Recommendation_Engine {
    /**
     * @param array $snapshot Normalized snapshot.
     *
     * @return array
     */
    public function evaluate( $snapshot ) {
        $health          = 'healthy';
        $recommendations = array();

        $free_percent   = isset( $snapshot['memory']['free_memory_percent'] ) ? (float) $snapshot['memory']['free_memory_percent'] : 0;
        $wasted_percent = isset( $snapshot['memory']['wasted_percent'] ) ? (float) $snapshot['memory']['wasted_percent'] : 0;
        $restart_total  = isset( $snapshot['statistics']['restart_total'] ) ? absint( $snapshot['statistics']['restart_total'] ) : 0;

        if ( $free_percent < 10 ) {
            $health = 'warning';
            $recommendations[] = array(
                'rule_id'   => 'low_free_memory',
                'severity'  => 'warning',
                'message'   => sprintf( 'OPcache free memory is %s%% (<10%%). Consider increasing opcache.memory_consumption.', $free_percent ),
                'checklist' => array(
                    'Increase opcache.memory_consumption incrementally.',
                    'Monitor free memory and restart behavior after change.',
                    'Confirm improved hit rate after tuning.',
                ),
            );
        }

        if ( $wasted_percent > 10 ) {
            $health = 'warning';
            $recommendations[] = array(
                'rule_id'   => 'high_wasted_memory',
                'severity'  => 'warning',
                'message'   => sprintf( 'OPcache wasted memory is %s%% (>10%%). Investigate invalidation churn and deployment frequency.', $wasted_percent ),
                'checklist' => array(
                    'Audit deployment cadence and cache invalidation patterns.',
                    'Review timestamp validation and revalidate frequency settings.',
                    'Verify wasted memory trend drops after adjustments.',
                ),
            );
        }

        if ( $restart_total >= 3 ) {
            $health = 'degraded';
            $recommendations[] = array(
                'rule_id'   => 'frequent_restarts',
                'severity'  => 'critical',
                'message'   => sprintf( 'OPcache restart counters are elevated (%d). Check memory sizing and invalidation storms.', $restart_total ),
                'checklist' => array(
                    'Inspect deployment/reload patterns in the last 24h.',
                    'Tune memory and max file/key settings as needed.',
                    'Re-check restart counters after mitigation.',
                ),
            );
        }

        $recommendations[] = array(
            'rule_id'   => 'timestamp_validation_note',
            'severity'  => 'info',
            'message'   => 'Timestamp validation improves code freshness but may reduce cache efficiency. Tune opcache.validate_timestamps and opcache.revalidate_freq for your deployment model.',
            'checklist' => array(
                'Document current deployment workflow expectations.',
                'Adjust validation settings in small increments.',
                'Verify both correctness and hit-rate trends post-change.',
            ),
        );

        return array(
            'health'          => $health,
            'recommendations' => $recommendations,
        );
    }
}

/**
 * @param int|float $value Numerator.
 * @param int|float $total Denominator.
 *
 * @return float
 */
function pcm_opcache_percent( $value, $total ) {
    $value = (float) $value;
    $total = (float) $total;

    if ( $total <= 0 ) {
        return 0.0;
    }

    return round( ( $value / $total ) * 100, 2 );
}
