<?php
/*
Plugin Name: Exempt JS/JSON From CDN
Plugin URI: https://pressable.com
Description: Exempt JS and JSON files from the Pressable CDN
Version: 1.0.1
Author: Pressable
Author URI: https://pressable.com
*/

if (!defined('IS_PRESSABLE'))
{
    return;
}

function pcm_json_js_cdn_exempter($output)
{
    //Commented out to prevent HTML Injection
    // $output = html_entity_decode($output);
    /*
     * The line below replaces everything it finds before the comma (,) with what we define after it.
     * In this case, that is replacing any CDN URL containing a specific file type extension or name with
     * the site's own domain + path to the file (effectively exempting it from being served from the CDN).
     *
     * DB_NAME is used to grab the 9 digit value used for database names and each site's CDN URL.
     * (.*) is a wildcard which allows this to work for files in any directory and with any name.
     * (.json) is the file type or name you want to exclude, change to (.jpg) to exclude jpg files for example.
     * For multiple file types, separate with a | e.g. (.jpg|.png|.css).
     * To exclude a specific file, replace (.json) with (filename.ext) - e.g. (fusion-column-bg-image.js).
     * $1$2 combines the values of (.*) and (.json) to form a complete file path (e.g. /wp-content/uploads/2021/02/file.json).
    */

    //Set variable as default to prevent variable not defined error
    if (!isset($json_output))
    {
        $json_output = '';
    }

    //Exclude all .json files from CDN
    $json_output = preg_replace('/' . DB_NAME . '.v2.pressablecdn.com(.*)(.json)/i', $_SERVER['SERVER_NAME'] . '/$1$2', $json_output);

    //Exclude all .js files from CDN
    $output = preg_replace('/' . DB_NAME . '.v2.pressablecdn.com([^"].*)(js)/i', $_SERVER['SERVER_NAME'] . '$1$2', $output);
	
	//Remove instances of extend_cdn query string to exempt .js from CDN once it is excluded from CDN
    $output = str_replace("?extend_cdn&#038;", "?", $output);
	
	

    //Return result excluded  for .js and .json
    return $output . $json_output;
}

function pcm_json_js_template_redirect()
{
    ob_start();
    ob_start('pcm_json_js_cdn_exempter');
    ob_flush();
}

// Grab the site contents and prep for the search/replace
add_action('template_redirect', 'pcm_json_js_template_redirect');
