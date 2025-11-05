<?php
/**
 * Pressable Cache Management  - Flush cache when comment is deleted.
 *
 * @package Pressable
 */

$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['flush_cache_on_comment_delete_checkbox'] ) && ! empty( $options['flush_cache_on_comment_delete_checkbox'] ) ) {

	add_action( 'trash_comment', 'pcm_trash_comment_action', 10, 0 );

	/**
	 * Function for `trash_comment` action-hook.
	 */
	function pcm_trash_comment_action() {

		wp_cache_flush();

		// Save time stamp to database if cache is flushed when comment is deleted.
		$object_cache_flush_time = gmdate( ' jS F Y  g:ia' ) . "\nUTC";
		update_option( 'flush-cache-on-comment-delete-time-stamp', $object_cache_flush_time );
	}

}
