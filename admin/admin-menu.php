<?php // Pressable Cache Management - Admin Menu


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

// add sub-level administrative menu
function pressable_cache_management_add_sublevel_menu()
{

    /*
    
    add_submenu_page(
     'options-general.php',
    string   $parent_slug,
    string   $page_title,
    string   $menu_title,
    string   $capability,
    string   $menu_slug,
    callable $function = ''
    );
    
    */

    add_submenu_page('admin.php', esc_html__('', 'pressable_cache_management') , esc_html__('Pressable Cache Management', 'pressable_cache_management') , 'manage_options', 'pressable_cache_management', 'pressable_cache_management_display_settings_page');

}
add_action('admin_menu', 'pressable_cache_management_add_sublevel_menu');

// add top-level administrative menu
function pressable_cache_management_add_toplevel_menu()
{

    /*
    
    add_menu_page(
    string   $page_title,
    string   $menu_title,
    string   $capability,
    string   $menu_slug,
    callable $function = '',
    string   $icon_url = '',
    int      $position = null
    )
    
    */


    //Check if branding Pressable branding is enabled or disabled

    $remove_pressable_branding_tab_options  = false;
    
    $remove_pressable_branding_tab_options = get_option('remove_pressable_branding_tab_options');
   
    
if ($remove_pressable_branding_tab_options && 'disable'  == $remove_pressable_branding_tab_options['branding_on_off_radio_button'] )
{

        add_menu_page(esc_html__('Cache Management Settings', 'pressable_cache_management') , esc_html__('Cache Control', 'pressable_cache_management') , 'manage_options', 'pressable_cache_management', 'pressable_cache_management_display_settings_page',
        plugin_dir_url(__FILE__) . '/assets/img/cache_control.png', 2); 

    } else {

    add_menu_page(esc_html__('Pressable Cache Management Settings', 'pressable_cache_management') , esc_html__('Pressable CM', 'pressable_cache_management') , 'manage_options', 'pressable_cache_management', 'pressable_cache_management_display_settings_page',
    plugin_dir_url(__FILE__) . '/assets/img/pressable-icon-primary.svg', 2);
    
    }


}
add_action('admin_menu', 'pressable_cache_management_add_toplevel_menu');

//Display admin notices for top level menu
function plugin_admin_notice()
{
    //get the current screen
    $screen = get_current_screen();

    //return if not plugin settings page
    if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

    //Checks if settings updated
    if (isset($_GET['settings-updated']))
    {
        //if settings updated successfully
        if ('true' === $_GET['settings-updated']): ?>
 
            <div class="notice notice-success is-dismissible">
                <p><?php _e('Cache settings updated.', 'textdomain') ?></p>
            </div>

            <?php
        else: ?>

            	<div class="notice notice-warning is-dismissible">
                <p><?php _e('Sorry, I was unable to save your cache settings try again :(', 'textdomain') ?></p>
            </div>

        <?php
        endif;
    }
}
add_action('admin_notices', 'plugin_admin_notice');

