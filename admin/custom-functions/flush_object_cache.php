<?php
/**
 * Pressable Cache Management - Flush Object Cache + Page Cache
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( isset( $_POST['flush_object_cache_nonce'] ) ) {

    function pressable_cache_button() {
        if ( ! wp_verify_nonce(
            sanitize_text_field( wp_unslash( $_POST['flush_object_cache_nonce'] ) ),
            'flush_object_cache_nonce'
        ) || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Flush WP Object Cache (Redis / Memcached)
        wp_cache_flush();

        // Flush Batcache page cache if available
        if ( function_exists( 'batcache_clear_cache' ) ) {
            batcache_clear_cache();
        }

        // WP Super Cache
        if ( function_exists( 'wp_cache_clear_cache' ) ) {
            wp_cache_clear_cache();
        }

        // W3 Total Cache
        if ( function_exists( 'w3tc_flush_all' ) ) {
            w3tc_flush_all();
        }

        // WP Rocket
        if ( function_exists( 'rocket_clean_domain' ) ) {
            rocket_clean_domain();
        }

        // Custom hook for other integrations
        do_action( 'pcm_flush_all_cache' );

        // Clear the cached Batcache status so the badge refreshes on next page load
        do_action( 'pcm_after_object_cache_flush' );
        delete_transient( 'pcm_batcache_status' );
    }
    add_action( 'wp_before_admin_bar_render', 'pressable_cache_button', 999 );

    // Branded success notice - only show ONE (remove WP default)
    function flush_cache_notice__success() {
        $pcm_nid = 'pcm-obj-notice-' . substr( md5( microtime() ), 0, 8 );
        $wrap = 'display:flex;align-items:center;justify-content:space-between;gap:12px;'
              . 'border-left:4px solid #03fcc2;background:#fff;border-radius:0 8px 8px 0;'
              . 'padding:14px 18px;box-shadow:0 2px 8px rgba(4,0,36,.07);'
              . 'margin:10px 20px 10px 0;font-family:sans-serif;';
        $btn  = 'background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;line-height:1;padding:0;';
        echo '<div id="' . $pcm_nid . '" style="' . $wrap . '">';
        echo '<p style="margin:0;font-size:13px;color:#040024;">'
           . esc_html__( 'Object Cache Flushed Successfully.', 'pressable_cache_management' )
           . '</p>';
        echo '<button type="button" onclick="document.getElementById(\'' . $pcm_nid . '\').remove();" style="' . $btn . '">&#x2297;</button>';
        echo '</div>';
    }
    add_action( 'admin_notices', 'flush_cache_notice__success' );

    update_option( 'flush-obj-cache-time-stamp', gmdate( 'j M Y, g:ia' ) . ' UTC' );
}
