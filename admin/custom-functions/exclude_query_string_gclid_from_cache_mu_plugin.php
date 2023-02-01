<?php // Pressable Cache Management - Exclude Google Ads URL with query string gclid from Batcache

// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

/**
* Batcache by default will create a new cached page for each query parameter
* But we want to ignore Google Ads with the URL Param of gclid
**/

if (!function_exists('exclude_gclid_from_batcache')) {
  function exclude_gclid_from_batcache() {
    global $batcache;
    if ( is_object( $batcache ) ) {
        $batcache->ignored_query_args = array( 'gclid' );
		echo 'Yes';
    }
  }
}

add_action( 'plugins_loaded', 'exclude_gclid_from_batcache' );
