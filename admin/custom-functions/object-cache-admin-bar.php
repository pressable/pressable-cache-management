<?php
/**
 * Pressable Cache Management - Admin Bar Cache Buttons
 * Branded popup notices matching plugin theme (#dd3a03, #040024, #03fcc2)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── Branded modal popup (replaces browser alert) ─────────────────────────
add_action( 'admin_footer', 'pcm_abar_modal_html' );
function pcm_abar_modal_html() {
    if ( ! pcm_abar_can_view() ) return;
    ?>
    <div id="pcm-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(4,0,36,.45);z-index:999999;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:12px;padding:28px 32px;max-width:440px;width:90%;box-shadow:0 8px 40px rgba(4,0,36,.18);font-family:sans-serif;position:relative;">
            <div style="width:48px;height:4px;background:#03fcc2;border-radius:4px;margin-bottom:18px;"></div>
            <div id="pcm-modal-message" style="font-size:14px;color:#040024;line-height:1.6;white-space:pre-line;margin-bottom:22px;"></div>
            <button id="pcm-modal-ok" style="background:#dd3a03;color:#fff;border:none;border-radius:8px;padding:10px 28px;font-size:13.5px;font-weight:700;cursor:pointer;font-family:sans-serif;letter-spacing:.4px;transition:background .2s;">OK</button>
        </div>
    </div>
    <script>
    (function($){
        function pcmShowModal(msg) {
            $('#pcm-modal-message').text(msg);
            $('#pcm-modal-overlay').css('display','flex');
        }
        $('#pcm-modal-ok, #pcm-modal-overlay').on('click', function(e){
            if (e.target === this) $('#pcm-modal-overlay').hide();
        });
        $('#pcm-modal-ok').hover(
            function(){ $(this).css('background','#b82f00'); },
            function(){ $(this).css('background','#dd3a03'); }
        );
        window.pcmShowModal = pcmShowModal;
    })(jQuery);
    </script>
    <?php
}

// ─── JS: Flush Object Cache ────────────────────────────────────────────────
add_action( 'admin_footer', 'pcm_abar_object_js' );
function pcm_abar_object_js() { ?>
    <script>
    jQuery(document).ready(function($){
        $('li#wp-admin-bar-cache-purge .ab-item').on('click', function(e){
            e.preventDefault();
            $.post(ajaxurl, { action: 'flush_pressable_cache' }, function(r){
                window.pcmShowModal(r.trim());
            });
        });
    });
    </script>
<?php }

// ─── JS: Purge Edge Cache ──────────────────────────────────────────────────
add_action( 'admin_footer', 'pcm_abar_edge_js' );
function pcm_abar_edge_js() { ?>
    <script>
    jQuery(document).ready(function($){
        $('li#wp-admin-bar-edge-purge .ab-item').on('click', function(e){
            e.preventDefault();
            $.ajax({ url: ajaxurl, type: 'POST', data: { action: 'pressable_edge_cache_purge' },
                success: function(r){ window.pcmShowModal(r.trim()); },
                error:   function(){ window.pcmShowModal('An error occurred during the Edge Cache purge request.'); }
            });
        });
    });
    </script>
<?php }

// ─── JS: Flush Object + Edge Cache ────────────────────────────────────────
add_action( 'admin_footer', 'pcm_abar_combined_js' );
function pcm_abar_combined_js() { ?>
    <script>
    jQuery(document).ready(function($){
        $('li#wp-admin-bar-combined-cache-purge .ab-item').on('click', function(e){
            e.preventDefault();
            $.ajax({ url: ajaxurl, type: 'POST', data: { action: 'flush_combined_cache' },
                success: function(r){ window.pcmShowModal(r.trim()); },
                error:   function(){ window.pcmShowModal('An error occurred during the combined cache flush.'); }
            });
        });
    });
    </script>
<?php }

// ─── Enqueue toolbar CSS ───────────────────────────────────────────────────
function pcm_abar_load_css() {
    wp_enqueue_style( 'pressable-cache-management-toolbar',
        plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/toolbar.css',
        array(), time(), 'all' );
}
add_action( 'init', 'pcm_abar_load_css' );

// ─── AJAX Hooks ───────────────────────────────────────────────────────────
add_action( 'wp_ajax_flush_pressable_cache',    'pcm_abar_flush_object_callback' );
add_action( 'wp_ajax_pressable_edge_cache_purge', 'pcm_abar_purge_edge_callback' );
add_action( 'wp_ajax_flush_combined_cache',     'pcm_abar_flush_combined_callback' );

function pcm_abar_flush_object_callback() {
    if ( ! current_user_can('administrator') && ! current_user_can('editor') && ! current_user_can('manage_woocommerce') ) {
        echo 'You do not have permission to flush the Object Cache.';
        wp_die();
    }
    wp_cache_flush();
    if ( function_exists('batcache_clear_cache') ) batcache_clear_cache();
    update_option( 'flush-obj-cache-time-stamp', gmdate('j M Y, g:ia') . ' UTC' );
    echo esc_html__( 'Object Cache Flushed successfully.', 'pressable_cache_management' );
    wp_die();
}

function pcm_abar_purge_edge_callback() {
    if ( ! current_user_can('administrator') && ! current_user_can('editor') && ! current_user_can('manage_woocommerce') ) {
        echo 'You do not have permission to purge the Edge Cache.';
        wp_die();
    }
    if ( ! class_exists('Edge_Cache_Plugin') ) {
        echo esc_html__( 'Error: Edge Cache Plugin is not active. Purge aborted.', 'pressable_cache_management' );
        wp_die();
    }
    $edge_cache = Edge_Cache_Plugin::get_instance();
    if ( ! method_exists( $edge_cache, 'purge_domain_now' ) ) {
        echo esc_html__( 'Error: Edge Cache purge method unavailable.', 'pressable_cache_management' );
        wp_die();
    }
    $result = $edge_cache->purge_domain_now( 'admin-bar-edge-purge' );
    if ( $result ) {
        update_option( 'edge-cache-purge-time-stamp', gmdate('j M Y, g:ia') . ' UTC' );
        echo esc_html__( 'Edge Cache purged successfully.', 'pressable_cache_management' );
    } else {
        echo esc_html__( 'Edge Cache purge failed. It might be disabled or rate-limited.', 'pressable_cache_management' );
    }
    wp_die();
}

function pcm_abar_flush_combined_callback() {
    if ( ! current_user_can('administrator') && ! current_user_can('editor') && ! current_user_can('manage_woocommerce') ) {
        echo 'You do not have permission to flush the combined cache.';
        wp_die();
    }
    $messages = array();

    // Object cache
    wp_cache_flush();
    if ( function_exists('batcache_clear_cache') ) batcache_clear_cache();
    update_option( 'flush-obj-cache-time-stamp', gmdate('j M Y, g:ia') . ' UTC' );
    $messages[] = esc_html__( 'Object Cache Flushed successfully.', 'pressable_cache_management' );

    // Edge cache
    if ( class_exists('Edge_Cache_Plugin') ) {
        $edge_cache = Edge_Cache_Plugin::get_instance();
        if ( method_exists( $edge_cache, 'purge_domain_now' ) ) {
            $result = $edge_cache->purge_domain_now( 'admin-bar-combined-purge' );
            if ( $result ) {
                update_option( 'edge-cache-purge-time-stamp', gmdate('j M Y, g:ia') . ' UTC' );
                $messages[] = esc_html__( 'Edge Cache Purged successfully.', 'pressable_cache_management' );
            } else {
                $messages[] = esc_html__( 'Edge Cache purge failed (possibly disabled or rate-limited).', 'pressable_cache_management' );
            }
        } else {
            $messages[] = esc_html__( 'Edge Cache Plugin active, but purge method unavailable.', 'pressable_cache_management' );
        }
    } else {
        $messages[] = esc_html__( 'Edge Cache Plugin not found; skipping Edge Cache purge.', 'pressable_cache_management' );
    }

    echo '- ' . implode( "\n- ", $messages );
    wp_die();
}

// ─── Permission check ─────────────────────────────────────────────────────
if ( ! function_exists('pcm_abar_can_view') ) {
    function pcm_abar_can_view() {
        return current_user_can('administrator') || current_user_can('editor') || current_user_can('manage_woocommerce');
    }
}

// ─── Admin Bar Menu ───────────────────────────────────────────────────────
add_action( 'admin_bar_menu', 'pcm_abar_add_menu', 100 );
function pcm_abar_add_menu( $wp_admin_bar ) {
    if ( is_network_admin() || ! pcm_abar_can_view() ) return;

    $branding_opts     = get_option('remove_pressable_branding_tab_options');
    $branding_disabled = $branding_opts && 'disable' == $branding_opts['branding_on_off_radio_button'];

    $parent_id    = $branding_disabled ? 'pcm-wp-admin-toolbar-parent-remove-branding' : 'pcm-wp-admin-toolbar-parent';
    $parent_title = $branding_disabled ? 'Cache Control' : 'Cache Management';

    // Detect Edge Cache state
    $edge_cache_is_enabled = false;
    if ( class_exists('Edge_Cache_Plugin') ) {
        $ec            = Edge_Cache_Plugin::get_instance();
        $server_status = method_exists($ec,'get_ec_status') ? $ec->get_ec_status() : null;
        if ( defined('Edge_Cache_Plugin::EC_ENABLED') && $server_status === Edge_Cache_Plugin::EC_ENABLED ) {
            $edge_cache_is_enabled = true;
        } elseif ( get_option('edge-cache-enabled') === 'enabled' ) {
            $edge_cache_is_enabled = true;
        }
    }

    // Parent
    $wp_admin_bar->add_node( array( 'id' => $parent_id, 'title' => $parent_title ) );

    // Flush Object Cache
    $wp_admin_bar->add_menu( array(
        'id'     => 'cache-purge',
        'title'  => __( 'Flush Object Cache', 'pressable_cache_management' ),
        'parent' => $parent_id,
        'meta'   => array( 'class' => 'pcm-wp-admin-toolbar-child' ),
    ));

    // Edge Cache options (only if enabled)
    if ( $edge_cache_is_enabled ) {
        $wp_admin_bar->add_menu( array(
            'id'     => 'edge-purge',
            'title'  => __( 'Purge Edge Cache', 'pressable_cache_management' ),
            'parent' => $parent_id,
            'meta'   => array( 'class' => 'pcm-wp-admin-toolbar-child' ),
        ));
        $wp_admin_bar->add_menu( array(
            'id'     => 'combined-cache-purge',
            'title'  => __( 'Flush Object & Edge Cache', 'pressable_cache_management' ),
            'parent' => $parent_id,
            'meta'   => array( 'class' => 'pcm-wp-admin-toolbar-child' ),
        ));
    }

    // Cache Settings (admin only)
    if ( current_user_can('administrator') ) {
        $wp_admin_bar->add_menu( array(
            'id'     => 'settings',
            'title'  => __( 'Cache Settings', 'pressable_cache_management' ),
            'parent' => $parent_id,
            'href'   => admin_url('admin.php?page=pressable_cache_management'),
            'meta'   => array( 'class' => 'pcm-wp-admin-toolbar-child' ),
        ));
    }
}
