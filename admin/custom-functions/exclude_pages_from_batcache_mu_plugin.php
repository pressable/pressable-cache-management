<?php //Plugin Name: Exclude website pages from the Batcache


if (!defined('IS_PRESSABLE'))
    {
        return;
    }


$options = get_option('pressable_cache_management_options');

//Import options from the database
$exempted_pages = $options['exempt_from_batcache'];

// Use explode to split the pages using comma
if (function_exists('batcache_cancel')) {
    $exempted_pages = explode(', ', $exempted_pages);

    foreach ($exempted_pages as $page) {
        if (strpos($_SERVER['REQUEST_URI'], $page) !== false) {
            batcache_cancel();
        }
    }
}
