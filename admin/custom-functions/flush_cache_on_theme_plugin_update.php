<?php // Custom function - Flush cache automatically on themes and plugins update


// Disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

//call option from checkbox to see if an option is selected
$options = get_option('pressable_cache_management_options');

if (isset($options['flush_cache_theme_plugin_checkbox']) && !empty($options['flush_cache_theme_plugin_checkbox']))
{

    function pcm_plugins_themes_update_completed($upgrader_object, $options)
    {

        if ($type === 'plugin' || $type === 'theme');
       
        wp_cache_flush();
        $object_cache_flush_time = date(' jS F Y  g:ia') . "\nUTC" . " — " . "<b>" . $options['type'] . " was updated</b>";
        update_option('flush-cache-theme-plugin-time-stamp', $object_cache_flush_time);
    } 
    add_action('upgrader_process_complete', 'pcm_plugins_themes_update_completed', 10, 2);
 }
