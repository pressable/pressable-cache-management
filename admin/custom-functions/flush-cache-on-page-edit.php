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

        global $batcache, $wp_object_cache;

        // Batcache must be loaded and the object cache must support incr()
        if ( ! isset( $batcache ) || ! is_object( $batcache ) || ! method_exists( $wp_object_cache, 'incr' ) ) {
            return;
        }

        $batcache->configure_groups();

        $url = apply_filters( 'batcache_manager_link', $url );
        if ( empty( $url ) ) {
            return;
        }

        do_action( 'batcache_manager_before_flush', $url );

        // Batcache keys off the http:// version of the URL
        $url     = set_url_scheme( $url, 'http' );
        $url_key = md5( $url );

        // Increment the version key — Batcache treats the cached copy as stale
        wp_cache_add( "{$url_key}_version", 0, $batcache->group );
        wp_cache_incr( "{$url_key}_version", 1, $batcache->group );

        // Handle sites where the Batcache group is excluded from remote sync
        if ( property_exists( $wp_object_cache, 'no_remote_groups' ) ) {
            $k = array_search( $batcache->group, (array) $wp_object_cache->no_remote_groups );
            if ( false !== $k ) {
                unset( $wp_object_cache->no_remote_groups[ $k ] );
                wp_cache_set( "{$url_key}_version", $batcache->group );
                $wp_object_cache->no_remote_groups[ $k ] = $batcache->group;
            }
        }

        do_action( 'batcache_manager_after_flush', $url );

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

}
