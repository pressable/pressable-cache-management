<?php // Custom function - Extend Batcache in wp-config.php

// disable direct file access

if (!defined('ABSPATH'))
{
    exit;
}

//library that writes the batache function to wp-config.php
require_once (dirname(__FILE__) . '/wp-write-to-file-lib.php');

$options = get_option('pressable_cache_management_options');

if (isset($options['extend_batcache_checkbox']) && !empty($options['extend_batcache_checkbox']))
{

    /*
     * Extend Batcache
     * Extend Batcache settings from wp-config.php file
    */

    if (file_exists(ABSPATH . 'wp-config.php'))
    {
        $global_config_file = ABSPATH . 'wp-config.php';
    }
    else
    {
        $global_config_file = dirname(ABSPATH) . '/wp-config.php';
    }

    $line = 'global $batcache; if ( is_object($batcache) ) { $batcache->max_age = 86400; $batcache->seconds = 3600;  };';

    if (!is_writeable_wp_config($global_config_file) || !wp_config_file_replace_line('global *\$batcache; if *\( *is_object', $line, $global_config_file))
    {
        if (defined('global $batcache;') && constant('global $batcache;') == false)

        {
            return;
        }
        else
        {
            //Throw error if writing batcache cannot write to wp-config.php file
            function cannot_write_admin_notice__success()
            {

                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                $user = $GLOBALS['current_user']; ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e('Something went wrong cannot write to <strong>wp-config.php</strong> file.', 'sample-text-domain'); ?></p>
        </div>
        <?php
            }
            add_action('admin_notices', 'cannot_write_admin_notice__success');
        }
        return false;
    }
    else
    {
        function ext_batcache_admin_notice($message = '', $classes = 'notice-success')
        {

            if (!empty($message))
            {
                printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
            }
        }

        function ext_batcahe_notice()
        {

            $extend_batcache_activate_display_notice = get_option('extend_batcache_activate_notice', 'activating');

            if ('activating' === $extend_batcache_activate_display_notice && current_user_can('manage_options'))
            {

                add_action('admin_notices', function ()
                {

                    $screen = get_current_screen();

                    //Display admin notice for this plugin page only
                    if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                    $user = $GLOBALS['current_user'];
                    $message = sprintf('<p>Extenteding Batcache is enabled<a href="https://pressable.com/knowledgebase/modifying-cache-times-batcache/"> Troubleshooting Guide</a>.', $user->display_name);

                    ext_batcache_admin_notice($message, 'notice notice-success is-dismissible');
                });

                update_option('extend_batcache_activate_notice', 'activated');

            }
        }
        add_action('init', 'ext_batcahe_notice');

    }
    return true;
}

else
{

    /*
     * Remove Batcache extending
     * Remove Batcache extending from wp-config.php file
    */

    $delete_config_file = true;

    global $wp_rewrite;
    if (file_exists(ABSPATH . 'wp-config.php'))
    {
        $global_config_file = ABSPATH . 'wp-config.php';

        /**Update option from the database if the connection is deactivated
         used by admin notice to display and remove notice**/
        update_option('extend_batcache_activate_notice', 'activating');
    }
    else
    {
        $global_config_file = dirname(ABSPATH) . '/wp-config.php';
    }

    if (apply_filters('wpsc_enable_wp_config_edit', true))
    {
        $line = 'global $batcache; if ( is_object($batcache) ) { $batcache->max_age = 86400; $batcache->seconds = 3600;  };';
        if (strpos(file_get_contents($global_config_file) , $line) && (!is_writeable_wp_config($global_config_file) || !wp_config_file_replace_line('global *\$batcache; if *\( *is_object', '', $global_config_file)))
        {
            wp_die("Could not remove Extending Batcache settings from $global_config_file. Please edit that file and remove the line containing the function 'global  $batcache;'. Then refresh this page. orcontact Pressable Support for help");
        }

        $line = 'global $batcache; if ( is_object($batcache) ) { $batcache->max_age = 86400; $batcache->seconds = 3600;  };';
        if (strpos(file_get_contents($global_config_file) , $line) && (!is_writeable_wp_config($global_config_file) || !wp_config_file_replace_line('global  *\$batcache; if *\( *is_object', '', $global_config_file)))
        {
            wp_die("Could not remove Extending Batcache settings from $global_config_file. Please edit that file and remove the line containing the function 'global  $batcache;'. Then refresh this page. orcontact Pressable Support for help");
        }

    }
}

