<?php //  Called by uninstall.php to remove all MU plugins and batcache configuration when plugin is uninstalled
// disable direct file access
if (!defined('ABSPATH'))
{
    exit;
}

//library that writes the batache function to wp-config.php
require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/wp-write-to-file-lib.php';

/*
 * Remove Batcache extending
 * Remove Batcache extending from wp-config.php file
*/

$delete_config_file = true;

global $wp_rewrite;
if (file_exists(ABSPATH . 'wp-config.php'))
{
    $global_config_file = ABSPATH . 'wp-config.php';

}
else
{
    $global_config_file = dirname(ABSPATH) . '/wp-config.php';
}

if (apply_filters('wpsc_enable_wp_config_edit', true))
{
    $line = 'global $batcache; if ( is_object($batcache) ) { $batcache->max_age = 86400; $batcache->seconds = 3600;  };';
    if (strpos(file_get_contents($global_config_file) , $line) && (!is_writeable_wp_config($global_config_file) || !wp_config_file_replace_line('global *\$batcache; if *\( *is_object', '', $global_config_file)))
    {
        wp_die("Could not remove Extending Batcache settings from $global_config_file. Please edit that file and remove the line containing the function 'global  $batcache;'. Then refresh this page. orcontact Pressable Support for help");
    }

    $line = 'global $batcache; if ( is_object($batcache) ) { $batcache->max_age = 86400; $batcache->seconds = 3600;  };';
    if (strpos(file_get_contents($global_config_file) , $line) && (!is_writeable_wp_config($global_config_file) || !wp_config_file_replace_line('global  *\$batcache; if *\( *is_object', '', $global_config_file)))
    {
        wp_die("Could not remove Extending Batcache settings from $global_config_file. Please edit that file and remove the line containing the function 'global  $batcache;'. Then refresh this page. orcontact Pressable Support for help");
    }

}

//  Called by uninstall.php to remove batache configuration
// disable direct file access
if (!defined('ABSPATH'))
{
    exit;
}

//library that writes the batache function to wp-config.php
require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/wp-write-to-file-lib.php';

/*
 * Remove Batcache extending
 * Remove Batcache extending from wp-config.php file
*/

$delete_config_file = true;

global $wp_rewrite;
if (file_exists(ABSPATH . 'wp-config.php'))
{
    $global_config_file = ABSPATH . 'wp-config.php';

}
else
{
    $global_config_file = dirname(ABSPATH) . '/wp-config.php';
}

if (apply_filters('wpsc_enable_wp_config_edit', true))
{
    $line = 'global $batcache; if ( is_object($batcache) ) { $batcache->max_age = 86400; $batcache->seconds = 3600;  };';
    if (strpos(file_get_contents($global_config_file) , $line) && (!is_writeable_wp_config($global_config_file) || !wp_config_file_replace_line('global *\$batcache; if *\( *is_object', '', $global_config_file)))
    {
        wp_die("Could not remove Extending Batcache settings from $global_config_file. Please edit that file and remove the line containing the function 'global  $batcache;'. Then refresh this page. orcontact Pressable Support for help");
    }

    $line = 'global $batcache; if ( is_object($batcache) ) { $batcache->max_age = 86400; $batcache->seconds = 3600;  };';
    if (strpos(file_get_contents($global_config_file) , $line) && (!is_writeable_wp_config($global_config_file) || !wp_config_file_replace_line('global  *\$batcache; if *\( *is_object', '', $global_config_file)))
    {
        wp_die("Could not remove Extending Batcache settings from $global_config_file. Please edit that file and remove the line containing the function 'global  $batcache;'. Then refresh this page. orcontact Pressable Support for help");
    }

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
$cdn_extender_plugin_file = WP_CONTENT_DIR . '/mu-plugins/cdn_extender.php';
if (file_exists($cdn_extender_plugin_file))
{
    unlink($cdn_extender_plugin_file);
}
