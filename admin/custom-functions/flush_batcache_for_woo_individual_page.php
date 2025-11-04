<?php // Pressable Cache Management  - Flush Batcache for WooCommerce individual page

// disable direct file access
if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['flush_batcache_for_woo_product_individual_page_checkbox'] ) && ! empty( $options['flush_batcache_for_woo_product_individual_page_checkbox'] ) ) {

	// Add the option from the textbox into the database
	update_option( 'flush_batcache_for_woo_product_individual_page_checkbox', $options['flush_batcache_for_woo_product_individual_page_checkbox'] );

	// Declear variable so that it can be accessed from cdn_exclude_specific_file.php
	$flush_batcache_for_woo_product_individual_page = get_option( 'flush_batcache_for_woo_product_individual_page_checkbox' );

	// Create the pressable-cache-management mu-plugin index file
	// $pcm_mu_plugins_index = WP_CONTENT_DIR . '/mu-plugins/';
	// if (!file_exists($pcm_mu_plugins_index)) {
	// Copy pressable-cache-management.php from plugin directory to mu-plugins directory
	// copy( plugin_dir_path(__FILE__) . '/pressable_cache_management_mu_plugin_index.php', $pcm_mu_plugins_index);
	// }


	// // Check if the pressable-cache-management directory exists or create the folder
	// if (!file_exists(WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/')) {
	// create the directory
	// wp_mkdir_p(WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/');
	// }


	$flush_batcache_for_woo_product_individual_page = WP_CONTENT_DIR . '/mu-plugins/pcm_batcache_manager.php';
	if ( file_exists( $flush_batcache_for_woo_product_individual_page ) ) {

	} else {
		$flush_batcache_for_woo_product_individual_page        = plugin_dir_path( __FILE__ ) . '/pcm_batcache_manager.php';
		$flush_batcache_for_woo_product_individual_page_active = WP_CONTENT_DIR . '/mu-plugins/pcm_batcache_manager.php';

		// Flush cache to enable activation take effect immediately
		wp_cache_flush();

		if ( ! copy( $flush_batcache_for_woo_product_individual_page, $flush_batcache_for_woo_product_individual_page_active ) ) {

		} else {

		}
	}


		// Display admin notice
	function flush_batcache_for_woo_product_individual_page_admin_notice( $message = '', $classes = 'notice-success' ) {

		if ( ! empty( $message ) ) {
			printf( '<div class="notice %2$s">%1$s</div>', $message, $classes );
		}
	}

	function pcm_flush_batcache_for_woo_product_individual_page_admin_notice() {

		$flush_batcache_for_woo_product_individual_page_activate_display_notice = get_option( 'flush_batcache_for_woo_product_individual_page_activate_notice', 'activating' );

		if ( 'activating' === $flush_batcache_for_woo_product_individual_page_activate_display_notice && current_user_can( 'manage_options' ) ) {

			add_action(
				'admin_notices',
				function () {

					$screen = get_current_screen();

					// Display admin notice for this plugin page only
					if ( $screen->id !== 'toplevel_page_pressable_cache_management' ) {
						return;
					}

					$user    = $GLOBALS['current_user'];
					$message = sprintf( '<p>Automatically flush individual pages, including product pages updated via the WooCommerce API.</p>' );

					flush_batcache_for_woo_product_individual_page_admin_notice( $message, 'notice notice-success is-dismissible' );
				}
			);

			update_option( 'flush_batcache_for_woo_product_individual_page_activate_notice', 'activated' );

		}
	}
	add_action( 'init', 'pcm_flush_batcache_for_woo_product_individual_page_admin_notice' );



} else {


	/**Update option from the database if the connection is deactivated
	used by admin notice to display and remove notice*/
	update_option( 'flush_batcache_for_woo_product_individual_page_activate_notice', 'activating' );

	$flush_batcache_for_woo_product_individual_page = WP_CONTENT_DIR . '/mu-plugins/pcm_batcache_manager.php';
	if ( file_exists( $flush_batcache_for_woo_product_individual_page ) ) {
		unlink( $flush_batcache_for_woo_product_individual_page );

		// Flush cache to enable deactivation take effect immediately
		wp_cache_flush();
	} else {
		// File not found.

	}
}
