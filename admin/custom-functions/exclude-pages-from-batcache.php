<?php
/**
 * Pressable Cache Management  - Exclude pages from Batcache.
 *
 * @package Pressable
 */

// Disable direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['exempt_from_batcache'] ) && ! empty( $options['exempt_from_batcache'] ) ) {

	// Create the pressable-cache-management mu-plugin index file.
	$pcm_mu_plugins_index = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management.php';
	if ( ! file_exists( $pcm_mu_plugins_index ) ) {
		// Copy pressable-cache-management.php from plugin directory to mu-plugins directory.
		copy( plugin_dir_path( __FILE__ ) . 'pressable-cache-management-mu-plugin-index.php', $pcm_mu_plugins_index );
	}

	// Check if the pressable-cache-management directory exists or create the folder.
	if ( ! file_exists( WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/' ) ) {
		// Create the directory.
		wp_mkdir_p( WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/' );
	}

	// Add the option from the textbox into the database.
	update_option( 'exempt_from_batcache', $options['exempt_from_batcache'] );

	// Exclude pages from Batcache.
	$obj_exclude_pages_from_batcache = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_exclude_pages_from_batcache.php';
	if ( ! file_exists( $obj_exclude_pages_from_batcache ) ) {
		$obj_exclude_pages_from_batcache_src    = plugin_dir_path( __FILE__ ) . 'exclude-pages-from-batcache-mu-plugin.php';
		$obj_exclude_pages_from_batcache_active = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_exclude_pages_from_batcache.php';

		// Flush cache to enable activation take effect immediately.
		wp_cache_flush();

		if ( file_exists( $obj_exclude_pages_from_batcache_src ) ) {
			copy( $obj_exclude_pages_from_batcache_src, $obj_exclude_pages_from_batcache_active );
		}
	}
} else {
	$obj_exclude_pages_from_batcache = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_exclude_pages_from_batcache.php';
	if ( file_exists( $obj_exclude_pages_from_batcache ) ) {
		wp_delete_file( $obj_exclude_pages_from_batcache );

		// Flush cache to enable deactivation take effect immediately.
		wp_cache_flush();
	}
}
