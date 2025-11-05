<?php
/**
 * Pressable Cache Management  - Enable Caching for pages which has wpp_ cookies.
 *
 * @package Pressable
 */

// Disable direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['cache_wpp_cookies_pages'] ) && ! empty( $options['cache_wpp_cookies_pages'] ) ) {

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
	update_option( 'cache_wpp_cookies_pages', $options['cache_wpp_cookies_pages'] );

	$obj_cache_wpp_cookies_pages = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_cache_wpp_cookies_pages.php';
	if ( ! file_exists( $obj_cache_wpp_cookies_pages ) ) {
		$obj_cache_wpp_cookies_pages_src    = plugin_dir_path( __FILE__ ) . 'cache-wpp-cookie-page-mu-plugin.php';
		$obj_cache_wpp_cookies_pages_active = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_cache_wpp_cookies_pages.php';

		// Flush cache to enable activation take effect immediately.
		wp_cache_flush();

		if ( file_exists( $obj_cache_wpp_cookies_pages_src ) ) {
			copy( $obj_cache_wpp_cookies_pages_src, $obj_cache_wpp_cookies_pages_active );
		}
	}

	/**
	 * Display admin notice.
	 *
	 * @param string $message The message to display.
	 * @param string $classes The classes to apply to the notice.
	 */
	function cache_wpp_cookies_pages_admin_notice( $message = '', $classes = 'notice-success' ) {
		if ( ! empty( $message ) ) {
			printf( '<div class="notice %2$s">%1$s</div>', esc_html( $message ), esc_attr( $classes ) );
		}
	}

	/**
	 * Admin notice for caching wpp cookies pages.
	 */
	function pcm_cache_wpp_cookies_pages_admin_notice() {

		$cache_wpp_cookies_pages_activate_display_notice = get_option( 'cache_wpp_cookies_pages_activate_notice', 'activating' );

		if ( 'activating' === $cache_wpp_cookies_pages_activate_display_notice && current_user_can( 'manage_options' ) ) {

			add_action(
				'admin_notices',
				function () {

					$screen = get_current_screen();

					// Display admin notice for this plugin page only.
					if ( 'toplevel_page_pressable_cache_management' !== $screen->id ) {
						return;
					}

					$message = sprintf( '<p>Batcache will now cache pages with wpp_ cookies.</p>' );

					cache_wpp_cookies_pages_admin_notice( $message, 'notice notice-success is-dismissible' );
				}
			);

			update_option( 'cache_wpp_cookies_pages_activate_notice', 'activated' );

		}
	}
	add_action( 'init', 'pcm_cache_wpp_cookies_pages_admin_notice' );

} else {

	// Update option from the database if the option is deactivated used by admin notice to display and remove notice.
	update_option( 'cache_wpp_cookies_pages_activate_notice', 'activating' );

	$obj_cache_wpp_cookies_pages = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_cache_wpp_cookies_pages.php';
	if ( file_exists( $obj_cache_wpp_cookies_pages ) ) {
		wp_delete_file( $obj_cache_wpp_cookies_pages );

		// Flush cache to enable deactivation take effect immediately.
		wp_cache_flush();
	}
}
