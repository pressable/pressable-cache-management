<?php
/**
 * Called by uninstall.php to remove all Pressable Cache Management MU plugins when plugin is uninstalled.
 *
 * @package Pressable
 */

// Disable direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Remove Pressable Cache Management mu-plugins.
 */

global $wp_filesystem;
if ( empty( $wp_filesystem ) ) {
	require_once ABSPATH . '/wp-admin/includes/file.php';
	WP_Filesystem();
}

// Remove Pressable Cache Management mu-plugin index.
$pcm_mu_plugin_index = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management.php';
if ( $wp_filesystem->exists( $pcm_mu_plugin_index ) ) {
	$wp_filesystem->delete( $pcm_mu_plugin_index );
}

$pcm_cache_mu_plugins = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management';
if ( $wp_filesystem->exists( $pcm_cache_mu_plugins ) ) {
	rrmdir( $pcm_cache_mu_plugins );
}

/**
 * Recursively removes a directory.
 *
 * @param string $dir Directory to remove.
 */
function rrmdir( $dir ) {
	global $wp_filesystem;
	if ( ! $wp_filesystem->is_dir( $dir ) ) {
		return;
	}
	$objects = $wp_filesystem->dirlist( $dir );
	if ( ! empty( $objects ) ) {
		foreach ( $objects as $object ) {
			if ( 'f' === $object['type'] ) {
				$wp_filesystem->delete( $dir . '/' . $object['name'] );
			} elseif ( 'd' === $object['type'] ) {
				rrmdir( $dir . '/' . $object['name'] );
			}
		}
	}
	$wp_filesystem->rmdir( $dir );
}


// Remove batcache manager plugin from mu-plugins directory.
$mu_plugins = array( 'class-batcache-manager.php' );

// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
foreach ( $mu_plugins as $mu_plugin ) {
	$file = WP_CONTENT_DIR . '/mu-plugins/' . $mu_plugin;
	if ( $wp_filesystem->exists( $file ) ) {
		$wp_filesystem->delete( $file );
	}
}
