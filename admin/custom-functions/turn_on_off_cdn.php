<?php //Pressable Cache Management - Custom function to turn on/off CDN


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}


/******************************
 * Activate CDN Option
 *******************************/

$cdn = false;

$cdn_tab_options = get_option('cdn_settings_tab_options');
$object_cache_tab_options = get_option('pressable_cache_management_options');
$api_auth_tab_options = get_option('pressable_api_authentication_tab_options');

/*****************************************************
 * Check if access token is valid and if access token
 * transient is created else it will generate a
 * new access token.
 *****************************************************/

if (isset($_POST['enable_cdn_nonce']))
{

    $check_access_token_expiry = get_option('access_token_expiry');

    if (time() < $check_access_token_expiry && false !== get_transient('access_token'))
    {

        function pcm_pressable_enable_cdn__cache()
        {

            //verify nonce
            if (wp_verify_nonce($_POST['enable_cdn_nonce'], 'enable_cdn_nonce'))
            {

                $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');
                // $access_token = $results["access_token"];
                $access_token = get_transient('access_token');
                $pressable_site_id = $api_auth_tab_options['pressable_site_id'];
                //Connecting to Pressable API
                $pressable_api_request_headers = array(
                    'Authorization' => 'Bearer ' . ($access_token)
                );
                //Pressable API request URL example: https://my.pressable.com/v1/sites
                $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/cdn';

                //Connection to the API using WordPress request function
                $pressable_api_response_post_request = wp_remote_request($pressable_api_request_url, array(
                    'method' => 'POST',
                    'headers' => $pressable_api_request_headers,
                    //  'timeout'   => 0.01,
                    //     'blocking'  => false
                    
                ));

                //Display request message
                // $response = wp_remote_retrieve_response_message($pressable_api_response_post_request);
                $pressable_api_query_response = json_decode(wp_remote_retrieve_body($pressable_api_response_post_request) , true);

                //Return error message if API response return nothing
                if ($pressable_api_query_response == null)
                {
                    function pcm_pressable_api_error_msg()
                    {
                        $screen = get_current_screen();

                        //Display admin notice for this plugin page only
                        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                        $user = $GLOBALS['current_user'];
                        $class = 'notice notice-error is-dismissible';
                        $message = __('Something went wrong try again. If it persist uninstall/reinstall the plugin.', 'pressable_cache_management', $user->display_name);
                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                    }
                    add_action('admin_notices', 'pcm_pressable_api_error_msg');
                }

                if ($pressable_api_query_response["message"] == "Success")
                {

                    update_option('cdnenabled', 'enable');

                }
                else
                {

                    return;
                }

                //Check array if CDN is activated
                if (in_array(true, $pressable_api_query_response))
                {

                    //Display admin notice when the cdn is enabled successfully
                    function pressable_cdn_purge_cache_notice_warning_msg()
                    {
                        $screen = get_current_screen();

                        //Display admin notice for this plugin page only
                        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                        $user = $GLOBALS['current_user'];
                        $class = 'notice notice-success is-dismissible';
                        $message = __('CDN Enabled Successfully :)', 'pressable_cache_management', $user->display_name);

                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                    }
                    add_action('admin_notices', 'pressable_cdn_purge_cache_notice_warning_msg');

                }
            }
        }
        add_action('init', 'pcm_pressable_enable_cdn__cache');

        /********************************************
         * Return false if access token has not expired
         * this prevent regenration of access token
         * before expiry time
         *********************************************/
        return false;

    }
    else
    {

        /******************************
         * Generate new access token
         * the previous one has expired
         *******************************/

        // function pcm_pressable_enable_cdn_check() {
        // if ($_POST['enable_cdn_nonce'] && wp_verify_nonce($_POST['enable_cdn_nonce'], 'enable_cdn_nonce')) {
        //Defining client id client secret and site id
        $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');
        $client_id = $api_auth_tab_options['api_client_id'];
        $client_secret = $api_auth_tab_options['api_client_secret'];
        $pressable_site_id = $api_auth_tab_options['pressable_site_id'];

        $response = wp_remote_post('https://my.pressable.com/auth/token', array(
            'body' => array(
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'grant_type' => 'client_credentials'
            )
        ));

        $results = json_decode(wp_remote_retrieve_body($response) , true);

        // handle any errors returned from the API
        if (is_wp_error($response))
        {
            return;
        }

        //Display admin notice error messsage if connection unsuccessful
        if (!$results)
        {

            function pressable_api_admin_notice_connection_failure()
            {
                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                $user = $GLOBALS['current_user'];
                $class = 'notice notice-error is-dismissible';
                $message = __('Connection failure try again :(', 'pressable_cache_management', $user->display_name);

                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
            }
            add_action('admin_notices', 'pressable_api_admin_notice_connection_failure');

        }

        //Define index to prevent error message in the array
        $key = array(
            "error_description" => "error_description"
        );


        //Set transient to expire access token in one hour
        $access_token_expiry = time() + $results["expires_in"];
        update_option('access_token_expiry', $access_token_expiry);

        //Set session to count token expiry time
        set_transient('access_token', $results["access_token"], $access_token_expiry);

        //Check if connection was successfuly
        if (!isset($results["access_token"]))
        {

            //Declearing index variable for access_token
            $results = array(
                "access_token" => "invalid_token"
            );

        }

        //$access_token = $results["access_token"];
        $access_token = get_transient('access_token');
        $pressable_site_id = $api_auth_tab_options['pressable_site_id'];
        //Connecting to Pressable API
        $pressable_api_request_headers = array(

            'Authorization' => 'Bearer ' . ($access_token)
        );
        //Pressable API request URL example: https://my.pressable.com/v1/sites
        $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/cdn';

        //Connection to the API using WordPress request function
        $pressable_api_response_post_request = wp_remote_request($pressable_api_request_url, array(
            'method' => 'POST',
            'headers' => $pressable_api_request_headers,
            //  'timeout'   => 0.01,
            //     'blocking'  => false
            
        ));

        //Display API request message
        // $response = wp_remote_retrieve_response_message($pressable_api_response_post_request);
        $pressable_api_query_response = json_decode(wp_remote_retrieve_body($pressable_api_response_post_request) , true);

        //Return error message if API response return nothing
        if ($pressable_api_query_response == null)
        {
            function pcm_pressable_api_error_msg()
            {
                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                $user = $GLOBALS['current_user'];
                $class = 'notice notice-error is-dismissible';
                $message = __('Something went wrong try again. If it persist uninstall/reinstall the plugin..', 'pressable_cache_management', $user->display_name);

                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
            }
            add_action('admin_notices', 'pcm_pressable_api_error_msg');
        }

        if ($pressable_api_query_response["message"] == "Success")
        {

            update_option('cdnenabled', 'enable');

        }
        else
        {

            return;
        }

        //Check array if CDN is activated
        if (in_array(true, $pressable_api_query_response))
        {

            //Display admin notice only once if connection to Pressable API is successful
            function pressable_api_enable_cdn_connection_admin_notice()
            {
                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                $user = $GLOBALS['current_user'];
                $class = 'notice notice-success is-dismissible';
                $message = __('CDN Enabled Successfully :)', 'pressable_cache_management', $user->display_name);

                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
            }
            add_action('admin_notices', 'pressable_api_enable_cdn_connection_admin_notice');

        }
    }

    // }
    //  }
    //  add_action('init', 'pcm_pressable_enable_cdn_check');
    /******************************
     * Deactivate CDN Option
     *******************************/

    /*******************************************************
     * Check if access token is valid and if access token
     * transient is created else it will generate a
     * new access token.
     *****************************************************/

}
if (isset($_POST['disable_cdn_nonce']))
{

    $check_access_token_expiry = get_option('access_token_expiry');

    if (time() < $check_access_token_expiry && false !== get_transient('access_token'))
    {

        function pcm_pressable_disable_cdn__cache()
        {

            //verify nonce
            if (wp_verify_nonce($_POST['disable_cdn_nonce'], 'disable_cdn_nonce'))
            {

                $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');
                // $access_token = $results["access_token"];
                $access_token = get_transient('access_token');
                $pressable_site_id = $api_auth_tab_options['pressable_site_id'];
                //Connecting to Pressable API
                $pressable_api_request_headers = array(
                    //Add your Bearer Token
                    'Authorization' => 'Bearer ' . ($access_token)
                );
                //Pressable API request URL example: https://my.pressable.com/v1/sites
                $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/cdn';

                //Connection to the API using WordPress request function
                $pressable_api_response_post_request = wp_remote_request($pressable_api_request_url, array(
                    'method' => 'DELETE',
                    'headers' => $pressable_api_request_headers,
                    //  'timeout'   => 0.01,
                    //     'blocking'  => false
                    
                ));

                //Display request message
                // $response = wp_remote_retrieve_response_message($pressable_api_response_post_request);
                $pressable_api_query_response = json_decode(wp_remote_retrieve_body($pressable_api_response_post_request) , true);

                //Return error message if API response return nothing
                if ($pressable_api_query_response == null)
                {
                    function pcm_pressable_api_error_msg()
                    {
                        $screen = get_current_screen();

                        //Display admin notice for this plugin page only
                        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                        $user = $GLOBALS['current_user'];
                        $class = 'notice notice-error is-dismissible';
                        $message = __('Something went wrong try again. If it persist uninstall/reinstall the plugin.', 'pressable_cache_management', $user->display_name);

                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                    }
                    add_action('admin_notices', 'pcm_pressable_api_error_msg');
                }

                if ($pressable_api_query_response["message"] == "Success")
                {

                    update_option('cdnenabled', 'disable');

                }
                else
                {

                    return;
                }

                //Check array if CDN is activated
                if (in_array(false, $pressable_api_query_response))
                {

                    //Display admin notice when the cdn is enabled successfully
                    function pressable_cdn_purge_cache_notice_warning_disable()
                    {
                        $screen = get_current_screen();

                        //Display admin notice for this plugin page only
                        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                        $user = $GLOBALS['current_user'];
                        $class = 'notice notice-warning is-dismissible';
                        $message = __('CDN Deactivated - It is always recommended to turn on your CDN for best caching experience.', 'pressable_cache_management');

                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                    }
                    add_action('admin_notices', 'pressable_cdn_purge_cache_notice_warning_disable');

                }
            }
        }
        add_action('init', 'pcm_pressable_disable_cdn__cache');

        /********************************************
         * Return false if access token has not expired
         * this prevent regenration of access token
         * before expiry time
         *********************************************/
        return false;

    }
    else
    {

        /******************************
         * Generate new access token if
         * the previous one has expired
         *******************************/
        $cdn = false;

        $cdn_tab_options = get_option('cdn_settings_tab_options');
        $object_cache_tab_options = get_option('pressable_cache_management_options');
        $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');

        if (isset($_POST['disable_cdn_nonce']))
        {

            function pcm_pressable_disable_cdn__cache_button()
            {

                //verify nonce
                if (wp_verify_nonce($_POST['disable_cdn_nonce'], 'disable_cdn_nonce'))
                {

                    //Defining client id and client secret
                    $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');
                    $client_id = $api_auth_tab_options['api_client_id'];
                    $client_secret = $api_auth_tab_options['api_client_secret'];

                    //Query the api to auto generate bearer token
                    $response = wp_remote_post('https://my.pressable.com/auth/token', array(
                        'body' => array(
                            'client_id' => $client_id,
                            'client_secret' => $client_secret,
                            'grant_type' => 'client_credentials'
                        )
                    ));

                    $results = json_decode(wp_remote_retrieve_body($response) , true);

                    // handle any errors returned from the API
                    if (is_wp_error($response))
                    {
                        return;
                    }

                    //If the query fils display admin notice error messsage
                    if (!$results)
                    {

                        function pressable_api_admin_notice__connection_failure()
                        {

                            $screen = get_current_screen();

                            //Display admin notice for this plugin page only
                            if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                            //Check for current user to display  admin notice message to only
                            if (current_user_can('manage_options'))
                            {

                                $user = $GLOBALS['current_user'];
                                $class = 'notice notice-error';
                                $message = __('Connection failure try again :(', 'pressable_cache_management', $user->display_name);

                                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                            }
                        }
                        add_action('admin_notices', 'pressable_api_admin_notice__connection_failure');
                    }

                    //Define index for error messahe in the array
                    $key = array(
                        "error_description" => "error_description"
                    );


                    //terminate process if not connected to pressable api
                    if (in_array("invalid_client", $results))
                    {

                        return;
                    }

                    //Set transient to expire access token in one hour
                    $access_token_expiry = time() + $results["expires_in"];
                    update_option('access_token_expiry', $access_token_expiry);

                    //Set session to count token expiry time
                    set_transient('access_token', $results["access_token"], $access_token_expiry);

                    //Check if connection was successfuly
                    if (!isset($results["access_token"]))
                    {

                        //Declearing index variable for access_token
                        $results = array(
                            "access_token" => "invalid_token"
                        );

                    }

                    /*********************************************************
                     * Make API request to Pressable
                     * Pressable API Documentation https://my.pressable.com/v1
                     *
                     * Return HTTP Request
                     *********************************************************/

                    // $access_token = $results["access_token"];
                    $access_token = get_transient('access_token');
                    $pressable_site_id = $api_auth_tab_options['pressable_site_id'];
                    //Connecting to Pressable API
                    $pressable_api_request_headers = array(

                        'Authorization' => 'Bearer ' . ($access_token)
                    );
                    //Pressable API request URL example: https://my.pressable.com/v1/sites
                    $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/cdn';

                    //initiating connection to the API using WordPress request function
                    $pressable_api_response_post_request = wp_remote_post($pressable_api_request_url, array(
                        'method' => 'DELETE',
                        'headers' => $pressable_api_request_headers,
                        //  'timeout'   => 0.01,
                        //     'blocking'  => false
                        
                    ));

                    //Display request message
                    // $response = wp_remote_retrieve_response_message($pressable_api_response_post_request);
                    $pressable_api_query_response = json_decode(wp_remote_retrieve_body($pressable_api_response_post_request) , true);

                    //Return error message if API response return nothing
                    if ($pressable_api_query_response == null)
                    {
                        function pcm_pressable_api_error_msg()
                        {
                            $screen = get_current_screen();

                            //Display admin notice for this plugin page only
                            if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                            $user = $GLOBALS['current_user'];
                            $class = 'notice notice-error is-dismissible';
                            $message = __('Something went wrong try again. If it persist uninstall/reinstall the plugin..', 'pressable_cache_management', $user->display_name);

                            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                        }
                        add_action('admin_notices', 'pcm_pressable_api_error_msg');
                    }

                    if ($pressable_api_query_response["message"] == "Success")
                    {

                        update_option('cdnenabled', 'disable');

                    }
                    else
                    {

                        return;
                    }

                    //Display admin notice only once if connection to Pressable API is successful
                    function pressable_api_deactivate_cdn_connection_admin_notice()
                    {
                        $screen = get_current_screen();

                        //Display admin notice for this plugin page only
                        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                        $user = $GLOBALS['current_user'];
                        $class = 'notice notice-warning is-dismissible';
                        $message = __('CDN Deactivated - It is always recommened to turn on your CDN for best caching experience.', 'pressable_cache_management', $user->display_name);

                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                    }
                    add_action('admin_notices', 'pressable_api_deactivate_cdn_connection_admin_notice');

                }
            }
        }
        add_action('init', 'pcm_pressable_disable_cdn__cache_button');

    }

}

//Add nagging admin notice if CDN is deactivated
//Commeneted code out as Edge Cache will replace CDN
// if (get_option('cdnenabled') === 'disable')
// {

//     //Display admin notice only once if connection to Pressable API is successful
//     function pressable_api_deactivate_cdn_connection_admin_notice_nag()
//     {
//         $screen = get_current_screen();

//         //Display admin notice for this plugin page only
//         if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
//         $user = $GLOBALS['current_user'];
//         $class = 'notice notice-warning is-dismissible';
//         $message = __('CDN Deactivated - It is always recommened to turn on your CDN for best caching experience.', 'pressable_cache_management', $user->display_name);

//         printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
//     }
//     add_action('admin_notices', 'pressable_api_deactivate_cdn_connection_admin_notice_nag');

// }
