<?php // Pressable Cache Management - Purge CDN Cache

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

if (isset($_POST['purge_cache_nonce']) && time() < $check_access_token_expiry && false !== get_transient('access_token'))
{

    function pcm_pressable_cdn_purge__cache()
    {

        $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');

        if (wp_verify_nonce($_POST['purge_cache_nonce'], 'purge_cache_nonce'))
        {

            // $access_token = $results["access_token"];
            $access_token = get_transient('access_token');
            $pressable_site_id = $api_auth_tab_options['pressable_site_id'];
            //Connecting to Pressable API
            $pressable_api_request_headers = array(
                //Add your Bearer Token
                'Authorization' => 'Bearer ' . ($access_token)
            );
            //Pressable API request URL example: https://my.pressable.com/v1/sites
            $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/cache';

            //initiating connection to the API using WordPress request function
            $pressable_api_response_post_request = wp_remote_request($pressable_api_request_url, array(
                'method' => 'DELETE',
                'headers' => $pressable_api_request_headers,
                //  'timeout'   => 0.01,
                //     'blocking'  => false
                
            ));

            //Display request message
            // $response = wp_remote_retrieve_response_message($pressable_api_response_post_request);
            $response = json_decode(wp_remote_retrieve_body($pressable_api_response_post_request) , true);

            if ($response["message"] == "Success")
            {

                //Display admin notice when the cache is purges successfully
                function pressable_cdn_purge_cache_notice_success()
                {
                    $class = 'notice notice-success is-dismissible';
                    $message = __('CDN Purged Successfully :)', 'pressable_cache_management');

                    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                }
                add_action('admin_notices', 'pressable_cdn_purge_cache_notice_success');

                //Save time stamp to database if cdn cache is flushed successfully.
                $cdn_purged_time = date(' jS F Y  g:ia') . "\nUTC";

                update_option('cdn-cache-purge-time-stamp', $cdn_purged_time);

            }
            elseif ($response["errors"][0] == "CDN can only be purged once per minute")
            {

                //Display admin notice when the cdn cache is flushed more than once per minute
                function pressable_cdn_purge_cache_notice_warning()
                {
                    $screen = get_current_screen();
                    if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                    $user = $GLOBALS['current_user'];
                    $class = 'notice notice-warning is-dismissible';
                    $message = __('CDN can only be purged once per minute :(', 'pressable_cache_management', $user->display_name);

                    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                }
                add_action('admin_notices', 'pressable_cdn_purge_cache_notice_warning');

            }
            else
            {

                function pressable_cdn_purge_cache_notice_error()
                {
                    $screen = get_current_screen();
                    if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                    $user = $GLOBALS['current_user'];
                    $class = 'notice notice-error is-dismissible';
                    $message = __('Something went wrong try again. If it persist uninstall/reinstall the plugin', 'pressable_cache_management', $user->display_name);

                    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                }
                add_action('admin_notices', 'pressable_cdn_purge_cache_notice_error');

                //return false if access token has not expired
                return false;

            }
        }

    }

    add_action('init', 'pcm_pressable_cdn_purge__cache');

    /******************************
     * Generate new access token if
     * the previous one has expired
     ******************************/

}
elseif (isset($_POST['purge_cache_nonce']))
{

    function pressable_cdn_purge__button()
    {

        $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');

        if (isset($api_auth_tab_options['pressable_site_id'], $api_auth_tab_options['api_client_id'], $api_auth_tab_options['api_client_secret']) && !empty($api_auth_tab_options['pressable_site_id'] || $api_auth_tab_options['api_client_id'] || $api_auth_tab_options['api_client_secret']))
        {

            if (wp_verify_nonce($_POST['purge_cache_nonce'], 'purge_cache_nonce'))
            {

                //Defining client id and client secret
                $client_id = $api_auth_tab_options['api_client_id'];
                $client_secret = $api_auth_tab_options['api_client_secret'];

                //Generating new access token
                $response = wp_remote_post('https://my.pressable.com/auth/token', array(
                    'body' => array(
                        'client_id' => $client_id,
                        'client_secret' => $client_secret,
                        'grant_type' => 'client_credentials'
                    )
                ));

                $results = json_decode(wp_remote_retrieve_body($response) , true);

                // Handle any errors returned from the API
                if (is_wp_error($response))
                {
                    return;
                }
                //Terminate if no connection
                if (!$results)
                {
                    return;
                }

                //Convert array to json format
                $results = json_decode($results, true);

                /******************************************
                 * Set transient to expire access token in
                 * one hour by default the Pressable API
                 * access token expires in 1 hour
                 ******************************************/

                $token_expires_in = $results["expires_in"];
                $access_token_expiry = time() + $token_expires_in;
                update_option('access_token_expiry', $access_token_expiry);

                //Set transient to count token expiry time
                set_transient('access_token', $results["access_token"], $token_expires_in);

                // $access_token = $results["access_token"];
                $access_token = get_transient('access_token');
                $pressable_site_id = $api_auth_tab_options['pressable_site_id'];
                //Connecting to Pressable API
                $pressable_api_request_headers = array(
                    //Add your Bearer Token
                    'Authorization' => 'Bearer ' . ($access_token)
                );
                //Pressable API request URL example: https://my.pressable.com/v1/sites
                $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/cache';

                //initiating connection to the API using WordPress request function
                $pressable_api_response_post_request = wp_remote_request($pressable_api_request_url, array(
                    'method' => 'DELETE',
                    'headers' => $pressable_api_request_headers,
                    //  'timeout'   => 0.01,
                    //     'blocking'  => false
                    
                ));

                //Display request message
                // $response = wp_remote_retrieve_response_message($pressable_api_response_post_request);
                $response = json_decode(wp_remote_retrieve_body($pressable_api_response_post_request) , true);

                if ($response["message"] == "Success")
                {

                    //Display admin notice when the cache is purges successfully
                    function pressable_cdn_purge_cache_notice_success_msg()
                    {
                        $screen = get_current_screen();
                        $class = 'notice notice-success is-dismissible';
                        $message = __('CDN Purged Successfully :)', 'pressable_cache_management');

                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                    }
                    add_action('admin_notices', 'pressable_cdn_purge_cache_notice_success_msg');

                    //Save time stamp to database if cdn cache is flushed successfully.
                    $cdn_purged_time = date(' jS F Y  g:ia') . "\nUTC";

                    update_option('cdn-cache-purge-time-stamp', $cdn_purged_time);

                }
                elseif ($response["errors"][0] == "CDN can only be purged once per minute")
                {

                    //Display admin notice when the cdn cache is flushed more than once per minute
                    function pressable_cdn_purge_cache_notice_warning_msg()
                    {
                        $screen = get_current_screen();
                        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                        $user = $GLOBALS['current_user'];
                        $class = 'notice notice-warning is-dismissible';
                        $message = __('CDN can only be purged once per minute :(', 'pressable_cache_management', $user->display_name);

                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                    }
                    add_action('admin_notices', 'pressable_cdn_purge_cache_notice_warning_msg');

                }
                else
                {

                    function pressable_cdn_purge_cache_notice_error_msg()
                    {
                        $screen = get_current_screen();
                        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                        $user = $GLOBALS['current_user'];
                        $class = 'notice notice-error is-dismissible';
                        $message = __('Something went wrong try again :(', 'pressable_cache_management', $user->display_name);

                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                    }
                    add_action('admin_notices', 'pressable_cdn_purge_cache_notice_error_msg');
                }

            }

        }

    }
    add_action('init', 'pressable_cdn_purge__button');

}
