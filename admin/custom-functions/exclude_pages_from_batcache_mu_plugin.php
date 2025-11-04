<?php
// Plugin Name: Exclude website pages from the Batcache and Edge Cache

if ( ! defined( 'IS_PRESSABLE' ) ) {
	return;
}

add_action( 'init', 'cancel_the_cache' );

function cancel_the_cache() {
	if ( ! function_exists( 'batcache_cancel' ) ) {
		return;
	}

	$options        = get_option( 'pressable_cache_management_options' );
	$exempted_pages = isset( $options['exempt_from_batcache'] ) ? $options['exempt_from_batcache'] : '';

	if ( empty( $exempted_pages ) ) {
		return;
	}

	// Convert stored options into an array and trim spaces
	$exempted_pages = array_map( 'trim', explode( ',', $exempted_pages ) );

	// Get current URI without query parameters
	$uri = strtok( $_SERVER['REQUEST_URI'], '?' );

	// Always exclude homepage if listed or explicitly requested
	if ( $uri === '/' && in_array( '/', $exempted_pages ) ) {
		batcache_cancel();
		disable_edge_cache();
		return;
	}

	// Loop through exempted pages
	foreach ( $exempted_pages as $page ) {
		// Match exact page or paginated versions (e.g., /about/, /about/page/2/)
		if ( $uri === $page || preg_match( '#^' . preg_quote( $page, '#' ) . '(/page/\d+/?)?$#i', $uri ) ) {
			batcache_cancel();
			disable_edge_cache();
			return;
		}
	}
}

function disable_edge_cache() {
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
}
