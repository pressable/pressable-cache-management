<?php
/**
 * Pressable Cache Management - Shared Batcache URL helpers
 *
 * Used by the post-list "Flush Cache" link, the toolbar "Flush Cache for This
 * Page" button and "Flush Cache on Post/Page Edit".
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Invalidate the Batcache copy of one URL by bumping its version key.
 *
 * @param string $url Full URL of the page.
 * @return string|false The http:// URL that was flushed, or false when Batcache is unavailable.
 */
function pcm_flush_batcache_url( $url ) {
    global $batcache, $wp_object_cache;

    if ( ! isset( $batcache ) || ! is_object( $batcache ) || ! method_exists( $wp_object_cache, 'incr' ) ) {
        return false;
    }

    $batcache->configure_groups();

    $url = apply_filters( 'batcache_manager_link', $url );
    if ( empty( $url ) ) {
        return false;
    }

    do_action( 'batcache_manager_before_flush', $url );

    // Batcache keys off the http:// version of the URL
    $url     = set_url_scheme( $url, 'http' );
    $url_key = md5( $url );

    wp_cache_add( "{$url_key}_version", 0, $batcache->group );
    $version = wp_cache_incr( "{$url_key}_version", 1, $batcache->group );

    // The version key must reach remote caches even when the Batcache group is local-only
    if ( property_exists( $wp_object_cache, 'no_remote_groups' ) ) {
        $k = array_search( $batcache->group, (array) $wp_object_cache->no_remote_groups, true );
        if ( false !== $k ) {
            unset( $wp_object_cache->no_remote_groups[ $k ] );
            wp_cache_set( "{$url_key}_version", $version, $batcache->group );
            $wp_object_cache->no_remote_groups[ $k ] = $batcache->group;
        }
    }

    do_action( 'batcache_manager_after_flush', $url, $version );

    return $url;
}

/**
 * Build a full URL for a request path on this site.
 *
 * The path comes from window.location.pathname, so it already holds any
 * subdirectory the site lives in; prefixing home_url() would repeat it.
 *
 * @param string $path Request path, e.g. /blog/about/.
 * @return string
 */
function pcm_site_url_for_path( $path ) {
    $home = wp_parse_url( home_url() );
    return $home['scheme'] . '://' . $home['host'] . ( isset( $home['port'] ) ? ':' . $home['port'] : '' ) . $path;
}
