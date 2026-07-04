<?php // Pressable Cache Management  - Flush cache when comment is deleted

$options = get_option('pressable_cache_management_options');


if (isset($options['flush_cache_on_comment_delete_checkbox']) && !empty($options['flush_cache_on_comment_delete_checkbox']))
{

add_action( 'trash_comment', 'pcm_trash_comment_action', 10, 2 );

/**
 * Function for `trash_comment` action-hook.
 * 
 * @param string     $comment_id The comment ID as a numeric string.
 * @param WP_Comment $comment    The comment to be trashed.
 *
 * @return void
 */
function pcm_trash_comment_action( $comment_id, $comment ){

	 wp_cache_flush();
	
		//Save time stamp to database if cache is flushed when comment is deleted.
        $object_cache_flush_time = date(' jS F Y  g:ia') . "\nUTC";
        update_option('flush-cache-on-comment-delete-time-stamp', $object_cache_flush_time);
   }
	
}
