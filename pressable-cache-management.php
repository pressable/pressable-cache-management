<?php
/*
Plugin Name:  Pressable Cache Management
Description:  Pressable cache management made easy
Plugin URI:   https://pressable.com/knowledgebase/pressable-cache-management-plugin/#overview
Author:       Pressable CS Team
Version:      4.2.8
Requires at   least: 5.0
Tested up to: 6.0
Requires PHP: 7.4
Text Domain:  pressable_cache_management
Domain Path:  /languages
License:      GPL v2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.txt
*/

// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

//Disable plugin automatically when used on another platform
if (!defined('IS_PRESSABLE'))
{
    add_action('admin_notices', 'pcm_auto_deactivation_notice');
    add_action('admin_init', 'deactivate_plugin_if_not_pressable');
}

function deactivate_plugin_if_not_pressable()
{
    //deactivate plugin code
    deactivate_plugins(plugin_basename(__FILE__));
}

//Display notice banner
function pcm_auto_deactivation_notice()
{
    
    $msg = '<div style="margin:50px 20px 20px 0;background-color: white;border:1px solid #c3c4c7;border-top-color:#d63638;border-top-width:5px;padding:20px;">';
  
    $msg .= '<h3 style="margin-top:0;color:#d63638;font-weight:900;">' . __('Attention! ', 'pressable_cache_management') . __('', 'pressable_cache_management') . '</h3>';
    
    $msg .= '</div>';
    $msg .= '</div>';
    echo $msg;
}

// load text domain
function pressable_cache_management_load_textdomain()
{

    load_plugin_textdomain('pressable_cache_management', false, plugin_dir_path(__FILE__) . 'languages/');

}
add_action('plugins_loaded', 'pressable_cache_management_load_textdomain');

// Include plugin dependencies: admin only
if (is_admin())
{

    require_once plugin_dir_path(__FILE__) . 'admin/admin-menu.php';
    require_once plugin_dir_path(__FILE__) . 'admin/settings-page.php';
    require_once plugin_dir_path(__FILE__) . 'admin/settings-register.php';
    require_once plugin_dir_path(__FILE__) . 'admin/settings-callbacks.php';
    require_once plugin_dir_path(__FILE__) . 'admin/settings-validate.php';
    require_once plugin_dir_path(__FILE__) . 'remove_old_mu_plugins.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/extend_batcache.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/turn_on_off_cdn.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/turn_on_off_edge_cache.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/purge_cdn_cache.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/purge_edge_cache.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/flush_object_cache.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/object_cache_admin_bar.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/exclude_jpg_png_webp_from_cdn.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/cdn_cache_extender.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/exclude_json_js_from_cdn.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/exclude_css_from_cdn.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/exclude_particular_file_from_cdn.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions//flush_batcache_for_woo_individual_page.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/exclude_pages_from_batcache.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/cache_wpp_cookie_page.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/flush_batcache_for_particular_page.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/flush_cache_on_comment_delete.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/exclude_query_string_gclid_from_cache.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/exclude_font_files_from_cdn.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/remove_pressable_branding.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/api_connection.php';

}

    //Added outside the above function to allow all themes access the hook automatically
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/flush_cache_on_theme_plugin_update.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/flush_cache_on_page_edit.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/flush_cache_on_page_post_delete.php';
    require_once plugin_dir_path(__FILE__) . 'admin/custom-functions/flush_single_page_toolbar.php';

/***********************************************
 * Adds settings link to plugin from plugin view
 ************************************************/

function pcm_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=pressable_cache_management&tab=pressable_api_authentication_tab">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
$pcm_plugin = plugin_basename(__FILE__);
add_filter("plugin_action_links_$pcm_plugin", 'pcm_settings_link');
