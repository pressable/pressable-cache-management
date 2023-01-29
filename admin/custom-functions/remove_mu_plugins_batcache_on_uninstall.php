<?php //  Called by uninstall.php to remove all Pressable Cache Management MU plugins when plugin is uninstalled

// disable direct file access
if (!defined('ABSPATH'))
{
    exit;
}

/*
 * Remove Pressable Cache Management mu-plugins 
*/

//Remove  Pressable Cache Management mu-plugin index
$pcm_mu_plugin_index = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management.php';
if (file_exists($pcm_mu_plugin_index ))
{
    unlink($pcm_mu_plugin_index );
}

$pcm_cache_mu_plugins = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management';
if (file_exists($pcm_cache_mu_plugins))
{
    rrmdir($pcm_cache_mu_plugins);
}

function rrmdir($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir."/".$object))
                    rrmdir($dir."/".$object);
                else
                    unlink($dir."/".$object);
                    wp_cache_flush();
            }
        }
        rmdir($dir);
    }
}
