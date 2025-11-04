<?php
/**
 * Pressable Cache Management  - Exclude Google Ads URL's with query string gclid from Batcache.
 *
 * @package Pressable
 */

// Disable direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['exclude_query_string_gclid_checkbox'] ) && ! empty( $options['exclude_query_string_gclid_checkbox'] ) ) {

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

	// Declare variable so that it can be accessed from.
	$exclude_query_string_gclid = get_option( 'exclude_query_string_gclid' );

	// Exclude Google Ads URL's with query string gclid from Batcache.
	$obj_exclude_query_string_gclid = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_exclude_query_string_gclid.php';
	if ( ! file_exists( $obj_exclude_query_string_gclid ) ) {
		$obj_exclude_query_string_gclid_src    = plugin_dir_path( __FILE__ ) . 'exclude-query-string-gclid-from-cache-mu-plugin.php';
		$obj_exclude_query_string_gclid_active = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_exclude_query_string_gclid.php';

		// Flush cache to enable activation take effect immediately.
		wp_cache_flush();

		if ( file_exists( $obj_exclude_query_string_gclid_src ) ) {
			copy( $obj_exclude_query_string_gclid_src, $obj_exclude_query_string_gclid_active );
		}
	}

	/**
	 * Display admin notice.
	 *
	 * @param string $message The message to display.
	 * @param string $classes The classes to apply to the notice.
	 */
	function exclude_query_string_gclid_admin_notice( $message = '', $classes = 'notice-success' ) {
		if ( ! empty( $message ) ) {
			printf( '<div class="notice %2$s">%1$s</div>', esc_html( $message ), esc_attr( $classes ) );
		}
	}

	/**
	 * Admin notice for excluding gclid from cache.
	 */
	function pcm_exclude_query_string_gclid_admin_notice() {

		$exclude_query_string_gclid_activate_display_notice = get_option( 'exclude_query_string_gclid_activate_notice', 'activating' );

		if ( 'activating' === $exclude_query_string_gclid_activate_display_notice && current_user_can( 'manage_options' ) ) {

			add_action(
				'admin_notices',
				function () {

					$screen = get_current_screen();

					// Display admin notice for this plugin page only.
					if ( 'toplevel_page_pressable_cache_management' !== $screen->id ) {
						return;
					}

					$message = sprintf( '<p> Google Ads URL with query string (gclid) will be excluded from Batcache.</p>' );

					exclude_query_string_gclid_admin_notice( $message, 'notice notice-success is-dismissible' );
				}
			);

			update_option( 'exclude_query_string_gclid_activate_notice', 'activated' );

		}
	}
	add_action( 'init', 'pcm_exclude_query_string_gclid_admin_notice' );

} else {

	/**
	 * Update option from the database if the option is deactivated
	 * used by admin notice to display and remove notice
	 */
	update_option( 'exclude_query_string_gclid_activate_notice', 'activating' );

	$obj_exclude_query_string_gclid = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_exclude_query_string_gclid.php';
	if ( file_exists( $obj_exclude_query_string_gclid ) ) {
		wp_delete_file( $obj_exclude_query_string_gclid );

		// Flush cache to enable deactivation take effect immediately.
		wp_cache_flush();
	}
}
