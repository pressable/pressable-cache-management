<?php //Pressable Cache Management Plugin - API Connection

$authentication_options = get_option('pressable_api_authentication_tab_options');

//Show warning message if credentials is not entered before connecting
if (isset($_POST['connect_api_nonce']))
{
    $authentication_options = get_option('pressable_api_authentication_tab_options');

    //Declear api_client_id and api_client_secret as null if empty in the array
    if (!isset($authentication_options['api_client_id'])) $authentication_options['api_client_id'] = '';
    if (!isset($authentication_options['api_client_secret'])) $authentication_options['api_client_secret'] = '';

    if ($authentication_options['api_client_id'] == null || $authentication_options['api_client_secret'] == null)
    {

        function pressable_api_connection_save_response()
        {
            $screen = get_current_screen();

            //Display admin notice for this plugin page only
            if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
            $user = $GLOBALS['current_user'];
            $class = 'notice notice-error is-dismissible';
            $message = __('Please save all your API credentials first before connecting :(', 'pressable_cache_management', $user->display_name);

            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
        }
        add_action('admin_notices', 'pressable_api_connection_save_response');
    }

}

//Add pressable cdn option to the DB if it does not exit already
if ('not-exists' === get_option('cdnenabled', 'not-exists'))
{

    //Add the options table if they don't exisit
    add_option('cdnenabled', 'enable');

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
if (isset($authentication_options['pressable_site_id'], $authentication_options['api_client_id'], $authentication_options['api_client_secret'], $_POST['connect_api_nonce']) && !empty($authentication_options['pressable_site_id']))
{
    //Defining client id and client secret
    $client_id = $authentication_options['api_client_id'];
    $client_secret = $authentication_options['api_client_secret'];

    //query the api to auto generate bearer token
    $curl = curl_init();
    $auth_data = array(
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'client_credentials'
    );
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $auth_data);
    curl_setopt($curl, CURLOPT_URL, 'https://my.pressable.com/auth/token');
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $results = curl_exec($curl);

    //Terminate if no connection
    if (!$results)
    {
        return;
    }

    curl_close($curl);

    //Convert array to json format
    $results = json_decode($results, true);

    //Define expires_in and access_token as empty if not found in array
    if (!isset($results["expires_in"])) $results["expires_in"] = '';
    if (!isset($results["access_token"])) $results["access_token"] = '';

    $token_expires_in = $results["expires_in"];
    $access_token = $results["access_token"];

    //Set transient to expire access token in one hour
    set_transient('access_token', $access_token, $token_expires_in);

    //If connection status is empty or incorrect don't display api connection error message
    if ($authentication_options['api_client_id'] == null || $authentication_options['api_client_secret'] == null)
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
                    $message = sprintf('<p>Authentication Successful :)</p>', $user->display_name);

                    pressable_api_connect_notice__render_notice($message, 'notice notice-success is-dismissible');
                });

                update_option('pressable_api_admin_notice__status', 'activated');
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

$authentication_options = get_option('pressable_api_authentication_tab_options');

if ($authentication_options)
{

    $pressable_site_id = $authentication_options['pressable_site_id'];

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
                $message = sprintf('<p>Your API credentials permission is incorrect<a href="https://pressable.com/knowledgebase/pressable-cache-management-plugin/#api-authentication"> See how to setup and connect.</a>', $user->display_name);

                pcm_api_forbidden_admin_notice($message, 'notice notice-warning is-dismissible');
            });

        }
    }
    add_action('init', 'pcm_api_permission_notice');

}
