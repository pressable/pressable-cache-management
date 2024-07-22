<?php //Pressable Cache Management Plugin - API Connection


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}



$pcm_api_authentication_options = get_option('pressable_api_authentication_tab_options');

//Show warning message if credentials is not entered before connecting
if (isset($_POST['connect_api_nonce']))
{
    $pcm_api_authentication_options = get_option('pressable_api_authentication_tab_options');

    //Declear api_client_id and api_client_secret as null if empty in the array
    if (!isset($pcm_api_authentication_options['api_client_id'])) $pcm_api_authentication_options['api_client_id'] = '';
    if (!isset($pcm_api_authentication_options['api_client_secret'])) $pcm_api_authentication_options['api_client_secret'] = '';

    if ($pcm_api_authentication_options['api_client_id'] == null || $pcm_api_authentication_options['api_client_secret'] == null)
    {

        function pressable_api_connection_save_response()
        {
            $screen = get_current_screen();

            //Display admin notice for this plugin page only
            if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
            $user = $GLOBALS['current_user'];
            $class = 'notice notice-error is-dismissible';
            $message = __('Please save all your API credentials first before connecting :(', 'pressable_cache_management');

            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
        }
        add_action('admin_notices', 'pressable_api_connection_save_response');
    }

}

//Add pressable cdn option to the DB if it does not exit already
if ('not-exists' === get_option('cdnenabled', 'not-exists')) {
     add_option('cdnenabled', 'disable');

}

// Add edge-cache-enabled option to the DB if it does not exist already
if ('not-exists' === get_option('edge-cache-enabled', 'not-exists')) {
    add_option('edge-cache-enabled', '');
}

function disconnect_api_nonce()
{

    if (isset($_POST['disconnect_api_nonce']))
    {

        if (!wp_verify_nonce($_POST['disconnect_api_nonce'], 'disconnect_api_nonce'))
        {

            return;

        }
        else
        {

            /******************************************
             * Delete client ID and secret value
             * from the database to diconnect the
             * API connection.
             ******************************************/

            $pressable_site_id = get_option('pressable_site_id');
            $db_auth = array(
                'pressable_site_id' => $pressable_site_id,

            );

            update_option('pressable_api_authentication_tab_options', $db_auth);

            //Clear Client ID and Client Secret field when connection is disconnected
            update_option('pcm_client_id', '');
            update_option('pcm_client_secret', '');

            //Remove the API connection option from the database table
            update_option('pressable_api_admin_notice__status', 'activating');

            if (is_multisite())
            {

                $pressable_site_id = get_option('pressable_site_id');
                $db_auth = array(
                    'pressable_site_id' => $pressable_site_id,

                );

                update_option('pressable_api_authentication_tab_options', $db_auth);

                //Clear Client ID and Client Secret field when connection is disconnected
                update_option('pcm_client_id', '');
                update_option('pcm_client_secret', '');

                //Remove the API connection option from the database table
                update_option('pressable_api_admin_notice__status', 'activating');
                //Show hidden site ID when disconnected from the API
                update_option('pcm_site_id_con_res', 'Not Found');
            }

        }
    }
}
add_action('init', 'disconnect_api_nonce');

// function connect_api_nonce() {
//  if (isset($_POST['connect_api_nonce'])) {
//  if (!wp_verify_nonce($_POST['connect_api_nonce'], 'connect_api_nonce')) {
//   return;
//  }
//  }
//  }
// add_action('init','connect_api_nonce');
if (isset($pcm_api_authentication_options['pressable_site_id'], $pcm_api_authentication_options['api_client_id'], $pcm_api_authentication_options['api_client_secret'], $_POST['connect_api_nonce']) && !empty($pcm_api_authentication_options['pressable_site_id']))
{
    //Defining client id and client secret
    $client_id = $pcm_api_authentication_options['api_client_id'];
    $client_secret = $pcm_api_authentication_options['api_client_secret'];

    //Query the api to auto generate a bearer token
    $response = wp_remote_post('https://my.pressable.com/auth/token', array(
        'body' => array(
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'grant_type' => 'client_credentials'
        )
    ));

    //Handle any errors returned from the API
    if (is_wp_error($response))
    {
        return;
    }

    $results = json_decode(wp_remote_retrieve_body($response) , true);

    //Terminate if no connection
    if (!$results)
    {
        return;
    }

    //Convert array to json format
    $json_results = json_encode($results);

    //Define expires_in and access_token as empty if not found in array
    if (!isset($results["expires_in"])) $results["expires_in"] = '';
    if (!isset($results["access_token"])) $results["access_token"] = '';

    $token_expires_in = $results["expires_in"];
    $access_token = $results["access_token"];

    //Set transient to expire access token in one hour
    set_transient('access_token', $access_token, $token_expires_in);

    //If connection status is empty or incorrect don't display api connection error message
    if ($pcm_api_authentication_options['api_client_id'] == null || $pcm_api_authentication_options['api_client_secret'] == null)
    {

        return;

    }

    //If connection is not successfully show admin notice error
    elseif (in_array("invalid_client", $results))
    {

        //Display admin notice if site is not connected to pressable
        function api_auth_to_connect_admin_notice__error()
        {
            $screen = get_current_screen();

            //Display admin notice for this plugin page only
            if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

            $user = $GLOBALS['current_user'];
            $class = 'notice notice-error';
            $message = __('Your Client ID or Client Secret is incorrect :(', 'pressable_cache_management');

            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));

        }

        add_action('admin_notices', 'api_auth_to_connect_admin_notice__error');

        /**Delete option from the database if the connection is not successfully
         used by admin notice to display and remove notice**/
        delete_option('pressable_api_admin_notice__status', 'activated');

    }
    else
    {

        //Display admin notice only once if connection to Pressable API is successful
        function pressable_api_connect_notice__render_notice($message = '', $classes = 'notice-success')
        {

            if (!empty($message))
            {
                printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
            }
        }






        function pressable_api_connect_notice__admin_notice()
        {

            $pressable_api_display_notice = get_option('pressable_api_admin_notice__status', 'activating');

            if ('activating' === $pressable_api_display_notice && current_user_can('manage_options'))
            {

                add_action('admin_notices', function ()
                {

                    $user = $GLOBALS['current_user'];
                    $message = sprintf('<p><h3>Authentication Successful 👋🏾 🥳 </h3></p>', $user->display_name);

                    pressable_api_connect_notice__render_notice($message, 'notice notice-success is-dismissible');
                });

                update_option('pressable_api_admin_notice__status', 'activated');


              // Sync CDN status with MyPressable Control Panel
            $access_token = get_transient('access_token');
            $pcm_api_authentication_options = get_option('pressable_api_authentication_tab_options');
            $pressable_site_id = $pcm_api_authentication_options['pressable_site_id'];

            $pressable_api_request_headers = array(
                'Authorization' => 'Bearer ' . $access_token
            );

            // CDN endpoint
            $cdn_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/cdn';
            $cdn_api_response = wp_remote_request($cdn_api_request_url, array(
                'method' => 'GET',
                'headers' => $pressable_api_request_headers,
            ));


            // Check if CDN endpoint is avaialble then hide the CDN tab -  see settings-page.php ln 164
            $cdn_status_code = wp_remote_retrieve_response_code($cdn_api_response);
            if ($cdn_status_code == 404) {
                // Check if CDN endpoint exist
                update_option('cdn-api-state', 'Not Found');
                return;
            }


            $cdn_results = wp_remote_retrieve_body($cdn_api_response);
            $cdn_results_data = json_decode($cdn_results, true);


                if (isset($cdn_results_data['data']['cdnEnabled'])) {
                $cdn_enabled = $cdn_results_data['data']['cdnEnabled'];
                if ($cdn_enabled) {
                    update_option('cdnenabled', 'enable');
                } else {
                    update_option('cdnenabled', 'disable');
                }
            }

            
                   // Sync Edge Cache status with MyPressable Control Panel
                  $pressable_api_request_site = 'https://my.pressable.com/v1/sites/' . $pressable_site_id;

                // Connection to the API using WordPress request function to check site status
                $pressable_api_response_site = wp_remote_request($pressable_api_request_site, array(
                    'method' => 'GET',
                    'headers' => $pressable_api_request_headers,
                ));


                 $edge_cache_enabled = json_decode(wp_remote_retrieve_body($pressable_api_response_site) , true);

                if (isset($edge_cache_enabled['data']['edgeCache']))
                { // Check if "edgeCache" key exists
                    if ($edge_cache_enabled['data']['edgeCache'] === "enabled")
                    {
                        // Edge Cache is already enabled, no need to make API calls
                        update_option('edge-cache-status', 'Success');
                        update_option('edge-cache-enabled', 'enabled');

                        return;
                    } else {

                          update_option('edge-cache-enabled', 'disabled');
                    }
                }



      

            }
        }
        add_action('init', 'pressable_api_connect_notice__admin_notice');

    }
}

/******************************
 * Check if Pressable site
 *  exists from the API
 *******************************/

else
{

    // Return false to stop making new api request on page refresh
    if ($response = 'Unauthorized')
    {
        return false;

    }

    //Declare array to prevent error on PHP 8.0
    $results = array(
        "access_token" => ""
    );

}

//Extract access token from the array
$results = extract($results);

//Declare variable if it's not set
if (!isset($access_token))
{
    $access_token = '';

}

$results = $access_token;

//Get access token from database
$access_token = get_transient('access_token');

$pcm_api_authentication_options = get_option('pressable_api_authentication_tab_options');

if ($pcm_api_authentication_options)
{

    $pressable_site_id = $pcm_api_authentication_options['pressable_site_id'];

    //Connecting to Pressable API
    $pressable_api_request_headers = array(
        //Add your Bearer Token
        'Authorization' => 'Bearer ' . ($access_token)
    );
    //Pressable API request URL example: https://my.pressable.com/v1/sites
    $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id;

    //initiating connection to the API using WordPress request function
    $pressable_api_response_post_request = wp_remote_request($pressable_api_request_url, array(
        'method' => 'GET',
        'headers' => $pressable_api_request_headers
    ));

    //Display request message
    $response = wp_remote_retrieve_response_message($pressable_api_response_post_request);

}

//Set Variable as Default
if (!isset($response))
{
    $response = '';

}

//Display error message if site is not found
if ($response == 'Not Found')
{
    //Save response to database
    update_option('pcm_site_id_con_res', $response);

    function pressable_site_id_not_found_notice__error()
    {

        $screen = get_current_screen();

        //Display admin notice for this plugn page only
        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
        // Check for current user to display  admin notice message to only
        if (current_user_can('manage_options'))
        {

            $user = $GLOBALS['current_user'];
            $class = 'notice notice-error';
            $message = __('Site ID not found :(', 'sample-text-domain');
            //update_option( 'pressable_incorrect_site_id', 'activated' );
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));

            update_option('pressable_api_admin_notice__status', 'activating');

            update_option('pcm_site_id_added_activate_notice', 'activating');

        }
    }
    add_action('admin_notices', 'pressable_site_id_not_found_notice__error');

}
//Display success message if site is found
elseif ($response == 'OK')
{

    //Save response to database
    update_option('pcm_site_id_con_res', $response);

    //Display admin notice
    function pcm_site_id_added_admin_notice($message = '', $classes = 'notice-success')
    {

        if (!empty($message))
        {
            printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
        }
    }

    function pcm_site_id_added_notice()
    {

        $pcm_site_id_added_activate_display_notice = get_option('pcm_site_id_added_activate_notice', 'activating');

        if ('activating' === $pcm_site_id_added_activate_display_notice && current_user_can('manage_options'))
        {

            add_action('admin_notices', function ()
            {

                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                $user = $GLOBALS['current_user'];
                $message = sprintf('<p>Site ID added Successfully :)</p>', $user->display_name);

                pcm_site_id_added_admin_notice($message, 'notice notice-success is-dismissible');
            });

            update_option('pcm_site_id_added_activate_notice', 'activated');

        }
    }
    add_action('init', 'pcm_site_id_added_notice');

}
elseif ($response == '')
{

    return;

}
elseif ($response == 'Forbidden')
{

    //Display forbidden admin notice
    function pcm_api_forbidden_admin_notice($message = '', $classes = 'notice-success')
    {

        if (!empty($message))
        {
            printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
        }
    }

    function pcm_api_permission_notice()
    {

        if (current_user_can('manage_options'))
        {

            add_action('admin_notices', function ()
            {

                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                $user = $GLOBALS['current_user'];
                $message = sprintf('<p>Your API credentials permission is incorrect<a href="https://pressable.com/knowledgebase/pressable-cache-management-plugin/#api-authentication"> See how to setup and connect correctly.</a>');

                pcm_api_forbidden_admin_notice($message, 'notice notice-warning is-dismissible');
            });

        }
    }
    add_action('init', 'pcm_api_permission_notice');

}
