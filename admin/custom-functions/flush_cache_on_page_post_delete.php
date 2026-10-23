<?php // Custom function - Add custom functions to flush cache when page or post is deleted


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}


$options = get_option('pressable_cache_management_options');

if (isset($options['flush_cache_on_page_post_delete_checkbox']) && !empty($options['flush_cache_on_page_post_delete_checkbox']))
{

    function pcm_flush_cache_on_page_post_delete() {
        wp_cache_flush();

        // Save time stamp to database if cache is flushed when a post or page was deleted.
        update_option('flush-cache-on-page-post-delete-time-stamp', gmdate('j M Y, g:ia') . ' UTC');

        //Set transient for admin notice for 9 seconds
        set_transient('pcm-page-post-delete-notice', true, 9);
    }

    function fire_on_page_post_delete( $post_ID, $post_after, $post_before ) {
        // Published post or page trashed, or restored from the trash straight to published
        if ( ( $post_after->post_status == 'trash' && $post_before->post_status == 'publish' )
            || ( $post_after->post_status == 'publish' && $post_before->post_status == 'trash' ) ) {
            pcm_flush_cache_on_page_post_delete();
        }
    }
    add_action( 'post_updated', 'fire_on_page_post_delete', 10, 3 );

    // Published post or page deleted permanently without going to the trash
    // (trash disabled, "Delete Permanently", wp post delete --force, REST force=true).
    function pcm_fire_on_published_post_force_delete( $post_ID, $post ) {
        if ( $post instanceof WP_Post && 'publish' === $post->post_status && is_post_type_viewable( $post->post_type ) ) {
            pcm_flush_cache_on_page_post_delete();
        }
    }
    add_action( 'before_delete_post', 'pcm_fire_on_published_post_force_delete', 10, 2 );

 }
