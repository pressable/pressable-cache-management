<?php // Custom function - Add custom functions to flush cache on page, post and custom post_types on edit

// disable direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * This function is triggered when a post or page is edited in a WordPress site.
 * The function first flushes the cache using the "wp_cache_flush" function.
 * It then checks the type of post that was edited (either "post", "page" or "custom post_types") and sets
 * a time stamp for when the cache was last flushed.
 */

$options = get_option( 'pressable_cache_management_options' );

// Get checkbox options and check if is not empty
if ( isset( $options['flush_cache_page_edit_checkbox'] ) && ! empty( $options['flush_cache_page_edit_checkbox'] ) ) {
	if ( isset( $options['flush_cache_page_edit_checkbox'] ) && ! empty( $options['flush_cache_page_edit_checkbox'] ) ) {
		function clear_batcache_on_post_edit( $post_id, $post, $update ) {
			wp_cache_flush();
			$post_type               = $post->post_type;
			$post_type_obj           = get_post_type_object( $post_type );
			$post_type_name          = $post_type_obj
				->labels->singular_name;
			$object_cache_flush_time = date( ' jS F Y g:ia' ) . "\nUTC" . '<b> — cache flushed due to ' . $post_type_name . ' edit: ' . $post->post_title . '</b>';
			update_option( 'flush-cache-page-edit-time-stamp', $object_cache_flush_time );
		}
		add_action( 'save_post', 'clear_batcache_on_post_edit', 10, 3 );

	}
}
