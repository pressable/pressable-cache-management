<?php // Pressable Cache Management - Purge CDN Cache


if (isset($_POST['purge_cache_nonce'])) {


     function pressable_cdn_purge__button() {


        
        $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');

         

        if (isset($api_auth_tab_options['pressable_site_id'], $api_auth_tab_options['api_client_id'], $api_auth_tab_options['api_client_secret']) && !empty($api_auth_tab_options['pressable_site_id'] || $api_auth_tab_options['api_client_id'] || $api_auth_tab_options['api_client_secret'])) {

            if (wp_verify_nonce($_POST['purge_cache_nonce'], 'purge_cache_nonce')) {

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

                //Terminate if no connection
                if (!$results ) {
                   return;
                }

                curl_close($curl);

                //Convert array to json format
                $results = json_decode($results, true);



                //If connection is not successfully show admin notice error
                if (in_array("invalid_client", $results)) {
                   
                   //Display admin notice if site is not connected to pressable
                   function unable_to_connect_admin_notice__error()
                    {
                        $screen = get_current_screen();

                        //Display admin notice for this plugin page only
                        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                        $user = $GLOBALS['current_user'];
                        $class = 'notice notice-error';
                        $message = __('You are not connected to MyPressable.', 'pressable_cache_management');


                        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class) , esc_html($message));

                    }

                    add_action('admin_notices', 'unable_to_connect_admin_notice__error');

                //If connection is successfully purge CDN cache
                } else {

                //extract access token from the array
                extract($results);

                $results = $access_token;

                $token = $results;
                $pressable_b_token = 'Authorization: Bearer ';
                $p_api_slug = "https://my.pressable.com/v1/sites/";
                $pressable_site_id = $api_auth_tab_options['pressable_site_id'];
                $p_cdn_api = "/cache";

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

                //Display admin notice when the cache is purges successfully
                function pressable_cdn_purge_cache_notice_success()
                { ?>

                <div class="notice notice-success is-dismissible">
                    <p><?php _e('CDN Purged Successfully :)', 'pressable_cache_management'); ?></p>
                </div>
                <?php
                    
                }
                add_action('admin_notices', 'pressable_cdn_purge_cache_notice_success');

                //Save time stamp to database if cache is flushed.
                $cdn_purged_time = date(' jS F Y  g:ia') . "\nUTC";

                update_option( 'cdn-cache-purge-time-stamp', $cdn_purged_time );


                                }

                        }

                    }
             

                 }
                 add_action('init', 'pressable_cdn_purge__button', 999);

        }