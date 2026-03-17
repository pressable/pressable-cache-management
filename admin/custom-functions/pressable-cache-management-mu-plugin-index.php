<?php // Pressable Cache Management mu-plugins index


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}


/*****
 * This file references the Pressable Cache Management mu-plugins 
 * https://wordpress.org/documentation/article/must-use-plugins/
 ******/



if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm-extend-batcache.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm-extend-batcache.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn-exclude-specific-file.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn-exclude-specific-file.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm-cdn-extender.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm-cdn-extender.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn-exclude-jpg-png-webp.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn-exclude-jpg-png-webp.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn-exclude-css.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn-exclude-css.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn-exclude-js-json.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn-exclude-js-json.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm-exclude-font-files-from-cdn.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm-exclude-font-files-from-cdn.php';
}

// if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/batcache_manager.php')) {
// require WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm-batcache-manager.php.php';
// }

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm-cache-wpp-cookies-pages.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm-cache-wpp-cookies-pages.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm-exclude-query-string-gclid.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm-exclude-query-string-gclid.php';
}



if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm-exclude-pages-from-batcache.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm-exclude-pages-from-batcache.php';
}
