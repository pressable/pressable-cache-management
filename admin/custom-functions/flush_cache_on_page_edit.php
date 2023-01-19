<?php // Custom function - Add custom functions to flush cache on page edit


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}


$options = get_option('pressable_cache_management_options');

// Get checkbox options and check if is not empty
if (isset($options['flush_cache_page_edit_checkbox']) && !empty($options['flush_cache_page_edit_checkbox']))
{

    //Flush Batcache cache on page edit
    function clear_batcache_on_post_edit()
    {
        wp_cache_flush();

        // Save time stamp to database if cache is flushed when a post or page was updated.
        $object_cache_flush_time = date(' jS F Y  g:ia') . "\nUTC";
        update_option('flush-cache-page-edit-time-stamp', $object_cache_flush_time);


    }
    add_action('save_post', 'clear_batcache_on_post_edit');

 }
