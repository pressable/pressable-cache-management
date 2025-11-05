<?php
/**
 * Custom function - Add custom functions to flush cache when page or post is deleted.
 *
 * @package Pressable
 */

// Disable direct file access.
if ( ! defined( 'ABSPATH' ) ) {

	exit;

}


$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['flush_cache_on_page_post_delete_checkbox'] ) && ! empty( $options['flush_cache_on_page_post_delete_checkbox'] ) ) {

	/**
	 * Flush cache when a page or post is deleted.
	 *
	 * @param int     $post_ID     The post ID.
	 * @param WP_Post $post_after  The post after the update.
	 * @param WP_Post $post_before The post before the update.
	 */
	function fire_on_page_post_delete( $post_ID, $post_after, $post_before ) {
		if ( 'trash' === $post_after->post_status && 'publish' === $post_before->post_status ) {
			// Flush site cache if post or page is trashed after publishing.
			wp_cache_flush();
		}
		if ( 'publish' === $post_after->post_status && 'trash' === $post_before->post_status ) {
			// Flush site cache if post or page is published after being trash (post undelete).
			wp_cache_flush();
		}

		// Save time stamp to database if cache is flushed when a post or page was daleted.
		$object_cache_flush_time = gmdate( ' jS F Y  g:ia' ) . "\nUTC";
		update_option( 'flush-cache-on-page-post-delete-time-stamp', $object_cache_flush_time );

		// Set transient for admin notice for 9 seconds.
		set_transient( 'pcm-page-post-delete-notice', true, 9 );
	}
	add_action( 'post_updated', 'fire_on_page_post_delete', 10, 3 );

}
