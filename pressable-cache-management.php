<?php
/*
Plugin Name:  Pressable Cache Management
Description:  Pressable cache management made easy
Plugin URI:   https://pressable.com/knowledgebase/pressable-cache-management-plugin/#overview
Author:       Pressable CS Team
Version:      5.8.7
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Text Domain:  pressable_cache_management
Domain Path:  /languages
License:      GPL v2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.txt
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── GitHub Auto-Updates via plugin-update-checker (YahnisElsts/plugin-update-checker) ──
// Library lives at: includes/plugin-update-checker/plugin-update-checker.php
// How updates are triggered: create a GitHub Release (or tag) on the repo and
// bump the Version header in this file. WordPress will show the update notice
// to all sites running the plugin within ~12 hours.
require_once plugin_dir_path( __FILE__ ) . 'includes/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$pcm_update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/pressable/pressable-cache-management/', // GitHub repo URL (with trailing slash)
    __FILE__,                                                     // Full path to main plugin file
    'pressable-cache-management'                                  // Plugin slug (folder name)
);

// Use tagged GitHub Releases as the update source.
// To release an update: tag the commit as v5.x.x in GitHub and publish a Release.
$pcm_update_checker->getVcsApi()->enableReleaseAssets();

// ─── Platform check ──────────────────────────────────────────────────────────
if ( ! defined( 'IS_PRESSABLE' ) ) {
    add_action( 'admin_notices', 'pcm_auto_deactivation_notice' );
    add_action( 'admin_init',    'deactivate_plugin_if_not_pressable' );
}

function deactivate_plugin_if_not_pressable() {
    deactivate_plugins( plugin_basename( __FILE__ ) );
}

function pcm_auto_deactivation_notice() {
    $style = 'margin:50px 20px 20px 0;background:#fff;'
           . 'border-left:4px solid #dd3a03;border-radius:0 8px 8px 0;'
           . 'padding:18px 20px;box-shadow:0 2px 8px rgba(4,0,36,.07);font-family:sans-serif;';
    echo '<div style="' . $style . '">';
    echo '<h3 style="margin:0 0 8px;color:#dd3a03;font-weight:700;">'
       . esc_html__( 'Attention!', 'pressable_cache_management' ) . '</h3>';
    echo '<p style="margin:0;color:#040024;">'
       . esc_html__( 'This plugin is not supported on this platform.', 'pressable_cache_management' ) . '</p>';
    echo '</div>';
}

// ─── i18n – load translations (en_US, es_ES, fr_FR, etc.) ───────────────────
function pressable_cache_management_load_textdomain() {
    // Third parameter must be relative to WP_PLUGIN_DIR (no leading slash, no absolute path)
    load_plugin_textdomain(
        'pressable_cache_management',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );
}
add_action( 'plugins_loaded', 'pressable_cache_management_load_textdomain' );

// ─── Admin-only includes ─────────────────────────────────────────────────────
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/admin-menu.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-register.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-callbacks.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-validate.php';
    require_once plugin_dir_path( __FILE__ ) . 'remove_old_mu_plugins.php';

    // Must load turn_on_off BEFORE purge (purge reuses pcm_edge_notice)
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/turn_on_off_edge_cache.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/purge_edge_cache.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush_object_cache.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/extend_batcache.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/object_cache_admin_bar.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush_batcache_for_woo_individual_page.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/exclude_pages_from_batcache.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush_batcache_for_particular_page.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush_cache_on_comment_delete.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/remove_pressable_branding.php';
}

// ─── Front-end + admin cache flush triggers ──────────────────────────────────
require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush_cache_on_theme_plugin_update.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush_cache_on_page_edit.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush_cache_on_page_post_delete.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush_single_page_toolbar.php';

// ─── 2026 Cacheability Advisor scaffolding ───────────────────────────────────
require_once plugin_dir_path( __FILE__ ) . 'includes/cacheability-advisor/storage.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/cache-busters/detector-framework.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/object-cache-intelligence/intelligence.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/php-opcache-awareness/opcache-awareness.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/redirect-assistant/assistant.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/smart-purge-strategy/strategy.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/security-privacy/security-privacy.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/observability-reporting/reporting.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/guided-remediation-playbooks/playbooks.php';

// ─── Settings link on plugin list page ──────────────────────────────────────
function pcm_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=pressable_cache_management">'
                   . esc_html__( 'Settings', 'pressable_cache_management' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pcm_settings_link' );
