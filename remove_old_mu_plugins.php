<?php //Pressable Cache Managemenet - Remove mu-plugins added by the old version of the plugin


// disable direct file access
if (!defined("ABSPATH"))
{
    exit();
}

//Check if this particular plugin  version is updated
$current_version = '3.4.4';
if (version_compare($current_version, '3.4.4', '>='))
{

    //library that writes/removes the batcache function to wp-config.php
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/wp-write-to-file-lib.php';

    /*
     * Remove Batcache extending
     * Remove Batcache extending from wp-config.php file
    */

    $delete_config_file = true;

    global $wp_rewrite;
    $global_config_file = file_exists(ABSPATH . 'wp-config.php') ? ABSPATH . 'wp-config.php' : dirname(ABSPATH) . '/wp-config.php';

    if (apply_filters('wpsc_enable_wp_config_edit', true))
    {
        $line = 'global $batcache; if ( is_object($batcache) ) { $batcache->max_age = 86400; $batcache->seconds = 3600;  };';
        if (strpos(file_get_contents($global_config_file) , $line) !== false && (!is_writeable_wp_config($global_config_file) || !wp_config_file_replace_line('global *\$batcache; if *\( *is_object', '', $global_config_file)))
        {
            wp_die("Could not remove Extending Batcache settings from $global_config_file. Please edit that file and remove the line containing the function 'global  $batcache;'. Then refresh this page. orcontact Pressable Support for help");
        }
    }
}

/**
 * Removes mu-plugins from the old mu-plugins directory
 * to prevent any conflict due to the new mu-plugins folder
 * which is created to store mu-plugins
 */

$mu_plugins = ['cdn_exclude_specific_file.php', 'cdn_exclude_css.php', 'cdn_exclude_jpg_png_webp.php', 'cdn_exclude_js_json.php', 'cdn_extender.php'];

foreach ($mu_plugins as $mu_plugin)
{
    $file = WP_CONTENT_DIR . '/mu-plugins/' . $mu_plugin;
    if (file_exists($file))
    {
        unlink($file);
    }
}

