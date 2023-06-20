<?php // Pressable Cache Management - Purge Edge Cache

// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

/**********************************************
 * Check if access token is valid and if access
 * token transient is created in the database
 * else it will generate a new access token.
 **********************************************/

$check_access_token_expiry = get_option('access_token_expiry');


if (isset($_POST['purge_edge_cache_nonce']) && time() < $check_access_token_expiry && false !== get_transient('access_token'))
{
    function pcm_pressable_edge_cache_purge()
    {
        $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');

        if (wp_verify_nonce($_POST['purge_edge_cache_nonce'], 'purge_edge_cache_nonce'))
        {
            $access_token = get_transient('access_token');
            $pressable_site_id = $api_auth_tab_options['pressable_site_id'];
            $pressable_api_request_headers = array(
                'Authorization' => 'Bearer ' . ($access_token)
            );
            $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/edge-cache';

            $pressable_api_response_post_request = wp_remote_request($pressable_api_request_url, array(
                'method' => 'DELETE',
                'headers' => $pressable_api_request_headers,
            ));

            $response = json_decode(wp_remote_retrieve_body($pressable_api_response_post_request) , true);

            if ($response["message"] == "Success")
            {
                function pressable_edge_cache_purge_notice_success()
                {
                    $class = 'notice notice-success is-dismissible';
                    $message = __('Edge Cache Purged Successfully :)', 'pressable_cache_management');
                    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                }
                add_action('admin_notices', 'pressable_edge_cache_purge_notice_success');

                $edge_cache_purged_time = date('jS F Y  g:ia') . "\nUTC";
                update_option('edge-cache-purge-time-stamp', $edge_cache_purged_time);
            }
            elseif ($response["errors"][0] == "Edge cache must be enabled")
            {
                function pressable_edge_cache_purge_cache_notice_warning()
                {
                    $screen = get_current_screen();
                    if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                    $user = $GLOBALS['current_user'];
                    $class = 'notice notice-success is-dismissible';
                    $message = __('We\'ve turned on Edge Cache because it was switched off, you can purge the cache now! 🚀', 'pressable_cache_management', $user->display_name);
                    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                }
                add_action('admin_notices', 'pressable_edge_cache_purge_cache_notice_warning');

                // Make an API call to turn on the Edge Cache
                $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/edge-cache';
                $pressable_api_response_put_request = wp_remote_request($pressable_api_request_url, array(
                    'method' => 'PUT',
                    'headers' => $pressable_api_request_headers,
                ));

                $response = json_decode(wp_remote_retrieve_body($pressable_api_response_put_request) , true);

               
            }
            else
            {
                function pressable_edge_cache_purge_notice_error()
                {
                    $screen = get_current_screen();
                    if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                    $user = $GLOBALS['current_user'];
                    $class = 'notice notice-error is-dismissible';
                    $message = __('Something went wrong try again. If it persists, uninstall/reinstall the plugin', 'pressable_cache_management', $user->display_name);
                    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                }
                add_action('admin_notices', 'pressable_edge_cache_purge_notice_error');
                return false;
            }
        }
    }

    add_action('init', 'pcm_pressable_edge_cache_purge');

}
elseif (isset($_POST['purge_edge_cache_nonce']))
{

    function pressable_edge_cache_purge__button()
    {
        $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');

        if (isset($api_auth_tab_options['pressable_site_id'], $api_auth_tab_options['api_client_id'], $api_auth_tab_options['api_client_secret']) && !empty($api_auth_tab_options['pressable_site_id'] || $api_auth_tab_options['api_client_id'] || $api_auth_tab_options['api_client_secret']))
        {

            if (wp_verify_nonce($_POST['purge_edge_cache_nonce'], 'purge_edge_cache_nonce'))
            {
                $client_id = $api_auth_tab_options['api_client_id'];
                $client_secret = $api_auth_tab_options['api_client_secret'];

                $response = wp_remote_post('https://my.pressable.com/auth/token', array(
                    'body' => array(
                        'client_id' => $client_id,
                        'client_secret' => $client_secret,
                        'grant_type' => 'client_credentials'
                    )
                ));

                $results = json_decode(wp_remote_retrieve_body($response) , true);

                if (is_wp_error($response))
                {
                    return;
                }

                if (!$results)
                {
                    return;
                }

                $token_expires_in = $results["expires_in"];
                $access_token_expiry = time() + $token_expires_in;
                update_option('access_token_expiry', $access_token_expiry);

                set_transient('access_token', $results["access_token"], $token_expires_in);

                $access_token = get_transient('access_token');
                $pressable_site_id = $api_auth_tab_options['pressable_site_id'];
                $pressable_api_request_headers = array(
                    'Authorization' => 'Bearer ' . ($access_token)
                );
                $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/edge-cache';

                $pressable_api_response_post_request = wp_remote_request($pressable_api_request_url, array(
                    'method' => 'DELETE',
                    'headers' => $pressable_api_request_headers,
                ));

                $response = json_decode(wp_remote_retrieve_body($pressable_api_response_post_request) , true);

                if ($response["message"] == "Success")
                {
                    function pressable_edge_cache_purge_notice_success_msg()
                    {
                        $screen = get_current_screen();
                        $class = 'notice notice-success is-dismissible';
                        $message = __('Edge Cache Purged Successfully :)', 'pressable_cache_management');
                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                    }
                    add_action('admin_notices', 'pressable_edge_cache_purge_notice_success_msg');

                    $edge_cache_purged_time = date(' jS F Y  g:ia') . "\nUTC";
                    update_option('edge-cache-purge-time-stamp', $edge_cache_purged_time);
                    //Check if Edge Cache is disabled from MyPressable control panel
                    
                }
                elseif ($response["errors"][0] == "Edge cache must be enabled")
                {
                    function pressable_edge_cache_purge_notice_warning_msg()
                    {
                        $screen = get_current_screen();
                        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                        $user = $GLOBALS['current_user'];
                        $class = 'notice notice-success is-dismissible';
                        $message = __('We\'ve turned on Edge Cache because it was switched off, you can purge the cache now! 🚀', 'pressable_cache_management', $user->display_name);
                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                    }
                    add_action('admin_notices', 'pressable_edge_cache_purge_notice_warning_msg');


                    // Make an API call to turn on the Edge Cache
                    $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/edge-cache';
                    $pressable_api_response_put_request = wp_remote_request($pressable_api_request_url, array(
                        'method' => 'PUT',
                        'headers' => $pressable_api_request_headers,
                    ));

                    $response = json_decode(wp_remote_retrieve_body($pressable_api_response_put_request) , true);

                }
                else
                {
                    function pressable_edge_cache_purge_notice_error_msg()
                    {
                        $screen = get_current_screen();
                        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                        $user = $GLOBALS['current_user'];
                        $class = 'notice notice-error is-dismissible';
                        $message = __('Something went wrong try again. If it persists, uninstall/reinstall the plugin', 'pressable_cache_management', $user->display_name);
                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                    }
                    add_action('admin_notices', 'pressable_edge_cache_purge_notice_error_msg');
                }
            }
        }
    }
    add_action('init', 'pressable_edge_cache_purge__button');
}
