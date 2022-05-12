<?php

/**
 * Runs on Uninstall of Pressable Cache Management
 *
 * @package   Pressable Cache Management
 * @author    Pressable Support Team (Contributor: Tarhe Otughwor, Jess Nunez)
 * @license   GPL-2.0+
 * @link      http://pressable.com
 */

//include file containing function to remove batcache on wp-config.php
// include_once ('remove_mu_plugins_batcache_on_uninstall.php');
include_once( plugin_dir_path( __FILE__ ) . 'remove_mu_plugins_batcache_on_uninstall.php' );

// exit if uninstall constant is not defined
if (!defined('WP_UNINSTALL_PLUGIN'))
{

    exit;

}

// delete the plugin options from the database
delete_option('pressable_cache_management_options');
delete_option('cdn_settings_tab_options');
delete_option('pressable_api_authentication_tab_options');
delete_option('cdn-cache-purge-time-stamp');
delete_option('flush-obj-cache-time-stamp');
delete_option('pressable_api_admin_notice__status');
delete_option('pressable_cdn_connection_decactivated_notice');
delete_option('pressable_api_enable_cdn_connection_admin_notice');
delete_option('extend_batcache_activate_notice');
delete_option('extend_cdn_activate_notice');
delete_option('exclude_images_from_cdn_activate_notice');
delete_option('exclude_json_js_from_cdn_notice');
delete_option('flush-cache-page-edit-time-stamp');
delete_option('flush-cache-theme-plugin-time-stamp');
delete_option('flush-object-cache-for-single-page-notice');
delete_option('exempt_batcache_activate_notice');
delete_option('flush-object-cache-for-single-page-time-stamp');
delete_option('exclude_json_js_from_cdn_activate_notice');
delete_option('exclude_css_from_cdn_activate_notice');
delete_option('remove_pressable_branding_tab_options');
delete_option('pcm_site_id_added_activate_notice');
delete_option('pcm_site_id_con_res');
delete_option('pressable_site_id');
delete_option('pcm_client_id');
delete_option('pcm_client_secret');
delete_option('single-page-path-url');
delete_option('page-title');
delete_option('page-url');
