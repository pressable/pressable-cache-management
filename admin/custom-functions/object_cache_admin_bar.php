<?php //Pressable Cache Management - Adds a cache purge button to the admin bar


/**************************************
 * //Pressable Cache Purge Adds a
 * Cache Purge button to the admin bar
 * by Jess Nunez
 *************************************/

add_action('admin_footer', 'cache_purge_action_js');

// Function to check when the flush cache button is clicked
function cache_purge_action_js()
{ ?>
  <script type="text/javascript" >
     jQuery("li#wp-admin-bar-cache-purge .ab-item").on( "click", function() {
        var data = {
                      'action': 'flush_pressable_cache',
                    };

        jQuery.post(ajaxurl, data, function(response) {
           alert( response );
        });

      });
  </script>


   <?php
}

add_action('admin_footer', 'cdn_cache_purge_action_js');

// Function to check when the flush cache button is clicked
function cdn_cache_purge_action_js()
{ ?>
  <script type="text/javascript" >
     jQuery("li#wp-admin-bar-cdn-purge .ab-item").on( "click", function() {
        var data = {
                      'action': 'pressable_cdn_cache_purge',
						
                    };

        jQuery.post(ajaxurl, data, function(response) {
           alert( response );
        });

      });
  </script>
<style type="text/css">


</style>

   <?php
}

add_action('wp_ajax_pressable_cdn_cache_purge', 'pressable_cdn_cache_purge_callback');

add_action('wp_ajax_flush_pressable_cache', 'flush_pressable_cache_callback');

$remove_pressable_branding_tab_options = false;

//Check if branding Pressable branding is enabled or disabled
$remove_pressable_branding_tab_options = get_option('remove_pressable_branding_tab_options');

if ($remove_pressable_branding_tab_options && 'disable' == $remove_pressable_branding_tab_options['branding_on_off_radio_button'])
{

    /******************************
     * Hide branding Option
     *******************************/

    add_action('admin_bar_menu', 'pcm_remove_branding', 100);

    function pcm_remove_branding($admin_bar)
    {

        //Display flush cache Admin Top Bar for only Admin and Shop Managers
        if (current_user_can('administrator') || current_user_can('manage_woocommerce'))
        {

            global $wp_admin_bar, $pagenow;;

            $wp_admin_bar->add_node(array(
                'id' => 'pcm-wp-admin-toolbar-parent-remove-branding',
                'title' => 'Cache Control'
            ));

            $wp_admin_bar->add_menu(array(
                'id' => 'cache-purge',
                'title' => 'Flush Object Cache',
                'parent' => 'pcm-wp-admin-toolbar-parent-remove-branding',
                'meta' => array(
                    "class" => "pcm-wp-admin-toolbar-child"
                )
            ));
			
	   //Check if the Pressable API is connected or hide the CDN purge cache admin bar button
            $pcm_con_auth = get_option('pressable_api_admin_notice__status');
            $site_id_con_res = get_option('pcm_site_id_con_res');

            //Check if CDN is enabled before displaying purge CDN button on admin bar
            $cdn_tab_options = get_option('cdn_settings_tab_options');
            $hide_cdn_options = get_option('cdnenabled');
            if ($site_id_con_res === 'OK' && $pcm_con_auth === 'activated')
            {
                if ($cdn_tab_options && $hide_cdn_options === 'enable')
                {
                    $wp_admin_bar->add_menu(array(
                        'id' => 'cdn-purge',
                        'title' => 'Purge CDN Cache',
                        'parent' => 'pcm-wp-admin-toolbar-parent-remove-branding',
                        'href' => '#',
                        'meta' => array(
                            "class" => "pcm-wp-admin-toolbar-child"
                        ) ,
                        'onclick' => 'purge_cdn_cache()'
                    ));

                }
                else
                {

                    //Hide CDN admin bar button if not connetced to the Pressable API
                    
                }
            }

            $wp_admin_bar->add_menu(array(
                'id' => 'settings',
                'title' => 'Cache Settings',
                'parent' => 'pcm-wp-admin-toolbar-parent-remove-branding',
                'href' => 'admin.php?page=pressable_cache_management',
                'meta' => array(
                    "class" => "pcm-wp-admin-toolbar-child"
                )
            ));

        }
    }

}
else
{

    /**
     * Show/hide admin bar for branding Option
     *
     */

    add_action('admin_bar_menu', 'cache_add_item', 100);

    function cache_add_item($admin_bar)
    {
        //Hide the toolbar on network site home
        if (is_network_admin())
        {

        }
        //Display flush cache Admin Top Bar for only Admin and Shop Managers
       elseif (current_user_can('administrator') || current_user_can('manage_woocommerce')) {

            global $wp_admin_bar, $pagenow;;

            $wp_admin_bar->add_node(array(
                'id' => 'pcm-wp-admin-toolbar-parent',
                'title' => 'Cache Management'
            ));

            $wp_admin_bar->add_menu(array(
                'id' => 'cache-purge',
                'title' => 'Flush Object Cache',
                'parent' => 'pcm-wp-admin-toolbar-parent',
                'meta' => array(
                    "class" => "pcm-wp-admin-toolbar-child"
                )
            ));

            //Check if the Pressable API is connected or hide the CDN purge cache admin bar button
            $pcm_con_auth = get_option('pressable_api_admin_notice__status');
            $site_id_con_res = get_option('pcm_site_id_con_res');

            //Check if CDN is enabled before displaying purge CDN button on admin bar
            $cdn_tab_options = get_option('cdn_settings_tab_options');
            $hide_cdn_options = get_option('cdnenabled');
            if ($site_id_con_res === 'OK' && $pcm_con_auth === 'activated')
            {
                if ($cdn_tab_options && $hide_cdn_options === 'enable')
                {
                    $wp_admin_bar->add_menu(array(
                        'id' => 'cdn-purge',
                        'title' => 'Purge CDN Cache',
                        'parent' => 'pcm-wp-admin-toolbar-parent',
                        'href' => '#',
                        'meta' => array(
                            "class" => "pcm-wp-admin-toolbar-child"
                        ) ,
                        'onclick' => 'purge_cdn_cache()'
                    ));

                }
                else
                {

                    //Hide CDN admin bar button if not connetced to the Pressable API
                    
                }
            }

            $wp_admin_bar->add_menu(array(
                'id' => 'settings',
                'title' => 'Cache Settings',
                'parent' => 'pcm-wp-admin-toolbar-parent',
                'href' => 'admin.php?page=pressable_cache_management',
                'meta' => array(
                    "class" => "pcm-wp-admin-toolbar-child"
                )
            ));

        }
    }

}

// Save date/time to database when cache is flushed
function flush_pressable_cache_callback()
{
    wp_cache_flush();

    //Save time stamp to database if cache is flushed.
    $object_cache_flush_time = date(' jS F Y  g:ia') . "\nUTC";

    update_option('flush-obj-cache-time-stamp', $object_cache_flush_time);
    $response = "Object Cache Flushed Successfully!";
    echo $response;
    wp_die();
}

/********************************************************
 * This snippet of code checks if an access token is
 * available and if it is expired. If the token is still
 * valid, it will make a request to the Pressable API
 * to delete the cache and update the timestamp of when
 * the cache was purged.
 ********************************************************/

function pressable_cdn_cache_purge_callback()
{
    purge_cdn_cache();
    wp_die();
}

function purge_cdn_cache()
{

    //Flush CDN cache
    // Check if the access token is not expired, otherwise use cached access token
    $check_access_token_expiry = get_option('access_token_expiry');

    if (time() < $check_access_token_expiry && false !== get_transient('access_token'))
    {
        $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');

        $access_token = get_transient('access_token');
        $pressable_site_id = $api_auth_tab_options['pressable_site_id'];
        $pressable_api_request_headers = array(
            'Authorization' => 'Bearer ' . ($access_token)
        );
        $pressable_api_request_url = 'https://my.pressable.com/v1/sites/' . $pressable_site_id . '/cache';

        $pressable_api_response_post_request = wp_remote_request($pressable_api_request_url, array(
            'method' => 'DELETE',
            'headers' => $pressable_api_request_headers
        ));

        $response = json_decode(wp_remote_retrieve_body($pressable_api_response_post_request) , true);

        if ($response["message"] == "Success")
        {

            $cdn_purged_time = date(' jS F Y  g:ia') . "\nUTC";
            update_option('cdn-cache-purge-time-stamp', $cdn_purged_time);
            $message = __('CDN Cache Purged Successfully!', 'pressable_cache_management');
            echo $message;
        }
        elseif ($response["errors"][0] == "CDN can only be purged once per minute")
        {
            $message = __('CDN can only be purged once per minute :(', 'pressable_cache_management');
            echo $message;
        }

        /**********************************************
         * Check if access token is valid and if access
         * token transient is created in the database
         * else it will generate a new access token.
         **********************************************/
    }
    else
    {
        $api_auth_tab_options = get_option('pressable_api_authentication_tab_options');

        if (isset($api_auth_tab_options['pressable_site_id'], $api_auth_tab_options['api_client_id'], $api_auth_tab_options['api_client_secret']) && (!empty($api_auth_tab_options['pressable_site_id']) || !empty($api_auth_tab_options['api_client_id']) || !empty($api_auth_tab_options['api_client_secret'])))
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

            if (is_wp_error($results))
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

                $cdn_purged_time = date(' jS F Y  g:ia') . "\nUTC";
                update_option('cdn-cache-purge-time-stamp', $cdn_purged_time);
                $message = __('CDN Cache Purged Successfully!', 'pressable_cache_management');
                echo $message;
            }
            elseif ($response["errors"][0] == "CDN can only be purged once per minute")
            {
                $message = __('CDN can only be purged once per minute :(', 'pressable_cache_management');
                echo $message;
            }

        }
    }

}
