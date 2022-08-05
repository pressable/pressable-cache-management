<?php
/*
Plugin Name:  Pressable Cache Management
Description:  Presable cache management made easy
Plugin URI:   https://pressable.com/knowledgebase/pressable-cache-management-plugin/#overview
Author:       Pressable CS Team
Version:      3.3.4
Requires at   least: 5.0
Tested up to: 6.0
Requires PHP: 7.4
Text Domain:  pressable-cache-management
Domain Path:  /languages
License:      GPL v2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.txt
*/

// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

//Plugin can only work for site hosted on Pressable.
if (!defined('IS_PRESSABLE'))
    {
        die("This plugin can only work for sites hosted on Pressable platform.");
    }


// load text domain
function pressable_cache_management_load_textdomain()
{

    load_plugin_textdomain('pressable_cache_management', false, plugin_dir_path(__FILE__) . 'languages/');

}
add_action('plugins_loaded', 'pressable_cache_management_load_textdomain');

// include plugin dependencies: admin only
if (is_admin())
{

    require_once plugin_dir_path(__FILE__) . 'admin/admin-menu.php';
    require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';
    require_once plugin_dir_path(__FILE__) . 'admin/settings-register.php';
    require_once plugin_dir_path(__FILE__) . 'admin/settings-callbacks.php';
    require_once plugin_dir_path(__FILE__) . 'admin/settings-validate.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/extend_batcache.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/turn_on_off_cdn.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/purge_cdn_cache.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/flush_object_cache.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/object_cache_admin_bar.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/exempt_page_from_batcache.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/exclude_jpg_png_webp_from_cdn.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/cdn_cache_extender.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/exclude_json_js_from_cdn.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/exclude_css_from_cdn.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/exclude_particular_file_from_cdn.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/flush_batcache_for_particular_page.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/remove_pressable_branding.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/api_connection.php';

   

}

    //Added outside the above function to allow all themes access the hook automatically
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/flush_cache_on_theme_plugin_update.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/flush_cache_on_page_edit.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/flush_single_page_toolbar.php';

 /***********************************************
 * Adds settings link to plugin from plugin view
 ************************************************/

function pcm_settings_link($links) { 
  $settings_link = '<a href="admin.php?page=pressable_cache_management&tab=pressable_api_authentication_tab">Settings</a>'; 
  array_unshift($links, $settings_link); 
  return $links; 
}
$pcm_plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$pcm_plugin", 'pcm_settings_link' );

