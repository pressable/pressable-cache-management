<?php
/**
 * Pressable Cache Management — Flush Batcache for WooCommerce Individual Pages
 *
 * When enabled, copies pcm_batcache_manager.php into mu-plugins so that Batcache
 * is flushed automatically for any individual page/product updated via WooCommerce API.
 * When disabled, removes the mu-plugin file and restores the previous state.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options = get_option( 'pressable_cache_management_options' );
$enabled = ! empty( $options['flush_batcache_for_woo_product_individual_page_checkbox'] );

$mu_plugin_dest = WP_CONTENT_DIR . '/mu-plugins/pcm_batcache_manager.php';
$mu_plugin_src  = plugin_dir_path( __FILE__ ) . 'pcm_batcache_manager.php';

if ( $enabled ) {

    // ── Feature ON ───────────────────────────────────────────────────────────

    // Always sync the source file into mu-plugins so that any updates to
    // pcm_batcache_manager.php (e.g. targeted flush fixes) take effect immediately.
    // Previously this only copied on first enable, meaning edits to the source
    // were never deployed to the live mu-plugin copy.
    $needs_update = ! file_exists( $mu_plugin_dest )
        || ( file_exists( $mu_plugin_src ) && md5_file( $mu_plugin_src ) !== md5_file( $mu_plugin_dest ) );

    if ( $needs_update && file_exists( $mu_plugin_src ) && @copy( $mu_plugin_src, $mu_plugin_dest ) ) {
        if ( ! file_exists( $mu_plugin_dest ) ) {
            // Only flush and show notice on fresh enable, not on every update
            wp_cache_flush();
            update_option( 'flush_batcache_for_woo_product_individual_page_activate_notice', 'activating' );
        }
    }

    // ── Show branded activation notice (once, on next page load) ─────────────
    add_action( 'init', 'pcm_woo_individual_page_activation_notice' );

    function pcm_woo_individual_page_activation_notice() {
        $state = get_option( 'flush_batcache_for_woo_product_individual_page_activate_notice', 'activated' );
        if ( 'activating' !== $state || ! current_user_can( 'manage_options' ) ) {
            return;
        }
        add_action( 'admin_notices', 'pcm_woo_individual_page_render_notice' );
        update_option( 'flush_batcache_for_woo_product_individual_page_activate_notice', 'activated' );
    }

    function pcm_woo_individual_page_render_notice() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'toplevel_page_pressable_cache_management' ) {
            return;
        }

        $nid  = 'pcm-woo-notice-' . substr( md5( microtime() ), 0, 8 );
        $wrap = 'display:flex;align-items:center;justify-content:space-between;gap:12px;'
              . 'border-left:4px solid #03fcc2;background:#fff;border-radius:0 8px 8px 0;'
              . 'padding:14px 18px;box-shadow:0 2px 8px rgba(4,0,36,.07);'
              . 'margin:10px 0;font-family:sans-serif;';
        $icon_wrap = 'display:flex;align-items:center;gap:10px;';
        $icon      = '<span style="display:inline-flex;align-items:center;justify-content:center;'
                   . 'width:32px;height:32px;border-radius:50%;background:#f0fdf9;flex-shrink:0;">'
                   . '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">'
                   . '<path d="M8 1.5a6.5 6.5 0 100 13 6.5 6.5 0 000-13zM0 8a8 8 0 1116 0A8 8 0 010 8z" fill="#03fcc2"/>'
                   . '<path d="M8 7a1 1 0 011 1v3a1 1 0 11-2 0V8a1 1 0 011-1zM8 5.5a1 1 0 100-2 1 1 0 000 2z" fill="#03fcc2"/>'
                   . '</svg></span>';
        $btn = 'background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;'
             . 'line-height:1;padding:0;flex-shrink:0;margin-top:2px;';

        echo '<div style="max-width:1120px;margin:0 auto;padding:0 20px;box-sizing:border-box;">';
        echo '<div id="' . esc_attr( $nid ) . '" style="' . $wrap . '">';
        echo '<div style="' . $icon_wrap . '">' . $icon;
        echo '<div>';
        echo '<p style="margin:0 0 2px;font-size:13px;font-weight:600;color:#040024;">'
           . esc_html__( 'Flush Batcache for WooCommerce Product Pages — Enabled', 'pressable_cache_management' )
           . '</p>';
        echo '<p style="margin:0;font-size:12px;color:#64748b;">'
           . esc_html__( 'Automatically flush individual pages, including product pages updated via the WooCommerce API.', 'pressable_cache_management' )
           . '</p>';
        echo '</div></div>';
        echo '<button type="button" onclick="document.getElementById(\'' . esc_js( $nid ) . '\').remove();" style="' . $btn . '">&#x2297;</button>';
        echo '</div>';
        echo '</div>';
    }

} else {

    // ── Feature OFF ──────────────────────────────────────────────────────────

    // Reset so notice shows again next time it is re-enabled
    update_option( 'flush_batcache_for_woo_product_individual_page_activate_notice', 'activating' );

    if ( file_exists( $mu_plugin_dest ) ) {
        @unlink( $mu_plugin_dest );
        wp_cache_flush();
    }

}
