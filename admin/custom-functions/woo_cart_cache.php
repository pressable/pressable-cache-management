<?php
/**
 * Pressable Cache Management - WooCommerce Cart Caching (WP Cloud closed beta)
 *
 * Cart cookies normally make a request bypass Edge Cache. With cart caching on, shoppers
 * with items in their cart get the cached (empty-cart) page from the edge and WooCommerce's
 * JavaScript fills in their cart. Cart and checkout are never cached, logged-in users still
 * bypass, and responses to cart-cookie requests are never written to the cache.
 * Docs: https://wp.cloud/docs/performance/cache/woo-cart-caching/
 *
 * API (through pcm_edge_feature_request, see cache_preload.php):
 *   GET  /edge-cache/<site-id>/woo_cart_cache/<domain>             -> data.enabled
 *   POST /edge-cache/<site-id>/woo_cart_cache/<domain> enable=1|0  -> data.enabled
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ── AJAX: status (no POST enable) or toggle (POST enable=1|0) ────────────────
function pcm_ajax_woo_cart_cache() {
    check_ajax_referer( 'pcm_batcache_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
    }

    $enable = isset( $_POST['enable'] ) ? '1' === $_POST['enable'] : null;
    $result = pcm_edge_feature_request( 'woo_cart_cache', $enable );

    if ( null !== $enable ) {
        // Confirm a change with a read, also after an error: it may have been applied anyway.
        $status = pcm_edge_feature_request( 'woo_cart_cache' );
        if ( ! empty( $status['success'] ) && ( ! empty( $result['success'] ) || ! empty( $status['data']->enabled ) === $enable ) ) {
            $result = $status;
        }
    }

    if ( empty( $result['success'] ) ) {
        wp_send_json_error( array( 'message' => $result['error'] ) );
    }
    wp_send_json_success( array( 'enabled' => ! empty( $result['data']->enabled ) ) );
}
add_action( 'wp_ajax_pcm_woo_cart_cache', 'pcm_ajax_woo_cart_cache' );
