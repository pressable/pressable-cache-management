<?php
/**
 * Pressable Cache Management  - Flush Batcache for WooCommerce individual page.
 *
 * @package Pressable
 */

// Disable direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['flush_batcache_for_woo_product_individual_page_checkbox'] ) && ! empty( $options['flush_batcache_for_woo_product_individual_page_checkbox'] ) ) {

	// Add the option from the textbox into the database.
	update_option( 'flush_batcache_for_woo_product_individual_page_checkbox', $options['flush_batcache_for_woo_product_individual_page_checkbox'] );

	// Declare variable so that it can be accessed from cdn_exclude_specific_file.php.
	$flush_batcache_for_woo_product_individual_page = get_option( 'flush_batcache_for_woo_product_individual_page_checkbox' );

	$batcache_manager_file_path = WP_CONTENT_DIR . '/mu-plugins/class-batcache-manager.php';
	if ( ! file_exists( $batcache_manager_file_path ) ) {
		$source_file = plugin_dir_path( __FILE__ ) . 'class-batcache-manager.php';
		if ( file_exists( $source_file ) ) {
			// Flush cache to enable activation take effect immediately.
			wp_cache_flush();
			copy( $source_file, $batcache_manager_file_path );
		}
	}

	/**
	 * Display admin notice.
	 *
	 * @param string $message The message to display.
	 * @param string $classes The classes to apply to the notice.
	 */
	function flush_batcache_for_woo_product_individual_page_admin_notice( $message = '', $classes = 'notice-success' ) {
		if ( ! empty( $message ) ) {
			printf( '<div class="notice %2$s">%1$s</div>', esc_html( $message ), esc_attr( $classes ) );
		}
	}

	/**
	 * Admin notice for flushing batcache for woo product individual page.
	 */
	function pcm_flush_batcache_for_woo_product_individual_page_admin_notice() {

		$flush_batcache_for_woo_product_individual_page_activate_display_notice = get_option( 'flush_batcache_for_woo_product_individual_page_activate_notice', 'activating' );

		if ( 'activating' === $flush_batcache_for_woo_product_individual_page_activate_display_notice && current_user_can( 'manage_options' ) ) {

			add_action(
				'admin_notices',
				function () {

					$screen = get_current_screen();

					// Display admin notice for this plugin page only.
					if ( 'toplevel_page_pressable_cache_management' !== $screen->id ) {
						return;
					}

					$message = sprintf( '<p>Automatically flush individual pages, including product pages updated via the WooCommerce API.</p>' );

					flush_batcache_for_woo_product_individual_page_admin_notice( $message, 'notice notice-success is-dismissible' );
				}
			);

			update_option( 'flush_batcache_for_woo_product_individual_page_activate_notice', 'activated' );

		}
	}
	add_action( 'init', 'pcm_flush_batcache_for_woo_product_individual_page_admin_notice' );

} else {

	/**
	 * Update option from the database if the connection is deactivated
	 * used by admin notice to display and remove notice
	 */
	update_option( 'flush_batcache_for_woo_product_individual_page_activate_notice', 'activating' );

	$batcache_manager_file_path = WP_CONTENT_DIR . '/mu-plugins/class-batcache-manager.php';
	if ( file_exists( $batcache_manager_file_path ) ) {
		wp_delete_file( $batcache_manager_file_path );

		// Flush cache to enable deactivation take effect immediately.
		wp_cache_flush();
	}
}
