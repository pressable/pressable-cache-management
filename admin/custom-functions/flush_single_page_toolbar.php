<?php
/**
 * Pressable Cache Management - Flush Batcache for Individual Page from toolbar
 * Sourced from official repo flush_single_page_toolbar.php with branded notices.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['flush_object_cache_for_single_page'] ) && ! empty( $options['flush_object_cache_for_single_page'] ) ) {

    if ( ! class_exists( 'PcmFlushCacheAdminbar' ) ) {

        class PcmFlushCacheAdminbar {

            public function __construct() {}

            public function add() {
                if ( is_admin() ) {
                    add_action( 'wp_before_admin_bar_render', array( $this, 'PcmFlushCacheAdminbar' ) );
                    add_action( 'admin_enqueue_scripts', array( $this, 'load_toolbar_js' ) );
                    add_action( 'admin_enqueue_scripts', array( $this, 'load_remove_branding_toolbar_js' ) );
                    add_action( 'admin_enqueue_scripts', array( $this, 'load_toolbar_css' ) );
                } else {
                    if ( is_admin() || is_admin_bar_showing() ) {
                        add_action( 'wp_before_admin_bar_render', array( $this, 'pcm_toolbar_for_page_preview' ) );
                        add_action( 'wp_enqueue_scripts', array( $this, 'load_toolbar_js' ) );
                        add_action( 'admin_enqueue_scripts', array( $this, 'load_remove_branding_toolbar_js' ) );
                        add_action( 'wp_enqueue_scripts', array( $this, 'load_toolbar_css' ) );
                        add_action( 'wp_footer', array( $this, 'print_my_inline_script' ) );
                    }
                }

                // AJAX: flush batcache for current page
                add_action( 'wp_ajax_pcm_delete_current_page_cache',      array( $this, 'pcm_delete_current_page_cache' ) );
                // AJAX: purge edge cache for current page
                add_action( 'wp_ajax_pcm_purge_current_page_edge_cache',  array( $this, 'pcm_purge_current_page_edge_cache' ) );
            }

            public function pcm_delete_current_page_cache() {
                if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'pcm_nonce' ) ) {
                    die( json_encode( array( 'Security Error!', 'error', 'alert' ) ) );
                }

                global $batcache, $wp_object_cache;

                if ( ! isset( $batcache ) || ! is_object( $batcache ) || ! method_exists( $wp_object_cache, 'incr' ) ) {
                    return;
                }

                $batcache->configure_groups();
                $path = urldecode( esc_url_raw( wp_unslash( $_GET['path'] ) ) );

                if ( preg_match( '/\.{2,}/', $path ) ) {
                    die( 'Suspected Directory Traversal Attack' );
                }

                $url = get_home_url() . $path;
                $url = apply_filters( 'batcache_manager_link', $url );
                if ( empty( $url ) ) return false;

                do_action( 'batcache_manager_before_flush', $url );
                $url = set_url_scheme( $url, 'http' );
                update_option( 'single-page-url-flushed', $url );

                $url_key = md5( $url );
                if ( is_object( $batcache ) ) {
                    wp_cache_add( "{$url_key}_version", 0, $batcache->group );
                    wp_cache_incr( "{$url_key}_version", 1, $batcache->group );
                }

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
                wp_send_json_success( array( 'flushed' => 'batcache' ) );
            }

            public function pcm_purge_current_page_edge_cache() {
                if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'pcm_nonce' ) ) {
                    die( json_encode( array( 'Security Error!', 'error', 'alert' ) ) );
                }

                $path = urldecode( esc_url_raw( wp_unslash( $_GET['path'] ) ) );
                if ( preg_match( '/\.{2,}/', $path ) ) {
                    die( 'Suspected Directory Traversal Attack' );
                }

                $url = get_home_url() . $path;
                update_option( 'edge-cache-single-page-url-purged', $url );
                if ( empty( $url ) ) return false;

                if ( class_exists( 'Edge_Cache_Plugin' ) ) {
                    $edge_cache = Edge_Cache_Plugin::get_instance();
                    $result     = $edge_cache->purge_uris_now( array( $url ) );
                    update_option( 'single-page-edge-cache-purge-time-stamp', gmdate( 'j M Y, g:ia' ) . ' UTC' );
                    wp_send_json_success( array( 'flushed' => 'edge-cache' ) );
                }

                wp_send_json_error( array( 'reason' => 'Edge_Cache_Plugin not available' ) );
            }

            public function load_toolbar_css() {
                wp_enqueue_style( 'pressable-cache-management-toolbar',
                    plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/toolbar.css',
                    array(), time(), 'all' );
            }

            public function load_toolbar_js() {
                wp_enqueue_script( 'pcm-toolbar',
                    plugin_dir_url( dirname( __FILE__ ) ) . 'public/js/toolbar.js',
                    array( 'jquery' ), time(), true );

                // Pass nonce and edge-cache state to JS for BOTH admin and frontend contexts.
                // pcm_nonce from print_my_inline_script() only runs on wp_footer (frontend).
                // wp_localize_script covers both admin and frontend reliably.
                $edge_on = ( get_option('edge-cache-enabled') === 'enabled' ) ? '1' : '0';
                wp_localize_script( 'pcm-toolbar', 'pcmToolbarData', array(
                    'nonce'    => wp_create_nonce( 'pcm_nonce' ),
                    'ajaxurl'  => admin_url( 'admin-ajax.php' ),
                    'flushEdge'=> $edge_on,
                ) );
            }

            public function load_remove_branding_toolbar_js() {
                wp_enqueue_script( 'pcm-toolbar-branding',
                    plugin_dir_url( dirname( __FILE__ ) ) . 'public/js/toolbar_remove_branding.js',
                    array( 'jquery' ), time(), true );
            }

            public function print_my_inline_script() { ?>
                <script>
                var pcm_ajaxurl = "<?php echo esc_url( admin_url('admin-ajax.php') ); ?>";
                var pcm_nonce   = "<?php echo wp_create_nonce('pcm_nonce'); ?>";
                </script>
                <?php
            }

            public function pcm_toolbar_for_page_preview() {
                global $wp_admin_bar;

                $branding_opts     = get_option( 'remove_pressable_branding_tab_options' );
                $branding_disabled = $branding_opts && 'disable' == $branding_opts['branding_on_off_radio_button'];
                $edge_cache_on     = ( get_option('edge-cache-enabled') === 'enabled' );

                // Single label: include Edge Cache in the title when it is active
                $flush_label = $edge_cache_on
                    ? __( 'Flush Cache for This Page', 'pressable_cache_management' )
                    : __( 'Flush Batcache for This Page', 'pressable_cache_management' );

                if ( $branding_disabled ) {
                    $parent = 'pcm-toolbar-parent-remove-branding';
                    $wp_admin_bar->add_node( array(
                        'id'    => $parent,
                        'title' => __( 'Flush Cache', 'pressable_cache_management' ),
                        'class' => 'pcm-toolbar-child',
                    ));
                    // Combined item — JS fires both Batcache + Edge Cache flushes in sequence
                    $wp_admin_bar->add_menu( array(
                        'id'     => 'pcm-toolbar-parent-remove-branding-flush-cache-of-this-page',
                        'title'  => $flush_label,
                        'parent' => $parent,
                        'meta'   => array( 'class' => 'pcm-toolbar-child' ),
                    ));
                } else {
                    $parent = 'pcm-toolbar-parent';
                    $wp_admin_bar->add_node( array(
                        'id'    => $parent,
                        'title' => __( 'Flush Cache', 'pressable_cache_management' ),
                    ));
                    // Combined item — JS fires both Batcache + Edge Cache flushes in sequence
                    $wp_admin_bar->add_menu( array(
                        'id'     => 'pcm-toolbar-parent-flush-cache-of-this-page',
                        'title'  => $flush_label,
                        'parent' => $parent,
                        'meta'   => array( 'class' => 'pcm-toolbar-child' ),
                    ));
                }
            }

            // Empty admin-side toolbar (handled by object_cache_admin_bar.php)
            public function PcmFlushCacheAdminbar() {}
        }
    }

    add_action( 'init', 'pcm_show_flush_cache_option_for_single_page' );

    function pcm_show_flush_cache_option_for_single_page() {
        $current_user = wp_get_current_user();
        if ( current_user_can('manage_woocommerce') || current_user_can('administrator') ) {
            $toolbar = new PcmFlushCacheAdminbar();
            $toolbar->add();
        } else {
            if ( ! function_exists('load_admin_toolbar_css') ) {
                function load_admin_toolbar_css() {
                    wp_enqueue_style( 'pressable-cache-management-toolbar',
                        plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/toolbar.css',
                        array(), time(), 'all' );
                }
            }
            add_action( 'init', 'load_admin_toolbar_css' );
        }
    }
}
