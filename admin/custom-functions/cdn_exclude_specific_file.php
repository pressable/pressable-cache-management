<?php
/*
Plugin Name: Exclude specific files from the CDN
Plugin URI: https://pressable.com
Description: Exempt specific files from the Pressable CDN
Version: 1.0.1
Author: Pressable
Author URI: https://pressable.com
*/

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
        stripslashes(rtrim($excluded_file,  '/'));

        $output = preg_replace('/' . DB_NAME . '.v2.pressablecdn.com(.*)(' . get_option('excluded_particular_file') .')/i', $_SERVER['SERVER_NAME'] . '/$1$2', $output);
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
    