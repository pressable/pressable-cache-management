<?php // Pressable Cache Management - Exclude .woff .woff2 .otf .ttf eot from CDN caching


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

$options = get_option('cdn_settings_tab_options');

if (isset($options['exclude_font_files_from_cdn']) && !empty($options['exclude_font_files_from_cdn']))
{

    //Create the pressable-cache-management mu-plugin index file
    $pcm_mu_plugins_index = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management.php';
    if (!file_exists($pcm_mu_plugins_index))
    {
        // Copy pressable-cache-management.php from plugin directory to mu-plugins directory
        copy(plugin_dir_path(__FILE__) . '/pressable_cache_management_mu_plugin_index.php', $pcm_mu_plugins_index);
    }

    // Check if the pressable-cache-management directory exists or create the folder
    if (!file_exists(WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/'))
    {
        //create the directory
        wp_mkdir_p(WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/');
    }

    //Add the option from the textbox into the database
    update_option('exclude_font_files', $options['exclude_font_files_from_cdn']);

    //Exclude .woff .woff2 .otf .ttf eot from CDN caching
    $cdn_exclude_font_files = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_exclude_font_files_from_cdn.php';
    if (file_exists($cdn_exclude_font_files))
    {

    }
    else
    {
        $cdn_exclude_font_files = plugin_dir_path(__FILE__) . '/cdn_exclude_font_files_mu_plugins.php';
        $cdn_exclude_font_files_active = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_exclude_font_files_from_cdn.php';

        if (!copy($cdn_exclude_font_files, $cdn_exclude_font_files_active))
        {

        }
        else
        {

        }
    }

    //Display admin notice
    function exclude_font_from_cdn_admin_notice($message = '', $classes = 'notice-success')
    {

        if (!empty($message))
        {
            printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
        }
    }

    function pcm_exclude_fonts_from_cdn_admin_notice()
    {

        $exclude_from_img_cdn_activate_display_notice = get_option('exclude_fonts_from_cdn_activate_notice', 'activating');

        if ('activating' === $exclude_from_img_cdn_activate_display_notice && current_user_can('manage_options'))
        {

            add_action('admin_notices', function ()
            {

                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                $user = $GLOBALS['current_user'];
                $message = sprintf('<p>Excluded all .WOFF .WOFF2 .OTF .TTF .EOT from CDN caching.</p>');

                exclude_font_from_cdn_admin_notice($message, 'notice notice-success is-dismissible');
            });

            update_option('exclude_fonts_from_cdn_activate_notice', 'activated');

        }
    }
    add_action('init', 'pcm_exclude_fonts_from_cdn_admin_notice');

}

else
{

    /**Update option from the database if the option is deactivated
     used by admin notice to display and remove notice**/
    update_option('exclude_fonts_from_cdn_activate_notice', 'activating');

    $cdn_exclude_font_files = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_exclude_font_files_from_cdn.php';
    if (file_exists($cdn_exclude_font_files))
    {
        unlink($cdn_exclude_font_files);
    }
    else
    {
        // File not found.
        
    }
}
