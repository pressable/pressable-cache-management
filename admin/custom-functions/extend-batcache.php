<?php
/**
 * Pressable Cache Management  - Extend batcache by 24 hours.
 *
 * @package Pressable
 */

// Disable direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['extend_batcache_checkbox'] ) && ! empty( $options['extend_batcache_checkbox'] ) ) {

	// Add the option from the textbox into the database.
	update_option( 'extend_batcache_checkbox', $options['extend_batcache_checkbox'] );

	// Declare variable so that it can be accessed from cdn_exclude_specific_file.php.
	$extend_batcache = get_option( 'extend_batcache_checkbox' );

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

	$obj_extend_batcache = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_extend_batcache.php';
	if ( ! file_exists( $obj_extend_batcache ) ) {
		$obj_extend_batcache_src    = plugin_dir_path( __FILE__ ) . 'extend-batcache-mu-plugin.php';
		$obj_extend_batcache_active = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_extend_batcache.php';

		// Flush cache to enable activation take effect immediately.
		wp_cache_flush();

		if ( file_exists( $obj_extend_batcache_src ) ) {
			copy( $obj_extend_batcache_src, $obj_extend_batcache_active );
		}
	}

	/**
	 * Display admin notice.
	 *
	 * @param string $message The message to display.
	 * @param string $classes The classes to apply to the notice.
	 */
	function extend_batcache_admin_notice( $message = '', $classes = 'notice-success' ) {
		if ( ! empty( $message ) ) {
			printf( '<div class="notice %2$s">%1$s</div>', esc_html( $message ), esc_attr( $classes ) );
		}
	}

	/**
	 * Admin notice for extending batcache.
	 */
	function pcm_extend_batcache_admin_notice() {

		$extend_batcache_activate_display_notice = get_option( 'extend_batcache_activate_notice', 'activating' );

		if ( 'activating' === $extend_batcache_activate_display_notice && current_user_can( 'manage_options' ) ) {

			add_action(
				'admin_notices',
				function () {

					$screen = get_current_screen();

					// Display admin notice for this plugin page only.
					if ( 'toplevel_page_pressable_cache_management' !== $screen->id ) {
						return;
					}

					$message = sprintf( '<p>Extending Batcache for 24 hours see <a href="https://pressable.com/knowledgebase/modifying-cache-times-batcache/"> Modifying Batcache Times.</a></p>' );

					extend_batcache_admin_notice( $message, 'notice notice-success is-dismissible' );
				}
			);

			update_option( 'extend_batcache_activate_notice', 'activated' );

		}
	}
	add_action( 'init', 'pcm_extend_batcache_admin_notice' );

} else {

	/**
	 * Update option from the database if the connection is deactivated
	 * used by admin notice to display and remove notice
	 */
	update_option( 'extend_batcache_activate_notice', 'activating' );

	$obj_extend_batcache = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_extend_batcache.php';
	if ( file_exists( $obj_extend_batcache ) ) {
		wp_delete_file( $obj_extend_batcache );

		// Flush cache to enable deactivation take effect immediately.
		wp_cache_flush();
	}
}
