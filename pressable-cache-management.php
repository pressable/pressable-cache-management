<?php
/*
Plugin Name:  Pressable Cache Management
Description:  Pressable cache management made easy
Plugin URI:   https://pressable.com/knowledgebase/pressable-cache-management-plugin/#overview
Author:       Pressable CS Team
Version:      6.1.0
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 8.1
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
    require_once plugin_dir_path( __FILE__ ) . 'remove-old-mu-plugins.php';

    // Must load turn_on_off BEFORE purge (purge reuses pcm_edge_notice)
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/turn-on-off-edge-cache.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/purge-edge-cache.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush-object-cache.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/extend-batcache.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/object-cache-admin-bar.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush-batcache-for-woo-individual-page.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/exclude-pages-from-batcache.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush-batcache-for-particular-page.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush-cache-on-comment-delete.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/remove-pressable-branding.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/defensive-mode-edge-cache.php';
}

// ─── Front-end + admin cache flush triggers ──────────────────────────────────
require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush-cache-on-theme-plugin-update.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush-cache-on-page-edit.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush-cache-on-page-post-delete.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush-single-page-toolbar.php';

// ─── Settings link on plugin list page ──────────────────────────────────────
function pcm_settings_link( $links ) {
    $settings_link = '<a href="admin.php?page=pressable_cache_management">'
                   . esc_html__( 'Settings', 'pressable_cache_management' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pcm_settings_link' );
