<?php // Pressable Cache Management mu-plugins index


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}


/*****
 * //This file refference the Pressablce Cache Management mu-plugins 
 * https://wordpress.org/documentation/article/must-use-plugins/
 ******/



if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm_extend_batcache.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm_extend_batcache.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn_exclude_specific_file.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn_exclude_specific_file.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm_cdn_extender.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm_cdn_extender.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn_exclude_jpg_png_webp.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn_exclude_jpg_png_webp.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn_exclude_css.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn_exclude_css.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn_exclude_js_json.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/cdn_exclude_js_json.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm_exclude_font_files_from_cdn.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm_exclude_font_files_from_cdn.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm_cache_wpp_cookies_pages.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm_cache_wpp_cookies_pages.php';
}

if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm_exclude_query_string_gclid.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm_exclude_query_string_gclid.php';
}



if(file_exists(WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm_exclude_pages_from_batcache.php')) {
require WPMU_PLUGIN_DIR.'/pressable-cache-management/pcm_exclude_pages_from_batcache.php';
}
