<?php // Pressable Cache Management - Exclude .JPG .PNG .WEBP from CDN caching


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

$options = get_option('cdn_settings_tab_options');

if (isset($options['exclude_jpg_png_webp_from_cdn']) && !empty($options['exclude_jpg_png_webp_from_cdn']))
{


    //Add the option from the textbox into the database
    update_option('exclude_images_file', $options['exclude_jpg_png_webp_from_cdn']);

    //Declear variable so that it can be accessed from cdn_exclude_jpg_png_webp.php
    $excluded_images = get_option('exclude_images_file');

    //Exclude .jpg .png .webp from CDN caching
    $cdn_exclude_jpg_png_webp = WP_CONTENT_DIR . '/mu-plugins/cdn_exclude_jpg_png_webp.php';
    if (file_exists($cdn_exclude_jpg_png_webp)) { 

    } else {
        $cdn_exclude_jpg_png_webp = plugin_dir_path(__FILE__) . '/cdn_exclude_jpg_png_webp.php';
        $cdn_exclude_jpg_png_webp_active = WP_CONTENT_DIR . '/mu-plugins/cdn_exclude_jpg_png_webp.php';

         if(!copy($cdn_exclude_jpg_png_webp,$cdn_exclude_jpg_png_webp_active))
         {

         }
         else
         {

         }
    }


    

    //Display admin notice
    function exclude_img_from_cdn_admin_notice($message = '', $classes = 'notice-success')
    {

        if (!empty($message))
        {
            printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
        }
    }

    function pcm_exclude_img_from_cdn_admin_notice()
    {

        $exclude_from_img_cdn_activate_display_notice = get_option('exclude_images_from_cdn_activate_notice', 'activating');

        if ('activating' === $exclude_from_img_cdn_activate_display_notice && current_user_can('manage_options'))
        {

            add_action('admin_notices', function ()
            {

                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                $user = $GLOBALS['current_user'];
                $message = sprintf('<p>Excluded all .JPG .PNG .WEBP from CDN caching.</p>', $user->display_name);

                exclude_img_from_cdn_admin_notice($message, 'notice notice-success is-dismissible');
            });

            update_option('exclude_images_from_cdn_activate_notice', 'activated');

        }
    }
    add_action('init', 'pcm_exclude_img_from_cdn_admin_notice');

}

else
{

    /**Update option from the database if the connection is deactivated
     used by admin notice to display and remove notice**/
    update_option('exclude_images_from_cdn_activate_notice', 'activating');

    $cdn_exclude_jpg_png_webp = WP_CONTENT_DIR . '/mu-plugins/cdn_exclude_jpg_png_webp.php';
    if (file_exists($cdn_exclude_jpg_png_webp)) {
        unlink($cdn_exclude_jpg_png_webp);
    } else {
        // File not found.
    }
}

