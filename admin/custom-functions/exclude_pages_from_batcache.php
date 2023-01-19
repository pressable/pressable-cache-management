<?php // Pressable Cache Management  - Exclude pages from Batcache

// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

$options = get_option('pressable_cache_management_options');


	if (isset($options['exempt_from_batcache']) && !empty($options['exempt_from_batcache']))
{

    //Add the option from the textbox into the database
    update_option('exempt_from_batcache', $options['exempt_from_batcache']);

    //Exclude specific files from CDN caching
    $obj_exclude_pages_from_batcache = WP_CONTENT_DIR . '/mu-plugins/pcm_exclude_pages_from_batcache.php';
    if (file_exists($obj_exclude_pages_from_batcache))
    {

    }
    else
    {
        $obj_exclude_pages_from_batcache = plugin_dir_path(__FILE__) . '/exclude_pages_from_batcache_mu_plugin.php';
		
        $obj_exclude_pages_from_batcache_active = WP_CONTENT_DIR . '/mu-plugins/pcm_exclude_pages_from_batcache.php';

        if (!copy($obj_exclude_pages_from_batcache, $obj_exclude_pages_from_batcache_active))
        {

        }
        else
        {

        }
    }

}
else
{
    $obj_exclude_pages_from_batcache = WP_CONTENT_DIR . '/mu-plugins/pcm_exclude_pages_from_batcache.php';
    if (file_exists($obj_exclude_pages_from_batcache))
    {
        unlink($obj_exclude_pages_from_batcache);
    }
    else
    {
        // File not found.
        
    }
}
