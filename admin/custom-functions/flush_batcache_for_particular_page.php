<?php
/**
 * Pressable Cache Management - Flush cache for a particular page (column link)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['flush_object_cache_for_single_page'] ) && ! empty( $options['flush_object_cache_for_single_page'] ) ) {

    add_action( 'init', 'pcm_show_flush_cache_column' );

    function pcm_show_flush_cache_column() {
        if ( current_user_can('administrator') || current_user_can('editor') || current_user_can('manage_woocommerce') ) {
            $column = new FlushObjectCachePageColumn();
            $column->add();
        }
    }

    function flush_object_cache_for_single_page_notice() {
        $state = get_option( 'flush-object-cache-for-single-page-notice', 'activating' );

        if ( 'activating' === $state &&
            ( current_user_can('administrator') || current_user_can('editor') || current_user_can('manage_woocommerce') )
        ) {
            add_action( 'admin_notices', function() {
                $screen = get_current_screen();
                if ( ! isset( $screen ) || $screen->id !== 'toplevel_page_pressable_cache_management' ) return;

                $wrap = 'display:flex;align-items:center;justify-content:space-between;gap:12px;'
                      . 'border-left:4px solid #03fcc2;background:#fff;border-radius:0 8px 8px 0;'
                      . 'padding:14px 18px;box-shadow:0 2px 8px rgba(4,0,36,.07);'
                      . 'margin:10px 0;font-family:sans-serif;';
                $btn  = 'background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;line-height:1;padding:0;';
                echo '<div style="max-width:1120px;margin:0 auto;padding:0 20px;box-sizing:border-box;">';
                echo '<div id="' . $pcm_nid . '" style="' . $wrap . '">';
                echo '<p style="margin:0;font-size:13px;color:#040024;">'
                   . esc_html__( 'You can Flush Cache for Individual page or post from page preview.', 'pressable_cache_management' )
                   . '</p>';
                echo '<button type="button" onclick="document.getElementById(\'' . $pcm_nid . '\').remove();" style="' . $btn . '">&#x2297;</button>';
                echo '</div>';
                echo '</div>';
            });

            update_option( 'flush-object-cache-for-single-page-notice', 'activated' );
        }
    }
    add_action( 'init', 'flush_object_cache_for_single_page_notice' );

} else {
    update_option( 'flush-object-cache-for-single-page-notice', 'activating' );
}

// ─── FlushObjectCachePageColumn class ────────────────────────────────────────
if ( ! class_exists( 'FlushObjectCachePageColumn' ) ) {
    class FlushObjectCachePageColumn {
        public function __construct() {}

        public function add() {
            add_filter( 'post_row_actions', array( $this, 'add_flush_object_cache_link' ), 10, 2 );
            add_filter( 'page_row_actions', array( $this, 'add_flush_object_cache_link' ), 10, 2 );
            add_action( 'admin_enqueue_scripts', array( $this, 'load_js' ) );
            add_action( 'wp_ajax_pcm_flush_object_cache_column', array( $this, 'flush_object_cache_column' ) );
        }

        public function add_flush_object_cache_link( $actions, $post ) {
            if ( current_user_can('administrator') || current_user_can('editor') || current_user_can('manage_woocommerce') ) {
                $actions['flush_object_cache_url'] =
                    '<a data-id="' . esc_attr( $post->ID ) . '"'
                    . ' data-nonce="' . wp_create_nonce( 'flush-object-cache_' . $post->ID ) . '"'
                    . ' id="flush-object-cache-url-' . esc_attr( $post->ID ) . '"'
                    . ' style="cursor:pointer;">'
                    . esc_html__( 'Flush Cache', 'pressable_cache_management' ) . '</a>';
            }
            return $actions;
        }

        public function flush_object_cache_column() {
            if ( ! ( current_user_can('administrator') || current_user_can('editor') || current_user_can('manage_woocommerce') ) ) {
                die( json_encode( array( 'success' => false, 'message' => 'Unauthorized' ) ) );
            }

            if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'flush-object-cache_' . intval( $_GET['id'] ) ) ) {
                die( json_encode( array( 'success' => false, 'message' => 'Nonce verification failed' ) ) );
            }

            $url_key    = get_permalink( intval( $_GET['id'] ) );
            $page_title = get_the_title( intval( $_GET['id'] ) );
            update_option( 'page-title', $page_title );

            global $batcache, $wp_object_cache;

            if ( ! isset( $batcache ) || ! is_object( $batcache ) || ! method_exists( $wp_object_cache, 'incr' ) ) {
                die( json_encode( array( 'success' => false ) ) );
            }

            $batcache->configure_groups();
            $url = apply_filters( 'batcache_manager_link', $url_key );
            if ( empty( $url ) ) {
                die( json_encode( array( 'success' => false ) ) );
            }

            do_action( 'batcache_manager_before_flush', $url );
            $url     = set_url_scheme( $url, 'http' );
            $url_key = md5( $url );

            wp_cache_add( "{$url_key}_version", 0, $batcache->group );
            wp_cache_incr( "{$url_key}_version", 1, $batcache->group );

            if ( property_exists( $wp_object_cache, 'no_remote_groups' ) ) {
                $k = array_search( $batcache->group, (array) $wp_object_cache->no_remote_groups );
                if ( false !== $k ) {
                    unset( $wp_object_cache->no_remote_groups[ $k ] );
                    wp_cache_set( "{$url_key}_version", $batcache->group );
                    $wp_object_cache->no_remote_groups[ $k ] = $batcache->group;
                }
            }

            do_action( 'batcache_manager_after_flush', $url );
            update_option( 'flush-object-cache-for-single-page-time-stamp', gmdate( 'j M Y, g:ia' ) . ' UTC' );
            // Also store the flushed URL so it shows on the settings page
            update_option( 'single-page-url-flushed', $url );

            die( json_encode( array( 'success' => true ) ) );
        }

        public function load_js() {
            wp_enqueue_script(
                'flush-object-cache-column',
                plugin_dir_url( dirname( __FILE__ ) ) . 'public/js/column.js',
                array(), time(), true
            );
        }
    }
}
