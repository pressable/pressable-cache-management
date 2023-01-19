<?php // Pressable Cache Management  - Exclude Google Ads URL's with query string gclid from Batcache

// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

$options = get_option('pressable_cache_management_options');

if (isset($options['exclude_query_string_gclid_checkbox']) && !empty($options['exclude_query_string_gclid_checkbox']))
{


    //Declear variable so that it can be accessed from 
    $exclude_query_string_gclid = get_option('exclude_query_string_gclid');

  


    //Exclude specific files from CDN caching
    $obj_exclude_query_string_gclid = WP_CONTENT_DIR . '/mu-plugins/pcm_exclude_query_string_gclid.php';
    if (file_exists($obj_exclude_query_string_gclid))
    {

    }
    else
    {
        $obj_exclude_query_string_gclid = plugin_dir_path(__FILE__) . '/exclude_query_string_gclid_from_cache_mu_plugin.php';
        
        $obj_exclude_query_string_gclid_active = WP_CONTENT_DIR . '/mu-plugins/pcm_exclude_query_string_gclid.php';

        if (!copy($obj_exclude_query_string_gclid, $obj_exclude_query_string_gclid_active))
        {

        }
        else
        {

        }
    }
    
    
      //Display admin notice
    function exclude_query_string_gclid_admin_notice($message = '', $classes = 'notice-success')
    {

        if (!empty($message))
        {
            printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
        }
    }

    function pcm_exclude_query_string_gclid_admin_notice()
    {

        $exclude_query_string_gclid_activate_display_notice = get_option('exclude_query_string_gclid_activate_notice', 'activating');

        if ('activating' === $exclude_query_string_gclid_activate_display_notice && current_user_can('manage_options'))
        {

            add_action('admin_notices', function ()
            {

                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                $user = $GLOBALS['current_user'];
                $message = sprintf('<p> Google Ads URL with query string (gclid) will be excluded from Batcache.</p>', $user->display_name);

                exclude_query_string_gclid_admin_notice($message, 'notice notice-success is-dismissible');
            });

            update_option('exclude_query_string_gclid_activate_notice', 'activated');

        }
    }
    add_action('init', 'pcm_exclude_query_string_gclid_admin_notice');

    
    
}
else
{
    
    
    /**Update option from the database if the option is deactivated
     used by admin notice to display and remove notice**/
    update_option('exclude_query_string_gclid_activate_notice', 'activating');
    
    $obj_exclude_query_string_gclid = WP_CONTENT_DIR . '/mu-plugins/pcm_exclude_query_string_gclid.php';
    if (file_exists($obj_exclude_query_string_gclid))
    {
        unlink($obj_exclude_query_string_gclid);
    }
    else
    {
        // File not found.
        
    }
}
