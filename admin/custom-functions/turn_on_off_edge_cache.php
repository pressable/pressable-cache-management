<?php //Pressable Cache Management - Custom function to turn on/off Edge Cache


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

// Notices for success/error states
if (!function_exists('pressable_edge_cache_notice_success_enable')) {
    function pressable_edge_cache_notice_success_enable()
    {
        $screen = get_current_screen();
        if (isset($screen) && $screen->id !== 'toplevel_page_pressable_cache_management') return;

        $message = __('
            <div style="margin-top: 20px;">
                <h3>🎉 Edge Cache Enabled!</h3>
                <p>Edge Cache provides performance improvements, particularly for Time to First Byte (TTFB),<br>
                by serving page cache from the nearest server to your website visitors.</p>
                <br>
                <a href="https://pressable.com/knowledgebase/edge-cache/" target="_blank">Learn more about Edge Cache.</a>
                <br><br>
            </div>
        ', 'pressable_cache_management');

        echo '<div class="notice notice-success is-dismissible">' . $message . '</div>';
    }
}


if (!function_exists('pressable_edge_cache_notice_success_disable')) {
    function pressable_edge_cache_notice_success_disable()
    {
        $screen = get_current_screen();
        if (isset($screen) && $screen->id !== 'toplevel_page_pressable_cache_management') return;
        $message = __('Edge Cache Deactivated.', 'pressable_cache_management');
        printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($message));
    }
}

if (!function_exists('pcm_pressable_edge_cache_error_msg')) {
    function pcm_pressable_edge_cache_error_msg($error_message = '')
    {
        $screen = get_current_screen();
        if (isset($screen) && $screen->id !== 'toplevel_page_pressable_cache_management') return;
        $message = empty($error_message) ? __('Something went wrong trying to communicate with the Edge Cache system. Try again.', 'pressable_cache_management') : $error_message;
        printf('<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html($message));
    }
}


/*******************************************************
 * Edge Cache Control using Edge_Cache_Plugin methods
 *******************************************************/


if (isset($_POST['enable_edge_cache_nonce']))
{
    function pcm_pressable_enable_edge_cache()
    {
        // verify nonce
        if (wp_verify_nonce($_POST['enable_edge_cache_nonce'], 'enable_edge_cache_nonce'))
        {
            if (class_exists('Edge_Cache_Plugin')) {
                // Get the instance of the Edge Cache plugin
                $edge_cache = Edge_Cache_Plugin::get_instance();
                
                // Call the equivalent 'enable' action
                $result = $edge_cache->query_ec_backend('on', ['wp_action' => 'manual_dashboard_set']);

                if (is_wp_error($result)) {
                    // Handle error
                    update_option('edge-cache-status', 'Error');
                    update_option('edge-cache-enabled', 'disabled');
                    add_action('admin_notices', function() use ($result) {
                        pcm_pressable_edge_cache_error_msg($result->get_error_message());
                    });
                } else {
                    // Success logic
                    update_option('edge-cache-status', 'Success');
                    update_option('edge-cache-enabled', 'enabled');
                    add_action('admin_notices', 'pressable_edge_cache_notice_success_enable');
                }
            } else {
                // Show error if the dependency class is missing
                add_action('admin_notices', function() {
                    pcm_pressable_edge_cache_error_msg('Required Edge Cache dependency is not available.');
                });
            }
        }
    }
    add_action('init', 'pcm_pressable_enable_edge_cache');
}


if (isset($_POST['disable_edge_cache_nonce']))
{
    function pcm_pressable_disable_edge_cache()
    {
        // verify nonce
        if (wp_verify_nonce($_POST['disable_edge_cache_nonce'], 'disable_edge_cache_nonce'))
        {
            if (class_exists('Edge_Cache_Plugin')) {
                // Get the instance of the Edge Cache plugin
                $edge_cache = Edge_Cache_Plugin::get_instance();
                
                // Call the equivalent 'disable' action
                $result = $edge_cache->query_ec_backend('off', ['wp_action' => 'manual_dashboard_set']);

                if (is_wp_error($result)) {
                    // Handle error
                    update_option('edge-cache-status', 'Error');
                    update_option('edge-cache-enabled', 'enabled'); // Stays enabled on failure
                    add_action('admin_notices', function() use ($result) {
                        pcm_pressable_edge_cache_error_msg($result->get_error_message());
                    });
                } else {
                    // Success logic
                    update_option('edge-cache-status', 'Success');
                    update_option('edge-cache-enabled', 'disabled');
                    add_action('admin_notices', 'pressable_edge_cache_notice_success_disable');
                }
            } else {
                // Show error if the dependency class is missing
                add_action('admin_notices', function() {
                    pcm_pressable_edge_cache_error_msg('Required Edge Cache dependency is not available.');
                });
            }
        }
    }
    add_action('init', 'pcm_pressable_disable_edge_cache');
}
