<?php // Custom function - Add custom functions to flush cache on page edit


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

// Get checkbox options and check if is not empty
$options = get_option('pressable_cache_management_options');

if (isset($options['flush_cache_page_edit_checkbox']) && !empty($options['flush_cache_page_edit_checkbox']))
{

    //Flush Batcache cache on page edit
    function clear_batcache_on_post_save()
    {
        wp_cache_flush();

        // Save time stamp to database if cache is flushed when a post or page was updated.
        $object_cache_flush_time = date(' jS F Y  g:ia') . "\nUTC";
        update_option('flush-cache-page-edit-time-stamp', $object_cache_flush_time);

        //Set transient for admin notice for 9 seconds
        set_transient('page_edit_notice', true, 9);
    }
    add_action('save_post', 'clear_batcache_on_post_save');

 }
