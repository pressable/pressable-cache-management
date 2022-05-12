<?php //Pressable Cache Management - Custom function to turn on/off CDN


/******************************
 * Activate CDN Option
 *******************************/

$cdn = false;

$cdn_tab_options = get_option('cdn_settings_tab_options');
$object_cache_tab_options = get_option('pressable_cache_management_options');
$api_auth_tab_options = get_option('pressable_api_authentication_tab_options');

//Merge options array together
// $options = array_merge($cdn_tab_options, $object_cache_tab_options, $api_auth_tab_options);

// //Check if authentication textbox is empty then do nothing
// if (empty($api_auth_tab_options['pressable_site_id'] || $api_auth_tab_options['api_client_id'] || $api_auth_tab_options['api_client_secret']))


// {

//     //TODO: add admin
//     return;

// }

//Check if options are set before processing
if (isset($cdn_tab_options['cdn_on_off_radio_button'], $api_auth_tab_options['pressable_site_id'], $api_auth_tab_options['api_client_id'], $api_auth_tab_options['api_client_secret']) && !empty($cdn_tab_options['cdn_on_off_radio_button'] || $api_auth_tab_options['pressable_site_id'] || $api_auth_tab_options['api_client_id'] || $api_auth_tab_options['api_client_secret']))
{

    $cdn = sanitize_text_field($cdn_tab_options['cdn_on_off_radio_button']);

}

//Set radion button state to defualt
if ('enable' === $cdn)
{

    //Defining client id client secret and site id
    $client_id = $api_auth_tab_options['api_client_id'];
    $client_secret = $api_auth_tab_options['api_client_secret'];
    $pressable_site_id = $api_auth_tab_options['pressable_site_id'];

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
    // if(!$results){die("Connection Failure");}
    curl_close($curl);

    //Display admin notice error messsage if connection unsuccessful
    if (!$results)
    {

        function pressable_api_admin_notice_connection_failure()
        {

            $screen = get_current_screen();

            //Display admin notice for this plugin page only
            if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
            //Check for current user to display  admin notice message to only
            if (current_user_can('manage_options'))
            {

                $user = $GLOBALS['current_user'];
                $class = 'notice notice-error';
                $message = __('Connection failure try again :( ', 'sample-text-domain');

                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
            }
        }
        add_action('admin_notices', 'pressable_api_admin_notice_connection_failure');

        

    }

    //Define index to prevent error message in the array
    $key = array(
        "error_description" => "error_description"
    );

    $results = json_decode($results, true);

    foreach ((array)$results as $key => $result)
    {
    }

    //Get error message from api response if there are any
    if ($key == "error_description")
    {

        /**Delete option from the database if the connection is not successfully
         used by admin notice to display and remove notice**/
        delete_option('pressable_api_admin_notice__status', 'activated');

        //Display admin notice if client ID or Client secret is incorrect
        function incorrect_id_admin_notice_success()
        {
            // Check for current user to display  admin notice message to only
            if (current_user_can('manage_options'))
            {

                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                $user = $GLOBALS['current_user'];
                $class = 'notice notice-error';
                $message = __('Your Client ID or Client Secret is incorrect.', 'sample-text-domain');

                // **Delete CDN enable option from the database if the connection is
                // not successful. This is used by admin notice to display and remove notice**/
                delete_option('pressable_api_enable_cdn_connection_admin_notice', 'activated');
                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));

            }
        }

        add_action('admin_notices', 'incorrect_id_admin_notice_success');

    }
    else
    {

      //
    }

    //Check if connection was successfuly
    if (!isset($results["access_token"]))
    {

        //Declearing index variable for access_token
        $results = array(
            "access_token" => "invalid_token"
        );

    }

    $token = $results["access_token"];
    $pressable_b_token = 'Authorization: Bearer ';
    $p_api_slug = "https://my.pressable.com/v1/sites/";
    $pressable_site_id = $api_auth_tab_options['pressable_site_id'];
    $p_cdn_api = "/cdn";

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $p_api_slug . $pressable_site_id . $p_cdn_api,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_HTTPHEADER => array(
            $pressable_b_token . $token,
            "cache-control: no-cache"
        ) ,
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if (!$response)
    {
        return;
    }

    //Convert request reponse to json object
    $pressable_api_query_response = json_decode($response, true);

    if (!$pressable_api_query_response)
    {
        return;
    }

    //Check array if CDN is activated
    if (in_array(false, $pressable_api_query_response))
    {

        //Display admin notice only once if connection to Pressable API is successful
        function pressable_cdn_activate_admin_notice($message = '', $classes = 'notice-success')
        {

            if (!empty($message))
            {
                printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
            }
        }

        function pressable_api_enable_cdn_connection_admin_notice()
        {

            $pressable_api_display_notice = get_option('pressable_api_enable_cdn_connection_admin_notice', 'activating');

            if ('activating' === $pressable_api_display_notice && current_user_can('manage_options'))
            {

                add_action('admin_notices', function ()
                {

                    $user = $GLOBALS['current_user'];
                    $message = sprintf('<p>CDN Enabled Successfully :)</p>', $user->display_name);

                   pressable_cdn_activate_admin_notice($message, 'notice notice-success is-dismissible');
                });

                update_option('pressable_api_enable_cdn_connection_admin_notice', 'activated');
                /**Delete option from the database if the connection is deactivated
                 used by admin notice to display and remove notice**/
                delete_option('pressable_cdn_connection_decactivated_notice', 'deactivated');
            }
        }
        add_action('init', 'pressable_api_enable_cdn_connection_admin_notice');

        $pressable_api_query_response = json_decode($response, true);

        //Check response if 404 error is found for site id


        
    }
    elseif (in_array("404", $pressable_api_query_response))
    {

        //If is not set define index variable to prevent error then terminate
        $pressable_api_query_response = array(
            "status" => "Not Found"
        );

        function pressable_site_id_notice__error()
        {

            $screen = get_current_screen();

            //Display admin notice for this plugn page only
            if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
            // Check for current user to display  admin notice message to only
            if (current_user_can('manage_options'))
            {

                $user = $GLOBALS['current_user'];
                $class = 'notice notice-error';
                $message = __('Pressable Site ID Incorrect.', 'sample-text-domain');
                //update_option( 'pressable_incorrect_site_id', 'activated' );
                /**Delete update cdn option from the database if site ID is incorrect
                 used by admin notice to display and remove admin notice**/
                delete_option('pressable_api_enable_cdn_connection_admin_notice', 'activated');

                printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
            }
        }

        add_action('admin_notices', 'pressable_site_id_notice__error');

    }

    /******************************
     * Deactivate CDN Option
     *******************************/

}
else
{

    $cdn = false;

    $cdn_tab_options = get_option('cdn_settings_tab_options');
    $object_cache_tab_options = get_option('pressable_cache_management_options');
    $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');

    // //Merge options array together
    // $options = array_merge($cdn_tab_options, $object_cache_tab_options, $api_auth_tab_options);

    // //Check if authentication textbox is empty then do nothing
    // if (empty($api_auth_tab_options['pressable_site_id'] || $api_auth_tab_options['api_client_id'] || $api_auth_tab_options['api_client_secret']))
    // {

    //     //TODO: add admin
    //     return;

    // }

    //Check if options are set before processing
    if (isset($cdn_tab_options['cdn_on_off_radio_button'], $api_auth_tab_options['pressable_site_id'], $api_auth_tab_options['api_client_id'], $api_auth_tab_options['api_client_secret']) && !empty($cdn_tab_options['cdn_on_off_radio_button'] || $api_auth_tab_options['pressable_site_id'] || $api_auth_tab_options['api_client_id'] || $api_auth_tab_options['api_client_secret']))
    {

        $cdn = sanitize_text_field($cdn_tab_options['cdn_on_off_radio_button']);

    }

    if ('disable' === $cdn)
    {

        //Defining client id and client secret
        $client_id = $api_auth_tab_options['api_client_id'];
        $client_secret = $api_auth_tab_options['api_client_secret'];

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

        curl_close($curl);
        //If the query fails display admin notice error messsage
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
                    $message = __('Connection failure try again :( ', 'sample-text-domain');

                    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                }
            }
            add_action('admin_notices', 'pressable_api_admin_notice__connection_failure');
        }

        //Define index for error messahe in the array
        $key = array(
            "error_description" => "error_description"
        );

        $results = json_decode($results, true);

        foreach ((array)$results as $key => $result)
        {

        }

        //Get error message from api response if there are any
        if ($key == "error_description")
        {

            /**Delete option from the database if the connection is not successfully
             used by admin notice to display and remove notice**/
            delete_option('pressable_api_admin_notice__status', 'activated');

            //Display admin notice if client ID or Client secret is incorrect
            function incorrect_id_admin_notice_error()
            {
                // Check for current user to display  admin notice message to only
                if (current_user_can('manage_options'))
                {

                    $screen = get_current_screen();

                    //Display admin notice for this plugin page only
                    if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                    $user = $GLOBALS['current_user'];

                    $class = 'notice notice-error';
                    $message = __('Your Client ID or Client Secret is incorrect.', 'sample-text-domain');

                    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                }
            }
            add_action('admin_notices', 'incorrect_id_admin_notice_error');

        }
        else
        {

           //
        }

        //Check if connection was successfuly
        if (!isset($results["access_token"]))
        {

            //Declearing index variable for access_token
            $results = array(
                "access_token" => "invalid_token"
            );

        }

        $token = $results["access_token"];
        $pressable_b_token = 'Authorization: Bearer ';
        $p_api_slug = "https://my.pressable.com/v1/sites/";
        $pressable_site_id = $api_auth_tab_options['pressable_site_id'];
        $p_cdn_api = "/cdn";

        /**
         * Make API request to Pressable
         * Pressable API Documentation my.pressable.com/v1
         *
         * Return HTTP request
         */

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $p_api_slug . $pressable_site_id . $p_cdn_api,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_HTTPHEADER => array(
                $pressable_b_token . $token,
                "cache-control: no-cache"
            ) ,
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        //Convert request reponse to json object
        $pressable_api_query_response = json_decode($response, true);

        if (!$pressable_api_query_response)
        {
            return;
        }

        //Check array if CDN is deactivated
        if (in_array(false, $pressable_api_query_response))
        {

            //Display admin notice only once if connection to Pressable API is successful
            function pressable_cdn_deactivate_admin_notice($message = '', $classes = 'notice-success')
            {

                if (!empty($message))
                {
                    printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
                }
            }

            function pressable_api_deactivate_cdn_connection_admin_notice()
            {

                $pressable_cdn_deactivate_display_notice = get_option('pressable_cdn_connection_decactivated_notice', 'deactivated');

                if ('deactivated' === $pressable_cdn_deactivate_display_notice && current_user_can('manage_options'))
                {

                    add_action('admin_notices', function ()
                    {

                        $screen = get_current_screen();

                        //Display admin notice for this plugin page only
                        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                        $user = $GLOBALS['current_user'];
                        $message = sprintf('<p>CDN Deactivated - It is always recommened to turn on your CDN for best caching experience.</p>', $user->display_name);

                         pressable_cdn_deactivate_admin_notice($message, 'notice notice-warning is-dismissible');
                    });

                    update_option('pressable_cdn_connection_decactivated_notice', 'deactivated');
                    /**Delete update cdn option from the database if the connection is deactivated
                     used by admin notice to display and remove admin notice**/
                    delete_option('pressable_api_enable_cdn_connection_admin_notice', 'activated');
                }
            }
            add_action('init', 'pressable_api_deactivate_cdn_connection_admin_notice');

            $pressable_api_query_response = json_decode($response, true);

            
        }
        elseif (in_array("Not Found", $pressable_api_query_response))
        {

            //If is not set define index variable to prevent error then terminate
            $pressable_api_query_response = array(
                "status" => "Not Found"
            );

            function pressable_site_id_notice__error()
            {

                $screen = get_current_screen();

                //Display admin notice for this plugn page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;
                // Check for current user to display  admin notice message to only
                if (current_user_can('manage_options'))
                {

                    $user = $GLOBALS['current_user'];
                    $class = 'notice notice-error';
                    $message = __('Pressable Site ID Incorrect.', 'sample-text-domain');
                    //update_option( 'pressable_incorrect_site_id', 'activated' );
                    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));
                }
            }
            add_action('admin_notices', 'pressable_site_id_notice__error');

        }
    }
}
