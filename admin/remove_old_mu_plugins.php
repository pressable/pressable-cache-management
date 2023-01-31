<?php //Pressable Cache Managemenet - Remove mu-plugins added by the old version of the plugin


// disable direct file access
if (!defined("ABSPATH"))
{
    exit();
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
