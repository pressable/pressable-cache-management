<?php // Pressable Cache Management  - Exclude pages from Batcache

// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

$options = get_option('pressable_cache_management_options');


	if (isset($options['exempt_from_batcache']) && !empty($options['exempt_from_batcache']))
{

	//Create the pressable-cache-management mu-plugin index file
	$pcm_mu_plugins_index = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management.php';
	if (!file_exists($pcm_mu_plugins_index)) {
		// Copy pressable-cache-management.php from plugin directory to mu-plugins directory
		copy( plugin_dir_path(__FILE__) . '/pressable_cache_management_mu_plugin_index.php', $pcm_mu_plugins_index);
	}
		
	// Check if the pressable-cache-management directory exists or create the folder
	if (!file_exists(WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/')) {
		//create the directory
		wp_mkdir_p(WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/');
	}
		
    //Add the option from the textbox into the database
    update_option('exempt_from_batcache', $options['exempt_from_batcache']);

    //Exclude specific files from CDN caching
    $obj_exclude_pages_from_batcache = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_exclude_pages_from_batcache.php';
    if (file_exists($obj_exclude_pages_from_batcache))
    {

    }
    else
    {
        $obj_exclude_pages_from_batcache = plugin_dir_path(__FILE__) . '/exclude_pages_from_batcache_mu_plugin.php';
		
        $obj_exclude_pages_from_batcache_active = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_exclude_pages_from_batcache.php';

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
    $obj_exclude_pages_from_batcache = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_exclude_pages_from_batcache.php';
    if (file_exists($obj_exclude_pages_from_batcache))
    {
        unlink($obj_exclude_pages_from_batcache);
    }
    else
    {
        // File not found.
        
    }
}
