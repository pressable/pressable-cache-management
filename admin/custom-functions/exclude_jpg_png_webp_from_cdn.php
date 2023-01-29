<?php // Pressable Cache Management - Exclude .JPG .JPEG .PNG .GIF .WEBP from CDN caching


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

$options = get_option('cdn_settings_tab_options');

if (isset($options['exclude_jpg_png_webp_from_cdn']) && !empty($options['exclude_jpg_png_webp_from_cdn']))
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
    update_option('exclude_images_file', $options['exclude_jpg_png_webp_from_cdn']);

    //Declear variable so that it can be accessed from cdn_exclude_jpg_png_webp.php
    $excluded_images = get_option('exclude_images_file');

    //Exclude .jpg .jpeg .png .gif .webp from CDN caching
    $cdn_exclude_jpg_png_webp = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/cdn_exclude_jpg_png_webp.php';
    if (file_exists($cdn_exclude_jpg_png_webp)) { 

    } else {
        $cdn_exclude_jpg_png_webp = plugin_dir_path(__FILE__) . '/cdn_exclude_jpg_png_webp.php';
        $cdn_exclude_jpg_png_webp_active = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/cdn_exclude_jpg_png_webp.php';

        //Flush cache to enable activation take effect immediately
        wp_cache_flush();


         if(!copy($cdn_exclude_jpg_png_webp,$cdn_exclude_jpg_png_webp_active))
         {

         }
         else
         {

         }
    }


    

    //Display admin notice
    function  exclude_img_files_from_cdn_admin_notice($message = '', $classes = 'notice-success')
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
                $message = sprintf('<p>Excluded all .JPG .PNG .GIF .WEBP from CDN Caching.</p>');

                exclude_img_files_from_cdn_admin_notice($message, 'notice notice-success is-dismissible');
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

    $cdn_exclude_jpg_png_webp = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/cdn_exclude_jpg_png_webp.php';
    if (file_exists($cdn_exclude_jpg_png_webp)) {
        unlink($cdn_exclude_jpg_png_webp);

        //Flush cache to enable deactivation take effect immediately
        wp_cache_flush();

    } else {
        // File not found.
    }
}
