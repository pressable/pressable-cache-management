<?php
/**
 * Plugin Name:  Pressable Cache Management
 * Description:  Pressable cache management made easy
 * Plugin URI:   https://pressable.com/knowledgebase/pressable-cache-management-plugin/#overview
 * Author:       Pressable CS Team
 * Version:      5.2.3
 * Requires at least: 5.0
 * Tested up to: 6.0
 * Requires PHP: 7.4
 * Text Domain: pressable_cache_management
 * Domain Path: /languages
 * License:      GPL v2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package Pressable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'IS_PRESSABLE' ) ) {
	add_action( 'admin_notices', 'pcm_auto_deactivation_notice' );
	add_action( 'admin_init', 'deactivate_plugin_if_not_pressable' );
}

/**
 * Deactivate the plugin if the site is not on the Pressable platform.
 */
function deactivate_plugin_if_not_pressable() {
	deactivate_plugins( plugin_basename( __FILE__ ) );
}

/**
 * Display a notice and deactivate the plugin if the site is not on the Pressable platform.
 */
function pcm_auto_deactivation_notice() {
	$msg  = '<div style="margin:50px 20px 20px 0;background-color:white;border:1px solid #c3c4c7;border-top-color:#d63638;border-top-width:5px;padding:20px;">';
	$msg .= '<h3 style="margin-top:0;color:#d63638;font-weight:900;">' . __( 'Attention! ', 'pressable_cache_management' ) . '</h3>';
	$msg .= '<p>' . __( 'This plugin is not supported on this platform.', 'pressable_cache_management' ) . '</p>';
	$msg .= '</div>';
	echo wp_kses_post( $msg );
}

/**
 * Load the plugin's text domain.
 */
function pressable_cache_management_load_textdomain() {
	load_plugin_textdomain( 'pressable_cache_management', false, plugin_dir_path( __FILE__ ) . 'languages/' );
}
add_action( 'plugins_loaded', 'pressable_cache_management_load_textdomain' );

if ( is_admin() ) {
	// Admin pages.
	require_once plugin_dir_path( __FILE__ ) . 'admin/admin-menu.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/settings-register.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/settings-callbacks.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/settings-validate.php';
	require_once plugin_dir_path( __FILE__ ) . 'remove-old-mu-plugins.php';

	// Cache related custom functions.
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/extend-batcache.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/turn-on-off-edge-cache.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/purge-edge-cache.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush-object-cache.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/object-cache-admin-bar.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush-batcache-for-woo-individual-page.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/exclude-pages-from-batcache.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/class-pressable-flush-object-cache-page-column.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush-cache-on-comment-delete.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/remove-pressable-branding.php';
}

	// Flush and page edit related functions.
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush-cache-on-theme-plugin-update.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush-cache-on-page-edit.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/flush-cache-on-page-post-delete.php';
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/class-pressable-flush-single-page-toolbar.php';

/**
 * Add a settings link to the plugin page.
 *
 * @param array $links An array of plugin action links.
 * @return array An array of plugin action links.
 */
function pcm_settings_link( $links ) {
	$settings_link = '<a href="admin.php?page=pressable_cache_management">Settings</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
$pcm_plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$pcm_plugin", 'pcm_settings_link' );
