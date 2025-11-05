<?php
/**
 * Pressable Cache Purge Adds a Cache Purge button to the admin bar.
 *
 * @package Pressable
 */

// Disable direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_bar_menu', 'cache_add_item', 100 );

/**
 * Add cache purge button to admin bar.
 *
 * @param WP_Admin_Bar $admin_bar The admin bar object.
 */
function cache_add_item( $admin_bar ) {
	if ( is_admin() ) {
		$admin_bar->add_menu(
			array(
				'id'    => 'cache-purge',
				'title' => 'Object Cache Purge',
				'href'  => '#',
			)
		);
	}
}

add_action( 'admin_footer', 'cache_purge_action_js' );

/**
 * Add javascript to admin footer.
 */
function cache_purge_action_js() {
	?>
	<script type="text/javascript">
		jQuery("li#wp-admin-bar-cache-purge .ab-item").on( "click", function() {
			var data = {
				'action': 'pressable_cache_purge',
			};

			jQuery.post(ajaxurl, data, function(response) {
				alert( response );
			});

		});
	</script>
	<?php
}

add_action( 'wp_ajax_pressable_cache_purge', 'pressable_cache_purge_callback' );

/**
 * Callback for cache purge action.
 */
function pressable_cache_purge_callback() {
	wp_cache_flush();

	// Save time stamp to database if cache is flushed.
	$object_cache_flush_time = gmdate( ' jS F Y  g:ia' ) . "\nUTC";

	update_option( 'flush-obj-cache-time-stamp', $object_cache_flush_time );
	$response = 'Object Cache Purged';
	echo esc_html( $response );
	wp_die();
}
