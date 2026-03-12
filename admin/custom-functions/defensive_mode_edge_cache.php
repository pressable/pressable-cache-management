<?php
/**
 * Pressable Cache Management - Edge Cache Defensive Mode
 *
 * Mirrors the exact pattern used in edge_cache_admin_action_handler():
 *   query_ec_backend( $endpoint, array( 'body' => $data ) )
 *
 * Enable:  POST to ddos_until with body timestamp = time() + duration_seconds
 * Disable: POST to ddos_until with body timestamp = 0
 * Status:  get_ec_ddos_until() — returns the Unix timestamp or 0 if off
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── Duration map: slug => [ label, seconds ] ────────────────────────────────
function pcm_defensive_mode_durations() {
    return [
        '30-minutes' => [ 'label' => '30 minutes', 'seconds' => 30 * MINUTE_IN_SECONDS ],
        '45-minutes' => [ 'label' => '45 minutes', 'seconds' => 45 * MINUTE_IN_SECONDS ],
        '1-hour'     => [ 'label' => '1 hour',     'seconds' => HOUR_IN_SECONDS ],
        '2-hours'    => [ 'label' => '2 hours',    'seconds' => 2  * HOUR_IN_SECONDS ],
        '3-hours'    => [ 'label' => '3 hours',    'seconds' => 3  * HOUR_IN_SECONDS ],
        '4-hours'    => [ 'label' => '4 hours',    'seconds' => 4  * HOUR_IN_SECONDS ],
        '5-hours'    => [ 'label' => '5 hours',    'seconds' => 5  * HOUR_IN_SECONDS ],
        '6-hours'    => [ 'label' => '6 hours',    'seconds' => 6  * HOUR_IN_SECONDS ],
        '7-hours'    => [ 'label' => '7 hours',    'seconds' => 7  * HOUR_IN_SECONDS ],
        '8-hours'    => [ 'label' => '8 hours',    'seconds' => 8  * HOUR_IN_SECONDS ],
        '9-hours'    => [ 'label' => '9 hours',    'seconds' => 9  * HOUR_IN_SECONDS ],
        '10-hours'   => [ 'label' => '10 hours',   'seconds' => 10 * HOUR_IN_SECONDS ],
        '11-hours'   => [ 'label' => '11 hours',   'seconds' => 11 * HOUR_IN_SECONDS ],
        '12-hours'   => [ 'label' => '12 hours',   'seconds' => 12 * HOUR_IN_SECONDS ],
        '13-hours'   => [ 'label' => '13 hours',   'seconds' => 13 * HOUR_IN_SECONDS ],
        '14-hours'   => [ 'label' => '14 hours',   'seconds' => 14 * HOUR_IN_SECONDS ],
        '15-hours'   => [ 'label' => '15 hours',   'seconds' => 15 * HOUR_IN_SECONDS ],
        '16-hours'   => [ 'label' => '16 hours',   'seconds' => 16 * HOUR_IN_SECONDS ],
        '17-hours'   => [ 'label' => '17 hours',   'seconds' => 17 * HOUR_IN_SECONDS ],
        '18-hours'   => [ 'label' => '18 hours',   'seconds' => 18 * HOUR_IN_SECONDS ],
        '19-hours'   => [ 'label' => '19 hours',   'seconds' => 19 * HOUR_IN_SECONDS ],
        '20-hours'   => [ 'label' => '20 hours',   'seconds' => 20 * HOUR_IN_SECONDS ],
        '21-hours'   => [ 'label' => '21 hours',   'seconds' => 21 * HOUR_IN_SECONDS ],
        '22-hours'   => [ 'label' => '22 hours',   'seconds' => 22 * HOUR_IN_SECONDS ],
        '23-hours'   => [ 'label' => '23 hours',   'seconds' => 23 * HOUR_IN_SECONDS ],
        '1-day'      => [ 'label' => '1 day',      'seconds' => DAY_IN_SECONDS ],
        '2-days'     => [ 'label' => '2 days',     'seconds' => 2  * DAY_IN_SECONDS ],
        '3-days'     => [ 'label' => '3 days',     'seconds' => 3  * DAY_IN_SECONDS ],
        '4-days'     => [ 'label' => '4 days',     'seconds' => 4  * DAY_IN_SECONDS ],
        '5-days'     => [ 'label' => '5 days',     'seconds' => 5  * DAY_IN_SECONDS ],
        '6-days'     => [ 'label' => '6 days',     'seconds' => 6  * DAY_IN_SECONDS ],
        '7-days'     => [ 'label' => '7 days',     'seconds' => 7  * DAY_IN_SECONDS ],
    ];
}

// ─── Enable Defensive Mode ────────────────────────────────────────────────────
function pcm_pressable_enable_defensive_mode() {
    if ( ! isset( $_POST['enable_defensive_mode_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['enable_defensive_mode_nonce'] ) ), 'enable_defensive_mode_nonce' ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $durations = pcm_defensive_mode_durations();
    $slug      = isset( $_POST['defensive_mode_duration'] )
        ? sanitize_text_field( wp_unslash( $_POST['defensive_mode_duration'] ) )
        : '30-minutes';

    if ( ! array_key_exists( $slug, $durations ) ) {
        $slug = '30-minutes';
    }

    if ( ! class_exists( 'Edge_Cache_Plugin' ) ) {
        add_action( 'admin_notices', function() {
            if ( function_exists( 'pcm_branded_notice' ) ) {
                pcm_branded_notice( esc_html__( 'Error: Edge Cache Plugin is not active.', 'pressable_cache_management' ), '#dd3a03' );
            }
        } );
        return;
    }

    $expires_at = time() + $durations[ $slug ]['seconds'];

    $edge_cache = Edge_Cache_Plugin::get_instance();
    $result     = $edge_cache->query_ec_backend( 'ddos_until', array(
        'body' => array(
            'timestamp' => $expires_at,
            'wp_action' => 'manual_dashboard_set',
        ),
    ) );

    if ( false === $result['success'] ) {
        $err = ! empty( $result['error'] ) ? $result['error'] : esc_html__( 'Unknown error enabling Defensive Mode.', 'pressable_cache_management' );
        add_action( 'admin_notices', function() use ( $err ) {
            if ( function_exists( 'pcm_branded_notice' ) ) {
                pcm_branded_notice( $err, '#dd3a03' );
            }
        } );
    } else {
        update_option( 'edge-cache-defensive-mode-active',     'yes' );
        update_option( 'edge-cache-defensive-mode-slug',       $slug );
        update_option( 'edge-cache-defensive-mode-expires-at', $expires_at );
        update_option( 'edge-cache-defensive-mode-set-at',     gmdate( 'j M Y, g:ia' ) . ' UTC' );
        delete_transient( 'pcm_ec_status_cache' );
        do_action( 'pcm_after_defensive_mode_change' );

        $label = $durations[ $slug ]['label'];
        add_action( 'admin_notices', function() use ( $label ) {
            if ( function_exists( 'pcm_branded_notice' ) ) {
                pcm_branded_notice(
                    sprintf( esc_html__( 'Defensive Mode enabled for %s.', 'pressable_cache_management' ), $label ),
                    '#03fcc2'
                );
            }
        } );
    }
}
add_action( 'init', 'pcm_pressable_enable_defensive_mode' );

// ─── Disable Defensive Mode ───────────────────────────────────────────────────
function pcm_pressable_disable_defensive_mode() {
    if ( ! isset( $_POST['disable_defensive_mode_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['disable_defensive_mode_nonce'] ) ), 'disable_defensive_mode_nonce' ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    if ( ! class_exists( 'Edge_Cache_Plugin' ) ) {
        add_action( 'admin_notices', function() {
            if ( function_exists( 'pcm_branded_notice' ) ) {
                pcm_branded_notice( esc_html__( 'Error: Edge Cache Plugin is not active.', 'pressable_cache_management' ), '#dd3a03' );
            }
        } );
        return;
    }

    $edge_cache = Edge_Cache_Plugin::get_instance();
    $result     = $edge_cache->query_ec_backend( 'ddos_until', array(
        'body' => array(
            'timestamp' => 0,
            'wp_action' => 'manual_dashboard_set',
        ),
    ) );

    if ( false === $result['success'] ) {
        $err = ! empty( $result['error'] ) ? $result['error'] : esc_html__( 'Unknown error disabling Defensive Mode.', 'pressable_cache_management' );
        add_action( 'admin_notices', function() use ( $err ) {
            if ( function_exists( 'pcm_branded_notice' ) ) {
                pcm_branded_notice( $err, '#dd3a03' );
            }
        } );
    } else {
        update_option( 'edge-cache-defensive-mode-active',     'no' );
        update_option( 'edge-cache-defensive-mode-slug',       '' );
        update_option( 'edge-cache-defensive-mode-expires-at', 0 );
        update_option( 'edge-cache-defensive-mode-set-at',     '' );
        delete_transient( 'pcm_ec_status_cache' );
        do_action( 'pcm_after_defensive_mode_change' );

        add_action( 'admin_notices', function() {
            if ( function_exists( 'pcm_branded_notice' ) ) {
                pcm_branded_notice( esc_html__( 'Defensive Mode disabled.', 'pressable_cache_management' ), '#03fcc2' );
            }
        } );
    }
}
add_action( 'init', 'pcm_pressable_disable_defensive_mode' );

// ─── AJAX: check defensive mode status from server ───────────────────────────
// Uses get_ec_ddos_until() which calls query_ec_backend( 'ddos_until' ) as a
// GET (no body args) and returns the ddos_until Unix timestamp, or 0 if off.
function pcm_ajax_check_defensive_mode_status() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized' ], 403 );
        return;
    }

    if ( ! class_exists( 'Edge_Cache_Plugin' ) ) {
        wp_send_json_error( [ 'message' => 'Edge Cache Plugin not available.' ] );
        return;
    }

    $edge_cache  = Edge_Cache_Plugin::get_instance();
    $ddos_until  = $edge_cache->get_ec_ddos_until(); // returns int timestamp or EC_ERROR (-1)

    // EC_ERROR means the API call failed
    if ( Edge_Cache_Plugin::EC_ERROR === $ddos_until ) {
        wp_send_json_error( [ 'message' => 'Could not retrieve Defensive Mode status from server.' ] );
        return;
    }

    $is_defensive = $ddos_until > time();

    if ( $is_defensive ) {
        // Sync local flag if mode was activated externally (WP-CLI, direct API)
        update_option( 'edge-cache-defensive-mode-active',     'yes' );
        update_option( 'edge-cache-defensive-mode-expires-at', $ddos_until );

        $set_at      = get_option( 'edge-cache-defensive-mode-set-at', '' );
        $expires_str = gmdate( 'j M Y, g:ia', $ddos_until ) . ' UTC';

        wp_send_json_success( [
            'defensive_active' => true,
            'set_at'           => $set_at,
            'expires_at'       => $expires_str,
        ] );

    } else {
        // Server says off — sync local options
        update_option( 'edge-cache-defensive-mode-active',     'no' );
        update_option( 'edge-cache-defensive-mode-slug',       '' );
        update_option( 'edge-cache-defensive-mode-expires-at', 0 );
        update_option( 'edge-cache-defensive-mode-set-at',     '' );

        wp_send_json_success( [
            'defensive_active' => false,
            'set_at'           => '',
            'expires_at'       => '',
        ] );
    }
}
add_action( 'wp_ajax_pcm_check_defensive_mode_status', 'pcm_ajax_check_defensive_mode_status' );
