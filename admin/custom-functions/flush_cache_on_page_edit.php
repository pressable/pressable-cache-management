<?php
/**
 * Pressable Cache Management — Flush Batcache for the individual page/post on edit.
 *
 * Instead of flushing the entire object cache on every save, this targets only
 * the Batcache entry for the URL of the post that was just saved — the same
 * technique used by flush_batcache_for_particular_page.php (column link) and
 * flush_single_page_toolbar.php (toolbar button).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['flush_cache_page_edit_checkbox'] ) && ! empty( $options['flush_cache_page_edit_checkbox'] ) ) {

    /**
     * Flush Batcache only for the URL of the post that was just saved.
     *
     * Fires on save_post (covers pages, posts, and all custom post types,
     * including WooCommerce products saved via the REST API).
     *
     * @param int     $post_id  The saved post ID.
     * @param WP_Post $post     The saved post object.
     * @param bool    $update   True if this is an update, false for a new post.
     */
    function pcm_flush_batcache_on_page_edit( $post_id, $post, $update ) {

        // Skip auto-saves, revisions, and non-published posts
        if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
            return;
        }
        if ( $post->post_status !== 'publish' ) {
            return;
        }

        // Get the public URL for this post
        $url = get_permalink( $post_id );
        if ( empty( $url ) ) {
            return;
        }

        $url = pcm_flush_batcache_url( $url );
        if ( ! $url ) {
            return;
        }

        // Record the flush for display on the settings page
        $post_type_obj  = get_post_type_object( $post->post_type );
        $post_type_name = $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type;

        $stamp = gmdate( 'j M Y, g:ia' ) . ' UTC'
               . '<b> — cache flushed for ' . esc_html( $post_type_name )
               . ' edit: ' . esc_html( $post->post_title ) . '</b>';

        update_option( 'flush-cache-page-edit-time-stamp', $stamp );
        // Also write the flushed URL so the settings page can show it
        update_option( 'single-page-url-flushed', $url );
    }

    add_action( 'save_post', 'pcm_flush_batcache_on_page_edit', 10, 3 );

    /**
     * Flush the URL a published post had before this update when that URL no
     * longer serves it: the post was unpublished, trashed, or its slug changed.
     * save_post only sees the new URL, so the old cached copy stayed live.
     *
     * @param int     $post_id     The post ID.
     * @param WP_Post $post_after  The post after the update.
     * @param WP_Post $post_before The post before the update.
     */
    function pcm_flush_batcache_for_old_permalink( $post_id, $post_after, $post_before ) {
        if ( 'publish' !== $post_before->post_status ) {
            return;
        }

        $old_url = get_permalink( $post_before );
        if ( $old_url && $old_url !== get_permalink( $post_after ) ) {
            pcm_flush_batcache_url( $old_url );
        }
    }

    add_action( 'post_updated', 'pcm_flush_batcache_for_old_permalink', 10, 3 );

}
