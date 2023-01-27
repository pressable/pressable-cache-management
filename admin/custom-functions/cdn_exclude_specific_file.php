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

    //Set variable as default to prevent variable not defined error
    if (!isset($excluded_file))
    {
        $excluded_file = '';
    }

    // $excluded_file = get_option('excluded_particular_file');
    $excluded_file = preg_replace('/\s+/', '', $excluded_file);
    stripslashes(rtrim($excluded_file, '/'));

    $extend_cdn = "extend_cdn";
    $excluded_file = get_option('excluded_particular_file');
    $site_domain_url = $_SERVER['SERVER_NAME'];

    if (empty($excluded_file))
    {
        return;
    }
    else
    {
		//Remove instances of extend_cdn query string to exempt files from cdn once it is excluded from CDN
        $output = preg_replace('/((?!pressablecdn.com)(' . $excluded_file . ')[^"]*)(\?[^"]*)/i', '$2', $output);
		
        $output = preg_replace('/' . DB_NAME . '.v2.pressablecdn.com(.*)(' . get_option('excluded_particular_file') . ')/i', $_SERVER['SERVER_NAME'] . '/$1$2', $output);
        return $output;

    }

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
