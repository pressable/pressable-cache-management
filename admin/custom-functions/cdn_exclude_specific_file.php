<?php  //Exempt specific files from the Pressable CDN

// disable direct file access
if (!defined('ABSPATH'))
{
    exit;
}

if (!defined('IS_PRESSABLE'))
{
    return;
}

function pcm_exclude_single_file($output)
{
    // Get the excluded file from the options table
    $excluded_file = get_option('excluded_particular_file');

    // Check if excluded file is empty, and return if it is
    if (empty($excluded_file)) {
        return $output;
    }

    // Clean the excluded file by trimming spaces and slashes
    $excluded_file = trim($excluded_file);
    $excluded_file = preg_replace('/\s+/', '', $excluded_file); // Remove spaces
    $excluded_file = rtrim($excluded_file, '/'); // Remove trailing slashes

    $site_domain_url = $_SERVER['SERVER_NAME'];

    // Replace the CDN URL with the server's domain for the excluded file
    $output = preg_replace(
        '/\/\/' . preg_quote(DB_NAME, '/') . '\.v2\.pressablecdn\.com(.*?\/' . preg_quote($excluded_file, '/') . ')/i',
        '//' . $site_domain_url . '$1',
        $output
    );

    // Ensure there are no double slashes in the URLs (except after "https:" or "http:")
    $output = preg_replace('/([^:])\/{2,}/', '$1/', $output);

    return $output;
}

function pcm_custom_template_redirect()
{
    ob_start();
    ob_start('pcm_exclude_single_file');
    ob_flush();
}

// Grab the site contents and prep for the search/replace
add_action('template_redirect', 'pcm_custom_template_redirect');
