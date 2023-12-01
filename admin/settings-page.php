<?php // Pressable Cache Management - Settings Page


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

// display the plugin settings page
function pressable_cache_management_display_settings_page()
{

    // check if user is allowed access
    if (!current_user_can('manage_options')) return;

    //Retrieve active tab from $_GET param
    $default_tab = null;
    $tab = isset($_GET['tab']) ? $_GET['tab'] : $default_tab;

?>


      <!-- Our admin page content should all be inside .wrap -->

      <?php
    $remove_pressable_branding_tab_options = get_option('remove_pressable_branding_tab_options');

?>

         <div class="wrap branding-<?php echo (is_array($remove_pressable_branding_tab_options)) ? esc_html(json_encode($remove_pressable_branding_tab_options)) : esc_html($remove_pressable_branding_tab_options); ?>">



        <!-- Print the page title -->
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <!-- Here are our tabs -->
        <nav class="nav-tab-wrapper">
          <a href="admin.php?page=pressable_cache_management" class="nav-tab nav-tab-object-cache <?php if ($tab === null): ?>nav-tab-active<?php
    endif; ?>">Object Cache</a>
          <a href="admin.php?page=pressable_cache_management&tab=cdn_settings_tab" class="nav-tab nav-tab-cdn <?php if ($tab === 'cdn_settings_tab'): ?>nav-tab-active<?php
    endif; ?>">CDN</a>
            <a href="admin.php?page=pressable_cache_management&tab=edge_cache_settings_tab" class="nav-tab nav-tab-edge-cache <?php if ($tab === 'edge_cache_settings_tab'): ?>nav-tab-active<?php
    endif; ?>">Edge Cache(Beta)</a>
          <a href="admin.php?page=pressable_cache_management&tab=pressable_api_authentication_tab" class="nav-tab nav-tab-api <?php if ($tab === 'pressable_api_authentication_tab'): ?>nav-tab-active<?php
    endif; ?>">API Authentication</a>

        <!-- Hidden from the plugin view can be accessed from the love icon below the plugin footer -->
          <a href="admin.php?page=pressable_cache_management&tab=remove_pressable_branding_tab" class="nav-tab nav-tab-hidden <?php if ($tab === 'remove_pressable_branding_tab'): ?>nav-tab-active<?php
    endif; ?>">Hidden Tab Remove Branding</a>
        </nav>

      <!-- Switch between tabs -->

      <div class="wrap">
        <h1 ><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post"><?php
    if ($default_tab == $tab)
    {

        //Display setings and page for object cache tab
        settings_fields('pressable_cache_management_options');
        do_settings_sections('pressable_cache_management');

        submit_button('Save Settings', 'custom-class');
    }
    elseif ($tab == 'cdn_settings_tab')
    {

        //Display setings and page for CDN tab
        settings_fields('cdn_settings_tab_options');
        do_settings_sections('cdn_settings_tab');

        $pcm_con_auth = get_option('pressable_api_admin_notice__status');
        $site_id_con_res = get_option('pcm_site_id_con_res');
        $pcm_cdn_status = get_option('cdnenabled');

        //Only display the submit button if API is connected
        if ($site_id_con_res === 'OK' && $pcm_con_auth === 'activated' && $pcm_cdn_status === 'enable')
        {

            submit_button('Save Settings', 'custom-class');

        }

    }
    elseif ($tab == 'edge_cache_settings_tab')
    {

        //Display setings and page for CDN tab
        settings_fields('edge_cache_settings_tab_options');
        do_settings_sections('edge_cache_settings_tab');

        $pcm_con_auth = get_option('pressable_api_admin_notice__status');
        $site_id_con_res = get_option('pcm_site_id_con_res');

    }

    elseif ($tab == 'pressable_api_authentication_tab')
    {

        //Display setings and page for authentication tab
        settings_fields('pressable_api_authentication_tab_options');
        do_settings_sections('pressable_api_authentication_tab');

        $pcm_con_auth = get_option('pressable_api_admin_notice__status');
        $site_id_con_res = get_option('pcm_site_id_con_res');

        //Hide the submit button if API is connected
        if ($site_id_con_res === 'OK' && $pcm_con_auth === 'activated')
        {
            //
            
        }
        else
        {

            submit_button('Save Credentials ', 'custom-class');
        }
    }

    elseif ($tab == 'remove_pressable_branding_tab')
    {

        //Display setings and page for this tab
        settings_fields('remove_pressable_branding_tab_options');
        do_settings_sections('remove_pressable_branding_tab');

        submit_button('Save Settings', 'custom-class');
    }

    //enque css script
    wp_enqueue_style('pressable_cache_management', plugin_dir_url(dirname(__FILE__)) . 'public/css/style.css', array() , null, 'screen');

?>


    <style type="text/css">

    /** Hide Pressable branding tab **/
    .nav-tab-hidden,
    #footer-built-with-love.branding-disable,
    .branding-disable h2,
    .branding-disable .pressablecmlogo { display:none!important; }

    </style>
      </div><?php
}


// Display footer message with Pressable branding
function pcm_footer_msg()
{
    /**
     **********************************************************
     * Add remove pressable branding_tab_options table to DB if
     * not exist to always enable Pressable branding by default
     **********************************************************
     */
    if ('not-exists' === get_option('remove_pressable_branding_tab_options', 'not-exists'))
    {

        // Add the options table if it does not exist
        add_option('remove_pressable_branding_tab_options', '');

        // Set options table to enable default
        $pcm_enable_pressable_branding = array(
            'branding_on_off_radio_button' => 'enable',
        );

        update_option('remove_pressable_branding_tab_options', $pcm_enable_pressable_branding);
    }

    // Hook to replace the default WordPress footer with your plugin's footer
    add_filter('admin_footer_text', 'pcm_replace_default_footer');
}

// Function to replace the default WordPress footer with your plugin's footer
function pcm_replace_default_footer($footer_text)
{
    $remove_pressable_branding_tab_options = get_option('remove_pressable_branding_tab_options');

    // Check if on the pressable_cache_management page
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'pressable_cache_management') {
        // Check if Pressable branding is enabled
        if ($remove_pressable_branding_tab_options && 'enable' == $remove_pressable_branding_tab_options['branding_on_off_radio_button']) {
            return 'Built with 
            <a href="admin.php?page=pressable_cache_management&tab=remove_pressable_branding_tab" style="text-decoration: none; color: transparent;"><span class="heart" style="color:red; font-size:24px;">&#x2665;</span></a> by The Pressable CS Team.';
        } else {
            // // Hide the specific text in the footer-thankyou span
            
            echo '<span id="footer-thankyou">Built with 
    <a href="admin.php?page=pressable_cache_management&tab=remove_pressable_branding_tab" style="text-decoration: none; color: transparent;"><span class="heart" style="color:red; font-size:24px;">&#x2665;</span></a></span>';
    return '<style>#footer-thankyou:contains("Developed by Webarts"){ display: none; }</style>';
        }
    } else {
        // Hide all other footer messages
        return '';
    }
}

// Hook functions to the admin_init action
add_action('admin_init', 'pcm_footer_msg');
