<?php // Extend Batcache for Pressable site

if ( ! defined( 'IS_PRESSABLE' ) ) {
		return;
}

// Batcache Customizations
global $batcache;

// Check is batcache params are in an object or an array, apply customizations accordingly
if ( is_object( $batcache ) ) {
	$batcache->max_age = 86400; // Seconds the cached render of a page will be stored
	$batcache->seconds = 1200; // The amount of time at least 2 people are required to
} elseif ( is_array( $batcache ) ) {
	$batcache['max_age'] = 86400; // Seconds the cached render of a page will be stored
	$batcache['seconds'] = 1200;// The amount of time at least 2 people are required to
}
