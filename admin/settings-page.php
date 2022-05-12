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


  $remove_pressable_branding_tab_options  = false;
    
  $remove_pressable_branding_tab_options = get_option('remove_pressable_branding_tab_options');

  
    
  if ($remove_pressable_branding_tab_options) { 

  }
  ?>
  <div class="wrap branding-<?php $remove_pressable_branding_tab_options; ?>">

    <!-- Print the page title -->
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Here are our tabs -->
    <nav class="nav-tab-wrapper">
      <a href="admin.php?page=pressable_cache_management" class="nav-tab nav-tab-object-cache <?php if ($tab === null): ?>nav-tab-active<?php
    endif; ?>">Object Cache</a>
      <a href="admin.php?page=pressable_cache_management&tab=cdn_settings_tab" class="nav-tab nav-tab-cdn <?php if ($tab === 'cdn_settings_tab'): ?>nav-tab-active<?php
    endif; ?>">CDN</a>
      <a href="admin.php?page=pressable_cache_management&tab=pressable_api_authentication_tab" class="nav-tab nav-tab-api <?php if ($tab === 'pressable_api_authentication_tab'): ?>nav-tab-active<?php
    endif; ?>">API Authentication</a>
   
    <!-- Hidden from the plugin view can be accessed from the love icon below the plugin footer -->
      <a href="admin.php?page=pressable_cache_management&tab=remove_pressable_branding_tab" class="nav-tab nav-tab-hidden <?php if ($tab === null): ?>nav-tab-active<?php
    endif; ?>">Hidden Tab Remove Branding</a>
    </nav>

  <!-- Switch between tabs -->

  <div class="wrap">
    <h1 ><?php echo esc_html(get_admin_page_title()); ?></h1>
    <form action="options.php" method="post"><?php

    if ($default_tab == $tab)
    {

        //Display setings and page for this tab
        settings_fields('pressable_cache_management_options');
        do_settings_sections('pressable_cache_management');

    }
    elseif ($tab == 'cdn_settings_tab')
    {

        //Display setings and page for this tab
        settings_fields('cdn_settings_tab_options');
        do_settings_sections('cdn_settings_tab');

    }
    elseif ($tab == 'pressable_api_authentication_tab')
    {

        //Display setings and page for this tab
        settings_fields('pressable_api_authentication_tab_options');
        do_settings_sections('pressable_api_authentication_tab');

    }

     elseif ($tab == 'remove_pressable_branding_tab')
    {

        //Display setings and page for this tab
        settings_fields('remove_pressable_branding_tab_options');
        do_settings_sections('remove_pressable_branding_tab');

    }

    //enque css script
    wp_enqueue_style('pressable_cache_management', plugin_dir_url(dirname(__FILE__)) . 'public/css/style.css', array() , null, 'screen');
    // submit button
    // submit_button();
    submit_button('Save Settings', 'custom-class');

?>
      
    </form>


<style type="text/css">
  
/**Footer heart styling**/
  .heart {
  fill: red;
  position: relative;
  top: 5px;
  width: 16px;
  animation: pulse 6s ease;
 /* animation: pulse 1s ease infinite;*/
}

@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.3); }
  100% { transform: scale(1); }
}

/** Styles for hiding Pressable branding **/
.nav-tab-hidden,
#footer-built-with-love.branding-disable,
.branding-disable h2,
.branding-disable .pressablecmlogo { display:none!important; }


</style>
  </div><?php
}



    
//Display footer message with Pressable branding
function pcm_footer_msg()
{

    
/**
 **********************************************************
 * Add remove pressable branding_tab_options table to DB if 
 * not exist  to always enable Pressable pranding by default
 **********************************************************
 */
    
  
 if ('not-exists' === get_option('remove_pressable_branding_tab_options', 'not-exists'))
{

    //Add the options table if it does not exist 
    add_option('remove_pressable_branding_tab_options', '');
     
        //Set opions table to enable default
        $pcm_enable_pressable_branding = array(
            'branding_on_off_radio_button' => 'enable',       
        );

        update_option('remove_pressable_branding_tab_options',  $pcm_enable_pressable_branding);
        
}
    

    add_action('admin_notices', function ()
    {

        $screen = get_current_screen();

        //Display footer message for only Pressable Cache Management plugin page
        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

        $user = $GLOBALS['current_user'];

        //Show Pressable footer branding
        function remove_footer_admin()
        {
            
         $remove_pressable_branding_tab_options  = false;
    
            $remove_pressable_branding_tab_options = get_option('remove_pressable_branding_tab_options');
                
              if ($remove_pressable_branding_tab_options && 'enable'  == $remove_pressable_branding_tab_options['branding_on_off_radio_button'] )
{
                
            
            echo '<span id="footer-built-with-love" class="branding-';
            echo $remove_pressable_branding_tab_options['branding_on_off_radio_button'];
            echo '">Built with 
            <a href="admin.php?page=pressable_cache_management&tab=remove_pressable_branding_tab">
            <svg class="heart" viewBox="0 0 32 29.6">
  <path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2
  c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/>
</svg></a> by The Pressable Customer Support Team.</span>';
            
            }
        }  
        
        add_filter('admin_footer_text', 'remove_footer_admin');
 });

}
add_action('init', 'pcm_footer_msg');




   
//Display footer message without Pressable branding
function pcm_footer_msg_remove_branding()
{

    add_action('admin_notices', function ()
    {

        $screen = get_current_screen();

        //Display footer message for only Pressable Cache Management plugin page
        if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

        $user = $GLOBALS['current_user'];

        // Admin footer modification | Hide Pressable footer branding
        function remove_pressable_footer_branding()
        {
            
         $remove_pressable_branding_tab_options  = false;
    
            $remove_pressable_branding_tab_options = get_option('remove_pressable_branding_tab_options');
                
            if ($remove_pressable_branding_tab_options && 'disable'  == $remove_pressable_branding_tab_options['branding_on_off_radio_button'] )
{
                
            
            echo '<span id="footer-built-with-love" class="branding-';
//             echo $remove_pressable_branding_tab_options['branding_on_off_radio_button'];
            echo '">Built with 
            <a href="admin.php?page=pressable_cache_management&tab=remove_pressable_branding_tab">
            <svg class="heart" viewBox="0 0 32 29.6">
  <path d="M23.6,0c-3.4,0-6.3,2.7-7.6,5.6C14.7,2.7,11.8,0,8.4,0C3.8,0,0,3.8,0,8.4c0,9.4,9.5,11.9,16,21.2
  c6.1-9.3,16-12.1,16-21.2C32,3.8,28.2,0,23.6,0z"/>
</svg></a></span>';
            
            }
        }  
        
        add_filter('admin_footer_text', 'remove_pressable_footer_branding');
 });

}
add_action('init', 'pcm_footer_msg_remove_branding');
