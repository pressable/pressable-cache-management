<?php // Pressable Cache Management - Cache pages which sets wpp_ cookies

// disable direct file access
if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

/**
 * Batcache by default ignore all cookies starting with wp so
 * we have to add cookies to skip list if we want batcache to
 * cache certain pages with cookies.
 *
 * Wonder plugin sets cookies starting with wpp which was preventing pages
 * getting cached. We collect all the cookies starting with wpp_ below
 * and adds it to the list that can be cached
 */

$all_wpp_cookies = array();
if ( is_array( $_COOKIE ) && ! empty( $_COOKIE ) ) {
	foreach ( array_keys( $_COOKIE ) as $maybe_wpp ) {
		if ( substr( $maybe_wpp, 0, 4 ) == 'wpp_' ) {
			$all_wpp_cookies[] = $maybe_wpp;
		}
	}
}

// Only add cookies to noskip if we found any starting with wpp_
// The wordpress_test_cookies is the default one
if ( count( $all_wpp_cookies ) > 0 ) {
	global $batcache;
	$batcache['noskip_cookies'] = array_merge( array( 'wordpress_test_cookie' ), $all_wpp_cookies );
}
