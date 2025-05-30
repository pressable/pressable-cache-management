<?php //Pressable Cache Management - Custom function to turn on/off Edge Cache


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

/******************************
 * Activate Edge Cache Option
 *******************************/

$edge_cache = false;

$edge_cache_tab_options = get_option('edge_cache_settings_tab_options');
$object_cache_tab_options = get_option('pressable_cache_management_options');
$api_auth_tab_options = get_option('pressable_api_authentication_tab_options');

/*****************************************************
 * Check if access token is valid and if access token
 * transient is created else it will generate a
 * new access token.
 *****************************************************/

if (isset($_POST['enable_edge_cache_nonce']))
{

    $check_access_token_expiry = get_option('access_token_expiry');

    if (time() < $check_access_token_expiry && false !== get_transient('access_token'))
    {

        function pcm_pressable_enable_edge_cache()
        {

            //verify nonce
            if (wp_verify_nonce($_POST['enable_edge_cache_nonce'], 'enable_edge_cache_nonce'))
            {

                $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');
                // $access_token = $results["access_token"];
                $access_token = get_transient('access_token');
                $pressable_site_id = $api_auth_tab_options['pressable_site_id'];
                //Connecting to Pressable API
                $pressable_api_request_headers = array(
                    'Authorization' => 'Bearer ' . ($access_token)
                );

                /****************************************************************************
                 * Pressable API request URL example: https://my.pressable.com/v1/sites
                 * --------------------------------------------------------------------------
                 * API request to check the edge cache staus to know the state before making a
                 * new request to Pressable API to update it. This will make sure Both the
                 * plugin and MyPressable Control Panel stay in sync with each other.
                 ****************************************************************************/

                $pressable_api_request_site = 'https://my.pressable.com/v1/sites/' . $pressable_site_id;

                // Connection to the API using WordPress request function to check site status
                $pressable_api_response_site = wp_remote_request($pressable_api_request_site, array(
                    'method' => 'GET',
                    'headers' => $pressable_api_request_headers,
                ));

                $site_response = json_decode(wp_remote_retrieve_body($pressable_api_response_site) , true);

                if (isset($site_response['data']['edgeCache']))
                { // Check if "edgeCache" key exists
                    if ($site_response['data']['edgeCache'] === "enabled")
                    {
                        // Edge Cache is already enabled, no need to make API calls
                        update_option('edge-cache-status', 'Success');
                        update_option('edge-cache-enabled', 'enabled');

                        return;
                    }
                }

                //Make API request to enable edge cache if disabled.
                $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/edge-cache';

                //Connection to the API using WordPress request function
                $pressable_api_response_post_request = wp_remote_request($pressable_api_request_url, array(
                    'method' => 'PUT',
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
                    //Check if Edge Cache  status if is enabled
                    update_option('edge-cache-status', 'Success');

                    //Manually added endge cache status. Pressable API does not currently check the status of Edge Cache
                    update_option('edge-cache-enabled', 'enabled');
                }
                elseif ($response["errors"][0] == "Edge cache must be enabled")
                {

                    // Make an API call to turn on the Edge Cache
                    $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/edge-cache';
                    $pressable_api_response_put_request = wp_remote_request($pressable_api_request_url, array(
                        'method' => 'PUT',
                        'headers' => $pressable_api_request_headers,
                        'body' => json_encode(array(
                            'enabled' => $response['enabled']
                        )) ,
                    ));

                    $response = json_decode(wp_remote_retrieve_body($pressable_api_response_put_request) , true);

                }

                //Check array if Edge Cache is activated
                if (in_array(true, $pressable_api_query_response))
                {

                    //Display admin notice when the edge cache is enabled successfully
                    function pressable_edge_cache_purge_cache_notice_warning_msg()
                    {
                        $screen = get_current_screen();

                        //Display admin notice for this plugin page only
                        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                        $user = $GLOBALS['current_user'];
                        $class = 'notice notice-success is-dismissible';
                        $message = __('<h3>Edge Cache enabled! 🎉</h3>Edge Cache provides performance improvements, particularly for Time to First Byte (TTFB),<br>by serving page cache from the nearest server to your website visitors. <br><br><a href="https://pressable.com/knowledgebase/edge-cache/" target="_blank">Learn more about Edge Cache.</a>', 'pressable_cache_management');
                        echo '<div class="notice notice-success">' . $message . '</div>';

                    }
                    add_action('admin_notices', 'pressable_edge_cache_purge_cache_notice_warning_msg');

                }
            }
        }
        add_action('init', 'pcm_pressable_enable_edge_cache');

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

        // function pcm_pressable_enable_edge_cache_check() {
        // if ($_POST['enable_edge_cache_nonce'] && wp_verify_nonce($_POST['enable_edge_cache_nonce'], 'enable_edge_cache_nonce')) {
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

        /****************************************************************************
         * Pressable API request URL example: https://my.pressable.com/v1/sites
         * --------------------------------------------------------------------------
         * API request to check the edge cache staus to know the state before making a
         * new request to Pressable API to update it. This will make sure Both the
         * plugin and MyPressable Control Panel stay in sync with each other.
         ****************************************************************************/

        $pressable_api_request_site = 'https://my.pressable.com/v1/sites/' . $pressable_site_id;

        // Connection to the API using WordPress request function to check site status
        $pressable_api_response_site = wp_remote_request($pressable_api_request_site, array(
            'method' => 'GET',
            'headers' => $pressable_api_request_headers,
        ));

        $site_response = json_decode(wp_remote_retrieve_body($pressable_api_response_site) , true);

        if (isset($site_response['data']['edgeCache']))
        { // Check if "edgeCache" key exists
            if ($site_response['data']['edgeCache'] === "enabled")
            {
                // Edge Cache is already enabled, no need to make API calls
                update_option('edge-cache-status', 'Success');
                update_option('edge-cache-enabled', 'enabled');

                return;
            }
        }

        //Make API request to enable edge cache if disabled.
        $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/edge-cache';

        //Connection to the API using WordPress request function
        $pressable_api_response_post_request = wp_remote_request($pressable_api_request_url, array(
            'method' => 'PUT',
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
                $message = __('Something went wrong try again. If it persist uninstall/reinstall the plugin.', 'pressable_cache_management', $user->display_name);

                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
            }
            add_action('admin_notices', 'pcm_pressable_api_error_msg');
        }

        if ($pressable_api_query_response["message"] == "Success")
        {
            //Check if Edge Cache  status if is enabled
            update_option('edge-cache-status', 'Success');

            //Manually added endge cache status. Pressable API does not currently check the status of Edge Cache
            update_option('edge-cache-enabled', 'enabled');

        }
        else
        {

            return;
        }

        //Check array if Edge Cache is activated
        if (in_array(true, $pressable_api_query_response))
        {

            //Display admin notice only once if connection to Pressable API is successful
            function pressable_api_enable_edge_cache_connection_admin_notice()
            {
                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                $user = $GLOBALS['current_user'];
                $class = 'notice notice-success is-dismissible';
                $message = __('<h3>Edge Cache enabled! 🎉</h3>Edge Cache provides performance improvements, particularly for Time to First Byte (TTFB),<br>by serving page cache from the nearest server to your website visitors. <br><br><a href="https://pressable.com/knowledgebase/edge-cache/" target="_blank">Learn more about Edge Cache.</a>', 'pressable_cache_management');
                echo '<div class="notice notice-success">' . $message . '</div>';

                echo '<div class="notice notice-warning">' . __('<strong>Notice:</strong> It is recommended to disable the Pressable CDN if you are using Edge Cache to prevent extra HTTP requests.', 'pressable_cache_management') . '</div>';

            }
            add_action('admin_notices', 'pressable_api_enable_edge_cache_connection_admin_notice');

        }
    }

    /******************************
     * Deactivate edge_cache Option
     *******************************/

    /*****************************************************
     * Check if access token is valid and if access token
     * transient is created else it will generate a
     * new access token.
     *****************************************************/

}
if (isset($_POST['disable_edge_cache_nonce']))
{

    $check_access_token_expiry = get_option('access_token_expiry');

    if (time() < $check_access_token_expiry && false !== get_transient('access_token'))
    {

        function pcm_pressable_disable_edge_cache()
        {

            //verify nonce
            if (wp_verify_nonce($_POST['disable_edge_cache_nonce'], 'disable_edge_cache_nonce'))
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

                /****************************************************************************
                 * Pressable API request URL example: https://my.pressable.com/v1/sites
                 * --------------------------------------------------------------------------
                 * API request to check the edge cache staus to know the state before making a
                 * new request to Pressable API to update it. This will make sure Both the
                 * plugin and MyPressable Control Panel stay in sync with each other.
                 ****************************************************************************/

                $pressable_api_request_site = 'https://my.pressable.com/v1/sites/' . $pressable_site_id;

                // Connection to the API using WordPress request function to check site status
                $pressable_api_response_site = wp_remote_request($pressable_api_request_site, array(
                    'method' => 'GET',
                    'headers' => $pressable_api_request_headers,
                ));

                $site_response = json_decode(wp_remote_retrieve_body($pressable_api_response_site) , true);

                if (isset($site_response['data']['edgeCache']))
                { // Check if "edgeCache" key exists
                    if ($site_response['data']['edgeCache'] === "disabled")
                    {
                        // Edge Cache is already enabled, no need to make API calls
                        update_option('edge-cache-status', 'Success');
                        update_option('edge-cache-enabled', 'disabled');

                        return;
                    }
                }

                //Make API request to disable edge cache if enabled.
                $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/edge-cache';

                //Connection to the API using WordPress request function
                $pressable_api_response_post_request = wp_remote_request($pressable_api_request_url, array(
                    'method' => 'PUT',
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
                    //Check if Edge Cache  status if is disabled
                    update_option('edge-cache-status', 'Success');

                    //Manually added endge cache status. Pressable API does not currently check the status of Edge Cache
                    update_option('edge-cache-enabled', 'disabled');

                }
                else
                {

                    return;
                }

                //Check array if Edge Cache is activated
                if (in_array(false, $pressable_api_query_response))
                {

                    //Display admin notice when the edge cache is enabled successfully
                    function pressable_edge_cache_purge_cache_notice_warning_disable()
                    {
                        $screen = get_current_screen();

                        //Display admin notice for this plugin page only
                        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                        $user = $GLOBALS['current_user'];
                        $class = 'notice notice-success is-dismissible';
                        $message = __('Edge Cache Deactivated.', 'pressable_cache_management');

                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                    }
                    add_action('admin_notices', 'pressable_edge_cache_purge_cache_notice_warning_disable');

                }
            }
        }
        add_action('init', 'pcm_pressable_disable_edge_cache');

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
        $edge_cache = false;

        $edge_cache_tab_options = get_option('edge_cache_settings_tab_options');
        $object_cache_tab_options = get_option('pressable_cache_management_options');
        $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');

        if (isset($_POST['disable_edge_cache_nonce']))
        {

            function pcm_pressable_disable_edge_cache_button()
            {

                //verify nonce
                if (wp_verify_nonce($_POST['disable_edge_cache_nonce'], 'disable_edge_cache_nonce'))
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

                    /****************************************************************************
                     * Pressable API request URL example: https://my.pressable.com/v1/sites
                     * --------------------------------------------------------------------------
                     * API request to check the edge cache staus to know the state before making a
                     * new request to Pressable API to update it. This will make sure Both the
                     * plugin and MyPressable Control Panel stay in sync with each other.
                     ****************************************************************************/

                    $pressable_api_request_site = 'https://my.pressable.com/v1/sites/' . $pressable_site_id;

                    // Connection to the API using WordPress request function to check site status
                    $pressable_api_response_site = wp_remote_request($pressable_api_request_site, array(
                        'method' => 'GET',
                        'headers' => $pressable_api_request_headers,
                    ));

                    $site_response = json_decode(wp_remote_retrieve_body($pressable_api_response_site) , true);

                    if (isset($site_response['data']['edgeCache']))
                    { // Check if "edgeCache" key exists
                        if ($site_response['data']['edgeCache'] === "disabled")
                        {
                            // Edge Cache is already disabled, no need to make API calls
                            update_option('edge-cache-status', 'Success');
                            update_option('edge-cache-enabled', 'disabled');

                            return;
                        }
                    }

                    //Make API request to disable edge cache if enabled.
                    $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/edge-cache';

                    //initiating connection to the API using WordPress request function
                    $pressable_api_response_post_request = wp_remote_post($pressable_api_request_url, array(
                        'method' => 'PUT',
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
                        //Check if Edge Cache  status if is disabled
                        update_option('edge-cache-status', 'Success');

                        //Manually added edge cache status. Pressable API does not currently check the status of Edge Cache
                        update_option('edge-cache-enabled', 'disabled');

                    }
                    else
                    {

                        return;
                    }

                    //Display admin notice only once if connection to Pressable API is successful
                    function pressable_api_deactivate_edge_cache_connection_admin_notice()
                    {
                        $screen = get_current_screen();

                        //Display admin notice for this plugin page only
                        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                        $user = $GLOBALS['current_user'];
                        $class = 'notice notice-success is-dismissible';
                        $message = __('Edge Cache Deactivated.', 'pressable_cache_management', $user->display_name);

                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                    }
                    add_action('admin_notices', 'pressable_api_deactivate_edge_cache_connection_admin_notice');

                }
            }
        }
        add_action('init', 'pcm_pressable_disable_edge_cache_button');

    }

}

//Add nagging admin notice if edge cache is disabled
// if (get_option('edge-cache-enabled') === 'enabled')
// {
//     //Display admin notice only once if connection to Pressable API is successful
//     function pressable_api_deactivate_edge_cache_connection_admin_notice_nag()
//     {
//         $screen = get_current_screen();
//         //Display admin notice for this plugin page only
//         if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
//         $user = $GLOBALS['current_user'];
//         $class = 'notice notice-success is-dismissible';
//         $message = __('Edge Cache provides performance improvements, particularly to the Time to First Byte (TTFB), by serving page cache directly from the closest server available to a site’s visitors.', 'pressable_cache_management', $user->display_name);
//         printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
//     }
//     add_action('admin_notices', 'pressable_api_deactivate_edge_cache_connection_admin_notice_nag');
// }
