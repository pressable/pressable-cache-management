<?php //Plugin Name: Exclude website pages from the Batcache


if (!defined('IS_PRESSABLE'))
    {
        return;
    }


$options = get_option('pressable_cache_management_options');

// Import options from the database
$exempted_pages = $options['exempt_from_batcache'];

if (function_exists('batcache_cancel')) {
    // Use explode to split the pages using a comma and trim spaces
    $exempted_pages = array_map('trim', explode(',', $exempted_pages));

    // Add a check for the homepage
    foreach ($exempted_pages as $page) {
        if (strpos($_SERVER['REQUEST_URI'], $page) !== false || $_SERVER['REQUEST_URI'] === '/') {
            batcache_cancel();
            break; // Exit the loop as we've already canceled caching
        }
    }
}

