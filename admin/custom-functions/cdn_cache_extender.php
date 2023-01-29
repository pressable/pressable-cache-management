<?php // Pressable Cache Managemenet Plugin - Extend the cache-control from 7 days until 10 years for static assets

// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

$options = get_option('cdn_settings_tab_options');

if (isset($options['cdn_cache_extender']) && !empty($options['cdn_cache_extender']))
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
	
    $cdn_extender_plugin_file = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_cdn_extender.php';
    if (file_exists($cdn_extender_plugin_file)) { 
        // extender plugin already installed. 
    } else {
        $cdn_extender_plugin = plugin_dir_path(__FILE__) . '/cdn_extender.php';
        $cdn_extender_plugin_active = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_cdn_extender.php';

        //Flush cache to enable activation take effect immediately
        wp_cache_flush();

         if(!copy($cdn_extender_plugin,$cdn_extender_plugin_active))
         {
             //
         }
         else
         {
             //
         }
    }

    //Display admin notice
    function ext_cdn_admin_notice($message = '', $classes = 'notice-success')
    {

        if (!empty($message))
        {
            printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
        }
    }

    function ext_cdn_notice()
    {

        $extend_cdn_activate_display_notice = get_option('extend_cdn_activate_notice', 'activating');

        if ('activating' === $extend_cdn_activate_display_notice && current_user_can('manage_options'))
        {

            add_action('admin_notices', function ()
            {

                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                $user = $GLOBALS['current_user'];
                $message = sprintf('<p>CDN Extender Enabled.</p>');

                ext_cdn_admin_notice($message, 'notice notice-success is-dismissible');
            });

            update_option('extend_cdn_activate_notice', 'activated');

        }
    }
    add_action('init', 'ext_cdn_notice');

}

else
{
      /**Update option from the database if the connection is deactivated
        used by admin notice to display and remove notice**/
    update_option('extend_cdn_activate_notice', 'activating');

    $cdn_extender_plugin_file = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_cdn_extender.php';
    if (file_exists($cdn_extender_plugin_file)) {
        unlink($cdn_extender_plugin_file);

        //Flush cache to enable deactivation take effect immediately
        wp_cache_flush();
    } else {
        // File not found.
    }

}
