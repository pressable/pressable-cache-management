<?php // Custom function - Add custom functions to flush cache on page or post edit

// disable direct file access
if (!defined("ABSPATH")) {
    exit();
}

$options = get_option("pressable_cache_management_options");

// Get checkbox options and check if is not empty
if (
    isset($options["flush_cache_page_edit_checkbox"]) &&
    !empty($options["flush_cache_page_edit_checkbox"])
) {
    function clear_batcache_on_post_edit($post_ID, $post, $update)
    {
        if ($post->post_type === "post" || $post->post_type === "page") {
            wp_cache_flush();

            if ($post->post_type === "post") {
                $object_cache_flush_time_post =
                    date(" jS F Y  g:ia") .
                    "\nUTC" .
                    "<b> — cache flushed due to post edit</b>";
                update_option(
                    "flush-cache-page-edit-time-stamp",
                    $object_cache_flush_time_post
                );
            } elseif ($post->post_type === "page") {
                $object_cache_flush_time_page =
                    date(" jS F Y  g:ia") .
                    "\nUTC" .
                    "<b> — cache flushed due to page edit</b>";
                update_option(
                    "flush-cache-page-edit-time-stamp",
                    $object_cache_flush_time_page
                );
            }
        }
    }
    add_action("save_post", "clear_batcache_on_post_edit", 10, 3);
}
