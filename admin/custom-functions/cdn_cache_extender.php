<?php // Pressable Cache Managemenet Plugin - Extend the cache-control from 7 days until 10 years for static assets

// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

$options = get_option('cdn_settings_tab_options');

if (isset($options['cdn_cache_extender']) && !empty($options['cdn_cache_extender']))
{

    $cdn_extender_plugin_file = WP_CONTENT_DIR . '/mu-plugins/cdn_extender.php';
    if (file_exists($cdn_extender_plugin_file)) { 
        // extender plugin already installed. 
    } else {
        $cdn_extender_plugin = plugin_dir_path(__FILE__) . '/cdn_extender.php';
        $cdn_extender_plugin_active = WP_CONTENT_DIR . '/mu-plugins/cdn_extender.php';

         if(!copy($cdn_extender_plugin,$cdn_extender_plugin_active))
         {
             //echo $cdn_extender_plugin." failed to copy to " .$cdn_extender_plugin_active;
         }
         else
         {
             //echo $cdn_extender_plugin. " copied into " .$cdn_extender_plugin_active;
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
                $message = sprintf('<p>CDN Extender Enabled.</p>', $user->display_name);

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

    $cdn_extender_plugin_file = WP_CONTENT_DIR . '/mu-plugins/cdn_extender.php';
    if (file_exists($cdn_extender_plugin_file)) {
        unlink($cdn_extender_plugin_file);
    } else {
        // File not found.
    }

}
