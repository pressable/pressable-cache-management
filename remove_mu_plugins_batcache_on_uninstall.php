<?php //  Called by uninstall.php to remove all MU plugins and batcache configuration when plugin is uninstalled

// disable direct file access
if (!defined('ABSPATH'))
{
    exit;
}

/*
 * Remove mu-plugins added by Pressable cache management
*/

//Remove specific file mu-plugin
$cdn_exclude_specific_file = WP_CONTENT_DIR . '/mu-plugins/cdn_exclude_specific_file.php';
if (file_exists($cdn_exclude_specific_file))
{
    unlink($cdn_exclude_specific_file);
}

//Remove .css exclude mu-plugin
$cdn_exclude_css = WP_CONTENT_DIR . '/mu-plugins/cdn_exclude_css.php';
if (file_exists($cdn_exclude_css))
{
    unlink($cdn_exclude_css);
}

//Remove .img exclude mu-plugin
$cdn_exclude_jpg_png_webp = WP_CONTENT_DIR . '/mu-plugins/cdn_exclude_jpg_png_webp.php';
if (file_exists($cdn_exclude_jpg_png_webp))
{
    unlink($cdn_exclude_jpg_png_webp);

}

//Remove .json .js exclude mu-plugin
$cdn_exclude_js_json = WP_CONTENT_DIR . '/mu-plugins/cdn_exclude_js_json.php';
if (file_exists($cdn_exclude_js_json))
{
    unlink($cdn_exclude_js_json);
}

//Remove cdn extender mu-plugin
$cdn_extender_plugin_file = WP_CONTENT_DIR . '/mu-plugins/pcm_cdn_extender.php';
if (file_exists($cdn_extender_plugin_file))
{
    unlink($cdn_extender_plugin_file);
}

//Remove exclude page from batcache mu-plugin
$pcm_exclude_pages_from_batcache = WP_CONTENT_DIR . '/mu-plugins/pcm_exclude_pages_from_batcache.php';
if (file_exists($pcm_exclude_pages_from_batcache))
{
    unlink($pcm_exclude_pages_from_batcache);
}

//Remove extend batcache mu-plugin
$pcm_extend_batcache = WP_CONTENT_DIR . '/mu-plugins/pcm_extend_batcache.php';
if (file_exists($pcm_extend_batcache))
{
    unlink($pcm_extend_batcache );
}


//Remove cache wpp_ cookies pages mu-plugin
$pcm_cache_wpp_cookies_pages = WP_CONTENT_DIR . '/mu-plugins/pcm_cache_wpp_cookies_pages.php';
if (file_exists($pcm_cache_wpp_cookies_pages))
{
    unlink($pcm_cache_wpp_cookies_pages);
}

//Todo:
//Remove pcm_exclude_font_files_from_cdn mu-plugin
// $pcm_exclude_font_files_from_cdn = WP_CONTENT_DIR . '/mu-plugins/pcm_exclude_font_files_from_cdn.php';
// if (file_exists($pcm_exclude_font_files_from_cdn))
// {
//     unlink($pcm_exclude_font_files_from_cdn);
// }

//Remove pcm_exclude_query_string_gclid mu-plugin
$pcm_exclude_query_string_gclid = WP_CONTENT_DIR . '/mu-plugins/pcm_exclude_query_string_gclid.php';
if (file_exists($pcm_exclude_query_string_gclid))
{
    unlink($pcm_exclude_query_string_gclid);
}
