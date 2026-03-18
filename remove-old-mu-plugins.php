<?php
/**
 * Pressable Cache Managemenet - Remove mu-plugins added by the old version of the plugin.
 *
 * @package Pressable
 */

// Disable direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// Check if this particular plugin  version is updated.
$current_version = '3.4.4';
if ( version_compare( $current_version, '3.4.4', '>=' ) ) {

	// Library that writes/removes the batcache function to wp-config.php.
	require_once plugin_dir_path( __FILE__ ) . 'admin/custom-functions/wp-write-to-file-lib.php';

	/*
	 * Remove Batcache extending.
	 * Remove Batcache extending from wp-config.php file.
	*/

	$delete_config_file = true;

	global $wp_rewrite;
	$global_config_file = file_exists( ABSPATH . 'wp-config.php' ) ? ABSPATH . 'wp-config.php' : dirname( ABSPATH ) . '/wp-config.php';

	if ( apply_filters( 'wpsc_enable_wp_config_edit', true ) ) {
		$line = 'global $batcache; if ( is_object($batcache) ) { $batcache->max_age = 86400; $batcache->seconds = 3600;  };';
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( strpos( file_get_contents( $global_config_file ), $line ) !== false && ( ! is_writeable_wp_config( $global_config_file ) || ! wp_config_file_replace_line( 'global *\$batcache; if *\( *is_object', '', $global_config_file ) ) ) {
			wp_die( esc_html( "Could not remove Extending Batcache settings from $global_config_file. Please edit that file and remove the line containing the function 'global  $batcache;'. Then refresh this page. orcontact Pressable Support for help" ) );
		}
	}
}

/**
 * Removes mu-plugins from the old mu-plugins directory
 * to prevent any conflict due to the new mu-plugins folder
 * which is created to store mu-plugins
 */

// ── Legacy root-level mu-plugins (CDN era) ───────────────────────────────────
$mu_plugins = array( 'cdn_exclude_specific_file.php', 'cdn_exclude_css.php', 'cdn_exclude_jpg_png_webp.php', 'cdn_exclude_js_json.php', 'cdn_extender.php' );

// ── Legacy root-level mu-plugin (underscore era, replaced by pcm-batcache-manager.php) ─
$mu_plugins[] = 'pcm_batcache_manager.php';

global $wp_filesystem;
if ( empty( $wp_filesystem ) ) {
	require_once ABSPATH . '/wp-admin/includes/file.php';
	WP_Filesystem();
}

// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
foreach ( $mu_plugins as $mu_plugin ) {
	$file = WP_CONTENT_DIR . '/mu-plugins/' . $mu_plugin;
	if ( $wp_filesystem->exists( $file ) ) {
		$wp_filesystem->delete( $file );
	}
}

// ── Legacy underscore-named files inside mu-plugins/pressable-cache-management/ ─
// Prior to v6.1.1 these were deployed with underscores; now deployed with hyphens.
// Remove the old names so both versions don't load simultaneously on upgraded installs.
$legacy_sub_mu_plugins = array(
	'pcm_extend_batcache.php',
	'pcm_exclude_pages_from_batcache.php',
	'pcm_exclude_query_string_gclid.php',
	'pcm_cache_wpp_cookies_pages.php',
);

$sub_dir = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management';
if ( $wp_filesystem->is_dir( $sub_dir ) ) {
	foreach ( $legacy_sub_mu_plugins as $legacy_file ) {
		$path = $sub_dir . '/' . $legacy_file;
		if ( $wp_filesystem->exists( $path ) ) {
			$wp_filesystem->delete( $path );
		}
	}
}
