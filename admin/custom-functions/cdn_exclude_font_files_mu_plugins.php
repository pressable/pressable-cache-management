<?php // Pressable Cache Management - Exclude .woff .woff2 .otf .ttf, eot from CDN caching

// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}


if (!defined('IS_PRESSABLE'))
    {
        return;
    }


    function pcm_exclude_fonts_from_cdn_exempter($output)
    {
        
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
   
        //Exclude font files from CDN
         $output = preg_replace('/' . DB_NAME . '.v2.pressablecdn.com(.*)(.woff|.woff2|.otf|.ttf|.eot)/i', $_SERVER['SERVER_NAME'] . '$1$2', $output);

        //Return result for output
        return $output;
    }

    function pcm_exclude_fonts_from_cdn_template_redirect()
    {
        ob_start();
        ob_start('pcm_exclude_fonts_from_cdn_exempter');
        ob_flush();
    }

    // Grab the site contents and prep for the search/replace
    add_action('template_redirect', 'pcm_exclude_fonts_from_cdn_template_redirect');
