<?php // Pressable Cache Management - Exclude .JS .JSON from CDN caching


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

$options = get_option('cdn_settings_tab_options');

if (isset($options['exclude_json_js_from_cdn']) && !empty($options['exclude_json_js_from_cdn']))
{

   //Exclude .js and .json from CDN caching

    $cdn_exclude_js_json = WP_CONTENT_DIR . '/mu-plugins/cdn_exclude_js_json.php';
    if (file_exists($cdn_exclude_js_json)) { 

    } else {
        $cdn_exclude_js_json = plugin_dir_path(__FILE__) . '/cdn_exclude_js_json.php';
        $cdn_exclude_js_json_active = WP_CONTENT_DIR . '/mu-plugins/cdn_exclude_js_json.php';

         if(!copy($cdn_exclude_js_json,$cdn_exclude_js_json_active))
         {

         }
         else
         {
            
         }
    }

    //Display admin notice
    function exclude_json_js_from_cdn_admin_notice($message = '', $classes = 'notice-success')
    {

        if (!empty($message))
        {
            printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
        }
    }

    function exclude_json_js_from_cdn_notice()
    {

        $exclude_from_json_js_cdn_activate_display_notice = get_option('exclude_json_js_from_cdn_activate_notice', 'activating');

        if ('activating' === $exclude_from_json_js_cdn_activate_display_notice && current_user_can('manage_options'))
        {

            add_action('admin_notices', function ()
            {

                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                $user = $GLOBALS['current_user'];
                $message = sprintf('<p>Excluded all .JSON  and .JS files from CDN caching.</p>', $user->display_name);

                exclude_json_js_from_cdn_admin_notice($message, 'notice notice-success is-dismissible');
            });

            update_option('exclude_json_js_from_cdn_activate_notice', 'activated');

        }
    }
    add_action('init', 'exclude_json_js_from_cdn_notice');

}

else
{

    /**Update option from the database if the connection is deactivated
     used by admin notice to display and remove notice**/
    update_option('exclude_json_js_from_cdn_activate_notice', 'activating');

    $cdn_exclude_js_json = WP_CONTENT_DIR . '/mu-plugins/cdn_exclude_js_json.php';
    if (file_exists($cdn_exclude_js_json)) {
        unlink($cdn_exclude_js_json);
    } else {
        // File not found.
    }
    
}
