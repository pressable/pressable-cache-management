<?php //Pressable Cache Management - Adds a cache purge button to the admin bar


/******************************
 * //Pressable Cache Purge Adds a
 * Cache Purge button to the admin bar
 * by Jess Nunez
 *******************************/

add_action('admin_footer', 'cache_purge_action_js');

// Function to check when the flush cache button is clicked
function cache_purge_action_js()
{ ?>
  <script type="text/javascript" >
     jQuery("li#wp-admin-bar-cache-purge .ab-item").on( "click", function() {
        var data = {
                      'action': 'pressable_cache_purge',
                    };

        jQuery.post(ajaxurl, data, function(response) {
           alert( response );
        });

      });



  </script>
<style type="text/css">
    
/*#wp-admin-bar-cache-purge .ab-item { 
  background-color: #0AD8C7;
}*/

</style>


   <?php
}

add_action('wp_ajax_pressable_cache_purge', 'pressable_cache_purge_callback');

 // if (empty($remove_pressable_branding_tab_options )) {
        $remove_pressable_branding_tab_options  = false;
// }


//Check if branding Pressable branding is enabled or disabled
$remove_pressable_branding_tab_options = get_option('remove_pressable_branding_tab_options');
// $branding = $remove_pressable_branding_tab_options['branding_on_off_radio_button'];

// if (isset($remove_pressable_branding_tab_options )) {
   


// }




if ($remove_pressable_branding_tab_options && 'disable'  == $remove_pressable_branding_tab_options['branding_on_off_radio_button'] )
{


 /******************************
 * Hide branding Option 
 *******************************/

    add_action('admin_bar_menu', 'pcm_remove_branding', 100);

    function pcm_remove_branding($admin_bar)
    {

        //Display flush cache bar for only admin
        if (current_user_can('administrator'))
        {

            global $wp_admin_bar, $pagenow;;

            $wp_admin_bar->add_node(array(
                'id' => 'pcm-wp-admin-toolbar-parent-remove-branding',
                'title' => 'Cache Control'
            ));

            $wp_admin_bar->add_menu(array(
                'id' => 'cache-purge',
                'title' => 'Purge Cache',
                'parent' => 'pcm-wp-admin-toolbar-parent-remove-branding',
                'meta' => array(
                    "class" => "pcm-wp-admin-toolbar-child"
                )
            ));

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

/******************************
 * Show branding Option 
 *******************************/

    add_action('admin_bar_menu', 'cache_add_item', 100);

    function cache_add_item($admin_bar)
    {

        //Display flsuh cache bar for only admin
        if (current_user_can('administrator'))
        {

            global $wp_admin_bar, $pagenow;;

            $wp_admin_bar->add_node(array(
                'id' => 'pcm-wp-admin-toolbar-parent',
                'title' => 'Cache Management'
            ));

            $wp_admin_bar->add_menu(array(
                'id' => 'cache-purge',
                'title' => 'Purge Cache',
                'parent' => 'pcm-wp-admin-toolbar-parent',
                'meta' => array(
                    "class" => "pcm-wp-admin-toolbar-child"
                )
            ));

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
function pressable_cache_purge_callback()
{
    wp_cache_flush();

    //Save time stamp to database if cache is flushed.
    $object_cache_flush_time = date(' jS F Y  g:ia') . "\nUTC";

    update_option('flush-obj-cache-time-stamp', $object_cache_flush_time);
    $response = "Object Cache Purged";
    echo $response;
    wp_die();
}
