<?php //Pressable Cache Management Plugin - API Connection 

$options = get_option('pressable_api_authentication_tab_options');

if (isset($options['pressable_site_id'], $options['api_client_id'], $options['api_client_secret']) && !empty($options['pressable_site_id']))
{



    //Defining client id and client secret
    $client_id = $options['api_client_id'];
    $client_secret = $options['api_client_secret'];

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

    //If connection is not successfully show admin notice error
    if (in_array("invalid_client", $results))
    {

        //Display admin notice if site is not connected to pressable
        function api_auth_to_connect_admin_notice__error()
        {
            $screen = get_current_screen();

            //Display admin notice for this plugin page only
            if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

            $user = $GLOBALS['current_user'];
            $class = 'notice notice-error';
            $message = __('Your Client ID or Client Secret is incorrect.', 'pressable_cache_management');

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
    //Declare array to prevent error on PHP 8.0
    $results = array(
        "access_token" => ""
    );

}

//Exract access token from the array
$results = extract($results);

//Declare variable if it's not set
if (!isset($access_token))
{
    $access_token = '';

}

$results = $access_token;

$access_token = $results;

$options = get_option('pressable_api_authentication_tab_options');

if ($options) {

   $pressable_site_id = $options['pressable_site_id']; 



//Connecting to Pressable API
$pressable_api_request_headers = array(
    //Add your Bearer Token
    'Authorization' => 'Bearer ' . ($access_token)
);
//Pressable API request URL example: https://my.pressable.com/v1/sites
$pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id;

//iitiating connection to the API using WordPress request function
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

            // return;
            
        }
    }
    add_action('admin_notices', 'pressable_site_id_not_found_notice__error');
    //Display success message if site is found
    
}
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

 // update_option('pcm_site_id_con_res', 'Not Found');
    //      echo "No response found";
    // return;
    
}
