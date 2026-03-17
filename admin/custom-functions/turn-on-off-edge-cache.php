<?php
/**
 * Pressable Cache Management - Turn On/Off Edge Cache
 * Based directly on the official repo's turn-on-off-edge-cache.php
 * with branded admin notices applied.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── Shared branded notice helper (defined once here) ──────────────────────
if ( ! function_exists( 'pcm_branded_notice' ) ) {
    function pcm_branded_notice( $message, $border_color = '#03fcc2', $is_html = false ) {
        $id   = 'pcm-notice-' . substr( md5( $message . $border_color . microtime() ), 0, 8 );
        $wrap = 'display:inline-flex;align-items:flex-start;justify-content:space-between;gap:16px;'
              . 'border-left:4px solid ' . esc_attr( $border_color ) . ';background:#fff;'
              . 'border-radius:0 8px 8px 0;padding:14px 18px;'
              . 'box-shadow:0 2px 8px rgba(4,0,36,.07);margin:10px 20px 10px 0;font-family:sans-serif;'
              . 'min-width:260px;max-width:520px;';
        $btn  = 'background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;'
              . 'line-height:1;padding:0;flex-shrink:0;margin-top:2px;';
        echo '<div id="' . esc_attr( $id ) . '" style="' . $wrap . '">';
        echo '<div style="flex:1;">';
        if ( $is_html ) {
            echo $message; // caller already escaped
        } else {
            echo '<p style="margin:0;font-size:13px;color:#040024;">' . esc_html( $message ) . '</p>';
        }
        echo '</div>';
        echo '<button type="button" onclick="document.getElementById(\'' . esc_js( $id ) . '\').remove();" style="' . $btn . '">&#x2297;</button>';
        echo '</div>';
    }
}

// ─── Notice: Edge Cache Enabled ─────────────────────────────────────────────
if ( ! function_exists( 'pressable_edge_cache_notice_success_enable' ) ) {
    function pressable_edge_cache_notice_success_enable() {
        $screen = get_current_screen();
        if ( isset( $screen ) && 'toplevel_page_pressable_cache_management' !== $screen->id ) return;

        $html  = '<h3 style="margin:0 0 8px;font-size:14px;font-weight:700;color:#040024;">'
               . '&#x1F389; ' . esc_html__( 'Edge Cache Enabled!', 'pressable_cache_management' ) . '</h3>';
        $html .= '<p style="margin:0 0 6px;font-size:13px;color:#475569;">'
               . esc_html__( 'Edge Cache provides performance improvements, particularly for Time to First Byte (TTFB), by serving page cache from the nearest server to your website visitors.', 'pressable_cache_management' )
               . '</p>';
        $html .= '<a href="https://pressable.com/knowledgebase/edge-cache/" target="_blank" '
               . 'rel="noopener noreferrer" style="font-size:13px;color:#dd3a03;font-weight:600;text-decoration:none;">'
               . esc_html__( 'Learn more about Edge Cache.', 'pressable_cache_management' ) . '</a>';

        $nid  = 'pcm-ec-enabled-' . substr( md5( microtime() ), 0, 8 );
        $wrap = 'display:flex;align-items:flex-start;justify-content:space-between;gap:16px;'
              . 'border-left:4px solid #03fcc2;background:#fff;'
              . 'border-radius:0 8px 8px 0;padding:14px 18px;'
              . 'box-shadow:0 2px 8px rgba(4,0,36,.07);margin:10px 0;font-family:sans-serif;';
        $btn  = 'background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;'
              . 'line-height:1;padding:0;flex-shrink:0;margin-top:2px;';
        echo '<div style="max-width:920px;margin:0 20px;">';
        echo '<div id="' . esc_attr( $nid ) . '" style="' . $wrap . '">';
        echo '<div style="flex:1;">' . $html . '</div>';
        echo '<button type="button" onclick="document.getElementById(\'' . esc_js( $nid ) . '\').remove();" style="' . $btn . '">&#x2297;</button>';
        echo '</div>';
        echo '</div>';
    }
}

// ─── Notice: Edge Cache Disabled ────────────────────────────────────────────
if ( ! function_exists( 'pressable_edge_cache_notice_success_disable' ) ) {
    function pressable_edge_cache_notice_success_disable() {
        $screen = get_current_screen();
        if ( isset( $screen ) && 'toplevel_page_pressable_cache_management' !== $screen->id ) return;
        pcm_branded_notice( esc_html__( 'Edge Cache Deactivated.', 'pressable_cache_management' ), '#03fcc2' );
    }
}

// ─── Notice: Error ───────────────────────────────────────────────────────────
if ( ! function_exists( 'pcm_pressable_edge_cache_error_msg' ) ) {
    function pcm_pressable_edge_cache_error_msg( $error_message = '' ) {
        $screen = get_current_screen();
        if ( isset( $screen ) && 'toplevel_page_pressable_cache_management' !== $screen->id ) return;
        $msg = empty( $error_message )
            ? esc_html__( 'Something went wrong trying to communicate with the Edge Cache system. Try again.', 'pressable_cache_management' )
            : esc_html( $error_message );
        pcm_branded_notice( $msg, '#dd3a03' );
    }
}

// ─── Enable Edge Cache (mirrors repo exactly, adds branded notices) ─────────
function pcm_pressable_enable_edge_cache() {
    if ( isset( $_POST['enable_edge_cache_nonce'] ) &&
         wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['enable_edge_cache_nonce'] ) ), 'enable_edge_cache_nonce' ) ) {

        if ( class_exists( 'Edge_Cache_Plugin' ) ) {
            $edge_cache = Edge_Cache_Plugin::get_instance();
            $result = $edge_cache->query_ec_backend( 'on', array( 'wp_action' => 'manual_dashboard_set' ) );

            if ( is_wp_error( $result ) ) {
                update_option( 'edge-cache-status',  'Error' );
                update_option( 'edge-cache-enabled', 'disabled' );
                add_action( 'admin_notices', function() use ( $result ) {
                    pcm_pressable_edge_cache_error_msg( $result->get_error_message() );
                });
            } else {
                update_option( 'edge-cache-status',  'Success' );
                update_option( 'edge-cache-enabled', 'enabled' );
                delete_transient( 'pcm_ec_status_cache' ); // force fresh status on next page load
                add_action( 'admin_notices', 'pressable_edge_cache_notice_success_enable' );
            }
        } else {
            add_action( 'admin_notices', function() {
                pcm_pressable_edge_cache_error_msg( 'Required Edge Cache dependency is not available.' );
            });
        }
    }
}
add_action( 'init', 'pcm_pressable_enable_edge_cache' );

// ─── Disable Edge Cache (mirrors repo exactly, adds branded notices) ────────
function pcm_pressable_disable_edge_cache() {
    if ( isset( $_POST['disable_edge_cache_nonce'] ) &&
         wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['disable_edge_cache_nonce'] ) ), 'disable_edge_cache_nonce' ) ) {

        if ( class_exists( 'Edge_Cache_Plugin' ) ) {
            $edge_cache = Edge_Cache_Plugin::get_instance();
            $result = $edge_cache->query_ec_backend( 'off', array( 'wp_action' => 'manual_dashboard_set' ) );

            if ( is_wp_error( $result ) ) {
                update_option( 'edge-cache-status',  'Error' );
                update_option( 'edge-cache-enabled', 'enabled' ); // stays enabled on failure
                add_action( 'admin_notices', function() use ( $result ) {
                    pcm_pressable_edge_cache_error_msg( $result->get_error_message() );
                });
            } else {
                update_option( 'edge-cache-status',  'Success' );
                update_option( 'edge-cache-enabled', 'disabled' );
                delete_transient( 'pcm_ec_status_cache' ); // force fresh status on next page load
                add_action( 'admin_notices', 'pressable_edge_cache_notice_success_disable' );
            }
        } else {
            add_action( 'admin_notices', function() {
                pcm_pressable_edge_cache_error_msg( 'Required Edge Cache dependency is not available.' );
            });
        }
    }
}
add_action( 'init', 'pcm_pressable_disable_edge_cache' );
