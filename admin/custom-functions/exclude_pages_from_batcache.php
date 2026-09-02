<?php // Pressable Cache Management  - Exclude pages from Batcache

// disable direct file access
if (!defined('ABSPATH'))
{
    exit;
}

$options = get_option('pressable_cache_management_options');

if (isset($options['exempt_from_batcache']) && !empty($options['exempt_from_batcache']))
{
    /*
     * Sync (not just create) the pressable-cache-management mu-plugins index.
     * Previously this only copied when the file was missing, so loader fixes
     * (e.g. require -> require_once hardening) never reached existing sites.
     * Same md5-sync pattern as flush_batcache_for_woo_individual_page.php.
     */
    $pcm_mu_plugins_index     = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management.php';
    $pcm_mu_plugins_index_src = plugin_dir_path(__FILE__) . 'pressable_cache_management_mu_plugin_index.php';
    if (!file_exists($pcm_mu_plugins_index)
        || (file_exists($pcm_mu_plugins_index_src) && md5_file($pcm_mu_plugins_index_src) !== md5_file($pcm_mu_plugins_index)))
    {
        @copy($pcm_mu_plugins_index_src, $pcm_mu_plugins_index);
    }

    // Check if the pressable-cache-management directory exists or create the folder
    if (!file_exists(WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/')) {
        //create the directory
        wp_mkdir_p(WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/');
    }

    //Add the option from the textbox into the database
    update_option('exempt_from_batcache', $options['exempt_from_batcache']);

    /*
     * Sync the mu-plugin itself as well, for the same reason: guarded
     * function definitions in exclude_pages_from_batcache_mu_plugin.php
     * must be deployed to sites that already have an old, unguarded copy.
     */
    $obj_exclude_src  = plugin_dir_path(__FILE__) . 'exclude_pages_from_batcache_mu_plugin.php';
    $obj_exclude_dest = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_exclude_pages_from_batcache.php';
    $obj_exclude_is_fresh = !file_exists($obj_exclude_dest);

    if ($obj_exclude_is_fresh
        || (file_exists($obj_exclude_src) && md5_file($obj_exclude_src) !== md5_file($obj_exclude_dest)))
    {
        if (@copy($obj_exclude_src, $obj_exclude_dest) && $obj_exclude_is_fresh)
        {
            //Flush cache to enable activation take effect immediately (fresh enable only)
            wp_cache_flush();
        }
    }
}
else
{
    $obj_exclude_pages_from_batcache = WP_CONTENT_DIR . '/mu-plugins/pressable-cache-management/pcm_exclude_pages_from_batcache.php';
    if (file_exists($obj_exclude_pages_from_batcache))
    {
        unlink($obj_exclude_pages_from_batcache);

        //Flush cache to enable deactivation take effect immediately
        wp_cache_flush();
    }
    else
    {
        // File not found.
    }
}
