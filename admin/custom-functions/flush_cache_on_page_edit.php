<?php // Custom function - Add custom functions to flush cache on page, post and custom post_types on edit

// disable direct file access
if (!defined("ABSPATH"))
{
    exit();
}


/**
 * This function is triggered when a post or page is edited in a Wordpress site.
 * The function first flushes the cache using the "wp_cache_flush" function.
 * It then checks the type of post that was edited (either "post" or "page") and sets 
 * a time stamp for when the cache was last flushed.
*/

$options = get_option("pressable_cache_management_options");

// Get checkbox options and check if is not empty
if (isset($options["flush_cache_page_edit_checkbox"]) && !empty($options["flush_cache_page_edit_checkbox"]))
{
    function clear_batcache_on_post_edit($post_id, $post, $update)
    {
        wp_cache_flush();
        if ($post->post_type === "post" || $post->post_type === "page")
        {
            if ($post->post_type === "post")
            {
                $object_cache_flush_time_post = date(" jS F Y g:ia") . "\nUTC" . "<b> — cache flushed due to " . $post->post_type . " edit: ". $post->post_title ."</b>";
                update_option("flush-cache-page-edit-time-stamp", $object_cache_flush_time_post);
            }
            elseif ($post->post_type === "page")
            {
                $object_cache_flush_time_page = date(" jS F Y g:ia") . "\nUTC" . "<b> — cache flushed due to " . $post->post_type . " edit: ". $post->post_title ."</b>";
                update_option("flush-cache-page-edit-time-stamp", $object_cache_flush_time_page);
            }
        }
        else
        {
            $object_cache_flush_time_post_type = date(" jS F Y g:ia") . "\nUTC" . "<b> — cache flushed due to " . $post->post_type . " edit: ". $post->post_title ."</b>";
            update_option("flush-cache-page-edit-time-stamp", $object_cache_flush_time_post_type);
        }
    }
    add_action("save_post", "clear_batcache_on_post_edit", 10, 3);
}
