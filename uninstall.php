<?php
/**
 * Runs on Uninstall of Pressable Cache Management
 *
 * @package   Pressable Cache Management
 * @author    Pressable Support Team
 * @license   GPL-2.0+
 * @link      http://pressable.com
 */

// Exit if uninstall constant is not defined (security check)
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove mu-plugins written by this plugin (batcache extensions, exclusions, etc.)
include_once plugin_dir_path( __FILE__ ) . 'remove-mu-plugins-batcache-on-uninstall.php';

// ── Delete every option this plugin has ever written to the database ──────────

$options_to_delete = array(

    // ── Main settings groups ──────────────────────────────────────────────────
    'pressable_cache_management_options',       // All main tab checkbox settings
    'remove_pressable_branding_tab_options',    // Branding show/hide setting
    'edge_cache_settings_tab_options',          // Edge Cache tab settings

    // ── Object cache flush timestamps ─────────────────────────────────────────
    'flush-obj-cache-time-stamp',               // Global object cache flush time
    'flush-cache-theme-plugin-time-stamp',      // Flush on plugin/theme update
    'flush-cache-page-edit-time-stamp',         // Flush on post/page edit
    'flush-cache-on-page-post-delete-time-stamp', // Flush on page/post delete
    'flush-cache-on-comment-delete-time-stamp', // Flush on comment delete

    // ── Individual page flush ─────────────────────────────────────────────────
    'flush-object-cache-for-single-page-time-stamp', // Single page flush time
    'flush-object-cache-for-single-page-notice',     // Single page flush notice
    'single-page-url-flushed',                  // URL of last single-page flush
    'single-page-edge-cache-purge-time-stamp',  // Single page edge cache purge time
    'single-page-path-url',                     // Stored path for single page
    'page-title',                               // Stored page title
    'page-url',                                 // Stored page URL

    // ── Edge cache ────────────────────────────────────────────────────────────
    'edge-cache-enabled',                       // Edge cache on/off state
    'edge-cache-status',                        // Edge cache status string
    'edge-cache-purge-time-stamp',              // Last edge cache purge time
    'edge-cache-single-page-url-purged',        // Last single-page edge purge URL

    // ── Batcache extension ────────────────────────────────────────────────────
    'pcm_extend_batcache_notice_pending',       // Pending "extending batcache" notice flag

    // ── Cache exclusions ──────────────────────────────────────────────────────
    'exempt_from_batcache',                     // Pages excluded from Batcache
    'exclude_query_string_gclid',               // GCLID query string exclusion flag
    'exclude_query_string_gclid_activate_notice',

    // ── WooCommerce product page flush ────────────────────────────────────────
    'flush_batcache_for_woo_product_individual_page_activate_notice',

    // ── Cookie / WP-PP cache ──────────────────────────────────────────────────
    'cache_wpp_cookies_pages',
    'cache_wpp_cookies_pages_activate_notice',

    // ── Legacy / CDN options (from older plugin versions) ─────────────────────
    'cdn_settings_tab_options',
    'pressable_api_authentication_tab_options',
    'cdn-cache-purge-time-stamp',
    'cdn-api-state',
    'cdnenabled',
    'pressable_api_admin_notice__status',
    'pressable_cdn_connection_decactivated_notice',
    'pressable_api_enable_cdn_connection_admin_notice',
    'extend_batcache_activate_notice',
    'extend_cdn_activate_notice',
    'exclude_images_from_cdn_activate_notice',
    'exclude_json_js_from_cdn_notice',
    'exclude_json_js_from_cdn_activate_notice',
    'exclude_css_from_cdn_activate_notice',
    'exclude_fonts_from_cdn_activate_notice',
    'exempt_batcache_activate_notice',
    'flush-object-cache-for-single-page-notice',
    'pressable_site_id',
    'pcm_site_id_added_activate_notice',
    'pcm_site_id_con_res',
    'pcm_client_id',
    'pcm_client_secret',

    // ── Update checker transients (PUC) ───────────────────────────────────────
    // PUC stores its own transients; delete them to leave no trace
    'puc_check_now_pressable-cache-management',

    // ── Plugin own transients (stored as options by WP) ───────────────────────
    '_transient_pcm_batcache_status',
    '_transient_timeout_pcm_batcache_status',
);

foreach ( $options_to_delete as $option ) {
    delete_option( $option );
}

// ── Delete PUC transients (wp_options rows with _transient_ prefix) ───────────
global $wpdb;
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like( '_transient_puc_' )     . '%pressable-cache-management%',
        $wpdb->esc_like( '_transient_timeout_puc_' ) . '%pressable-cache-management%'
    )
);
