<?php // Pressable Cache Management - Exclude .CSS from CDN caching


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

$options = get_option('cdn_settings_tab_options');

if (isset($options['exclude_css_from_cdn']) && !empty($options['exclude_css_from_cdn']))
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
	
    //Exclude .js and .json from CDN caching
    $cdn_exclude_css = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/cdn_exclude_css.php';
    if (file_exists($cdn_exclude_css)) { 

    } else {
        $cdn_exclude_css = plugin_dir_path(__FILE__) . '/cdn_exclude_css.php';
        $cdn_exclude_css_active = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/cdn_exclude_css.php';

        //Flush cache to enable activation take effect immediately
        wp_cache_flush();


         if(!copy($cdn_exclude_css,$cdn_exclude_css_active))
         {

         }
         else
         {
            
         }
    }

    //Display admin notice
    function exclude_css_js_from_cdn_admin_notice($message = '', $classes = 'notice-success')
    {

        if (!empty($message))
        {
            printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
        }
    }

    function exclude_css__from_cdn_notice()
    {

        $exclude_from_css_js_cdn_activate_display_notice = get_option('exclude_css_from_cdn_activate_notice', 'activating');

        if ('activating' === $exclude_from_css_js_cdn_activate_display_notice && current_user_can('manage_options'))
        {

            add_action('admin_notices', function ()
            {

                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                $user = $GLOBALS['current_user'];
                $message = sprintf('<p>Excluded all .CSS files from CDN caching.</p>');

                exclude_css_js_from_cdn_admin_notice($message, 'notice notice-success is-dismissible');
            });

            update_option('exclude_css_from_cdn_activate_notice', 'activated');

        }
    }
    add_action('init', 'exclude_css__from_cdn_notice');

}

else
{

    /**Update option from the database if the connection is deactivated
     used by admin notice to display and remove notice**/
    update_option('exclude_css_from_cdn_activate_notice', 'activating');

    $cdn_exclude_css = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/cdn_exclude_css.php';
    if (file_exists($cdn_exclude_css)) {
        unlink($cdn_exclude_css);

        //Flush cache to enable deactivation take effect immediately
        wp_cache_flush();

    } else {
        // File not found.
    }
    
}
