<?php
/**
 * Pressable Cache Management - Edge Cache Preload (WP Cloud closed beta)
 *
 * The preloader crawls the site from the front page, stores responses in a
 * preload cache at the origin data center, and refreshes pages after purges.
 * Docs: https://wp.cloud/docs/performance/cache/edge-cache-preload/
 *
 * Both calls go through the edge-cache mu-plugin's query_ec_backend(), which
 * signs requests with the site's own Atomic API key and builds the URL
 *   /edge-cache/<site-id>/preload/<domain>
 * the same way Defensive Mode uses the ddos_until endpoint:
 *   GET  (no args)                   -> current status
 *   POST body enable=1|0             -> turn preloading on or off
 * The GET response data carries: enabled, state (pending|running|finished|error),
 * urls_found, urls_preloaded, searching, error, finished_time.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GET the preload status, or POST a new enabled value when $enable is given.
 * Returns array( 'success' => bool, 'data' => object ) or array( 'success' => false, 'error' => string ).
 */
function pcm_cache_preload_request( $enable = null ) {
    if ( ! class_exists( 'Edge_Cache_Plugin' ) ) {
        return array( 'success' => false, 'error' => __( 'Edge Cache dependency is not available.', 'pressable_cache_management' ) );
    }
    $args = array();
    if ( null !== $enable ) {
        $args = array( 'body' => array( 'enable' => $enable ? 1 : 0, 'wp_action' => 'manual_dashboard_set' ) );
    }
    $result = Edge_Cache_Plugin::get_instance()->query_ec_backend( 'preload', $args );
    if ( ! is_array( $result ) || empty( $result['success'] ) ) {
        $error = is_array( $result ) && ! empty( $result['error'] ) ? $result['error'] : __( 'Unknown error from the Edge Cache API.', 'pressable_cache_management' );
        return array( 'success' => false, 'error' => $error );
    }
    return $result;
}

// Shape the API data for the status panel.
function pcm_cache_preload_payload( $data ) {
    $data   = (object) $data;
    $state  = isset( $data->state ) ? (string) $data->state : '';
    $labels = array(
        'pending'  => __( 'Crawl scheduled, waiting to start', 'pressable_cache_management' ),
        'running'  => __( 'Crawling and preloading pages', 'pressable_cache_management' ),
        'finished' => __( 'Full-site preload finished; pages are refreshed after purges', 'pressable_cache_management' ),
        'error'    => __( 'Preload stopped with an error', 'pressable_cache_management' ),
    );
    $finished = '';
    if ( ! empty( $data->finished_time ) ) {
        $ts       = is_numeric( $data->finished_time ) ? (int) $data->finished_time : strtotime( (string) $data->finished_time );
        $finished = $ts ? gmdate( 'j M Y, g:ia', $ts ) . ' UTC' : (string) $data->finished_time;
    }
    return array(
        'enabled'        => ! empty( $data->enabled ),
        'state'          => $state,
        'state_label'    => isset( $labels[ $state ] ) ? $labels[ $state ] : $state,
        'urls_found'     => isset( $data->urls_found ) ? (int) $data->urls_found : null,
        'urls_preloaded' => isset( $data->urls_preloaded ) ? (int) $data->urls_preloaded : null,
        'searching'      => ! empty( $data->searching ),
        'error'          => isset( $data->error ) ? (string) $data->error : '',
        'finished_time'  => $finished,
    );
}

// ── AJAX: status ─────────────────────────────────────────────────────────────
function pcm_ajax_cache_preload_status() {
    check_ajax_referer( 'pcm_batcache_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
    }
    $result = pcm_cache_preload_request();
    if ( empty( $result['success'] ) ) {
        wp_send_json_error( array( 'message' => $result['error'] ) );
    }
    wp_send_json_success( pcm_cache_preload_payload( $result['data'] ) );
}
add_action( 'wp_ajax_pcm_cache_preload_status', 'pcm_ajax_cache_preload_status' );

// ── AJAX: enable / disable ───────────────────────────────────────────────────
function pcm_ajax_cache_preload_toggle() {
    check_ajax_referer( 'pcm_batcache_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
    }
    $enable = ! empty( $_POST['enable'] ) && '0' !== $_POST['enable'];
    $result = pcm_cache_preload_request( $enable );
    if ( empty( $result['success'] ) ) {
        wp_send_json_error( array( 'message' => $result['error'] ) );
    }
    // The docs say to confirm a settings update with a GET; it also gives the crawl state.
    $status = pcm_cache_preload_request();
    $data   = ! empty( $status['success'] ) ? $status['data'] : $result['data'];
    wp_send_json_success( pcm_cache_preload_payload( $data ) );
}
add_action( 'wp_ajax_pcm_cache_preload_toggle', 'pcm_ajax_cache_preload_toggle' );
