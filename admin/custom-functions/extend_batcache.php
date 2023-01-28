<?php // Pressable Cache Management  - Exctend batcache by 24 hours

// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

$options = get_option('pressable_cache_management_options');

if (isset($options['extend_batcache_checkbox']) && !empty($options['extend_batcache_checkbox']))
{

    //Add the option from the textbox into the database
    update_option('extend_batcache_checkbox', $options['extend_batcache_checkbox']);

    //Declear variable so that it can be accessed from cdn_exclude_specific_file.php
    $extend_batcache = get_option('extend_batcache_checkbox');
	
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

    //Exclude specific files from CDN caching
    $obj_extend_batcache = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_extend_batcache.php';
    if (file_exists($obj_extend_batcache))
    {

    }
    else
    {
        $obj_extend_batcache = plugin_dir_path(__FILE__) . '/extend_batcache_mu_plugin.php';
		
        $obj_extend_batcache_active = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_extend_batcache.php';

        if (!copy($obj_extend_batcache, $obj_extend_batcache_active))
        {

        }
        else
        {

        }
    }
	
	
	  //Display admin notice
    function extend_batcache_admin_notice($message = '', $classes = 'notice-success')
    {

        if (!empty($message))
        {
            printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
        }
    }

    function pcm_extend_batcache_admin_notice()
    {

        $extend_batcache_activate_display_notice = get_option('extend_batcache_activate_notice', 'activating');

        if ('activating' === $extend_batcache_activate_display_notice && current_user_can('manage_options'))
        {

            add_action('admin_notices', function ()
            {

                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                $user = $GLOBALS['current_user'];
				$message = sprintf('<p>Extending Batcache for 24 hours see <a href="https://pressable.com/knowledgebase/modifying-cache-times-batcache/"> Modifying Batcache Times.</a>');

                extend_batcache_admin_notice($message, 'notice notice-success is-dismissible');
            });

            update_option('extend_batcache_activate_notice', 'activated');

        }
    }
    add_action('init', 'pcm_extend_batcache_admin_notice');

	
	
}
else
{
	
	
    /**Update option from the database if the connection is deactivated
     used by admin notice to display and remove notice**/
    update_option('extend_batcache_activate_notice', 'activating');
	
    $obj_extend_batcache = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_extend_batcache.php';
    if (file_exists($obj_extend_batcache))
    {
        unlink($obj_extend_batcache);
    }
    else
    {
        // File not found.
        
    }
}
