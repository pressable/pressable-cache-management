<?php // Pressable Cache Management  - Exclude a particular file from caching

// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

$options = get_option('cdn_settings_tab_options');

if (isset($options['exclude_particular_file_from_cdn']) && !empty($options['exclude_particular_file_from_cdn']))
{



    //Add the option from the textbox into the database
    update_option('excluded_particular_file', $options['exclude_particular_file_from_cdn']);

    //Declear variable so that it can be accessed from cdn_exclude_specific_file.php
    $excluded_file = get_option('excluded_particular_file');

  


    //Exclude specific files from CDN caching
    $cdn_exclude_specific_file = WP_CONTENT_DIR . '/mu-plugins/cdn_exclude_specific_file.php';
    if (file_exists($cdn_exclude_specific_file))
    {

    }
    else
    {
        $cdn_exclude_specific_file = plugin_dir_path(__FILE__) . '/cdn_exclude_specific_file.php';
        $cdn_exclude_specific_file_active = WP_CONTENT_DIR . '/mu-plugins/cdn_exclude_specific_file.php';

        if (!copy($cdn_exclude_specific_file, $cdn_exclude_specific_file_active))
        {

        }
        else
        {

        }
    }

}
else
{
    $cdn_exclude_specific_file = WP_CONTENT_DIR . '/mu-plugins/cdn_exclude_specific_file.php';
    if (file_exists($cdn_exclude_specific_file))
    {
        unlink($cdn_exclude_specific_file);
    }
    else
    {
        // File not found.
        
    }
}

