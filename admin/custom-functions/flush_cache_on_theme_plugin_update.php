<?php // Custom function - Flush cache automatically on theme and plugin update


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

//call option from checkbox to see if an option is selected
$options = get_option('pressable_cache_management_options');

if (isset($options['flush_cache_theme_plugin_checkbox']) && !empty($options['flush_cache_theme_plugin_checkbox']))
{

    //Check if theme or plugin is updated then flush cache when complete
    function pressable_plugins_update_completed()
    {

        // if ($options['action'] == 'update' && $options['type'] == 'plugin' && isset($options['plugins']))
        // {
        //     wp_cache_flush();
        //Check update for plugin
        function pcm_flush_flush_cache_after_plugin_update($upgrader_object, $options)
        {
            if ($options['action'] == 'update')
            {
                if ($options['type'] == 'plugin' && isset($options['plugins']))
                {
                    wp_cache_flush();
                }
            }
        }

        //Check update for themes
        function pcm_flush_cache_after_theme_update($upgrader_object, $options)
        {
            if ($options['action'] == 'update')
            {
                if ($options['type'] == 'theme' && isset($options['themes']))
                {
                    wp_cache_flush();
                }
            }
        }

        //Save time stamp to database if cache is flushed when theme or plugin updated.
        $object_cache_flush_time = date(' jS F Y  g:ia') . "\nUTC";
        update_option('flush-cache-theme-plugin-time-stamp', $object_cache_flush_time);

    }
    add_action('upgrader_process_complete', 'pressable_plugins_update_completed', 10, 2);

}

