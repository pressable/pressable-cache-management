<?php // Pressable Cache Management  - Enable Caching for pages which has wpp_ cookies 

// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

$options = get_option('pressable_cache_management_options');

if (isset($options['cache_wpp_cookies_pages']) && !empty($options['cache_wpp_cookies_pages']))
{

    //Add the option from the textbox into the database
    update_option('cache_wpp_cookies_pages', $options['cache_wpp_cookies_pages']);

  


    //Exclude specific files from CDN caching
    $obj_cache_wpp_cookies_pages = WP_CONTENT_DIR . '/mu-plugins/pcm_cache_wpp_cookies_pages.php';
    if (file_exists($obj_cache_wpp_cookies_pages))
    {

    }
    else
    {
        $obj_cache_wpp_cookies_pages = plugin_dir_path(__FILE__) . '/cache_wpp_cookie_page_mu_plugin.php';
		
        $obj_cache_wpp_cookies_pages_active = WP_CONTENT_DIR . '/mu-plugins/pcm_cache_wpp_cookies_pages.php';

        if (!copy($obj_cache_wpp_cookies_pages, $obj_cache_wpp_cookies_pages_active))
        {

        }
        else
        {

        }
    }
	
	
	  //Display admin notice
    function cache_wpp_cookies_pages_admin_notice($message = '', $classes = 'notice-success')
    {

        if (!empty($message))
        {
            printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
        }
    }

    function pcm_cache_wpp_cookies_pages_admin_notice()
    {

        $cache_wpp_cookies_pages_activate_display_notice = get_option('cache_wpp_cookies_pages_activate_notice', 'activating');

        if ('activating' === $cache_wpp_cookies_pages_activate_display_notice && current_user_can('manage_options'))
        {

            add_action('admin_notices', function ()
            {

                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                $user = $GLOBALS['current_user'];
				$message = sprintf('<p>Batcache will now cache pages with wpp_ cookies.</p>', $user->display_name);

                cache_wpp_cookies_pages_admin_notice($message, 'notice notice-success is-dismissible');
            });

            update_option('cache_wpp_cookies_pages_activate_notice', 'activated');

        }
    }
    add_action('init', 'pcm_cache_wpp_cookies_pages_admin_notice');

	
	
}
else
{
	
	
    /**Update option from the database if the option is deactivated
     used by admin notice to display and remove notice**/
    update_option('cache_wpp_cookies_pages_activate_notice', 'activating');
	
    $obj_cache_wpp_cookies_pages = WP_CONTENT_DIR . '/mu-plugins/pcm_cache_wpp_cookies_pages.php';
    if (file_exists($obj_cache_wpp_cookies_pages))
    {
        unlink($obj_cache_wpp_cookies_pages);
    }
    else
    {
        // File not found.
        
    }
}
