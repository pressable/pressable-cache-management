<?php //Extend Pressable's cache-control from 7 days until 10 years for static assets


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

function pressablecdn_template_redirect()
{
    ob_start('pressablecdn_ob_call');
    ob_flush();
}

/*********
 * This code appends a query string
 * "extend_cdn" to the registered styles
 * and scripts of the theme.
 **********/

// Only apply the modification to pages that are not RSS feed pages and robots.txt.
if (strpos($_SERVER['REQUEST_URI'], '/feed/') === false && strpos($_SERVER['REQUEST_URI'], 'robots.txt') === false) 
{

if (strpos($_SERVER['REQUEST_URI'], '/feed/') === false)
{

//Dont append query string if CDN is disabled
if (get_option('cdnenabled') == 'disable') {
    return;
}


    function pcm_append_querystring_theme_scripts()
    {
        $extensions = array(
            '.css',
            '.js'
        );
        $styles = wp_styles();
        foreach ($styles->registered as & $style)
        {
            if (is_null($style->src) || empty($style->src)) continue;
            // check if the src path contains any of the extensions
            foreach ($extensions as $ext)
            {
                if (strpos($style->src, $ext) !== false)
                {
                    $style->src = add_query_arg('extend_cdn', '', $style->src);
                    break;
                }
            }
        }

        $scripts = wp_scripts();
        foreach ($scripts->registered as & $script)
        {
            if (is_null($script->src) || empty($script->src)) continue;
            // check if the src path contains any of the extensions
            foreach ($extensions as $ext)
            {
                if (strpos($script->src, $ext) !== false)
                {
                    $script->src = add_query_arg('extend_cdn', '', $script->src);
                    break;
                }
            }
        }
    }
    add_action('wp_enqueue_scripts', 'pcm_append_querystring_theme_scripts', PHP_INT_MAX);

    /*********
     * This code uses a regular expression to search for <img and src attributes in the HTML
     * And appends the query string ?extend_cdn before the file extension of the src attribute
     * value for <img> tags. The code only appends the query string to  URL's with pressablecdn.com
     **********/

    function extend_cache_for_images($html)
    {
        if (empty($html))
        {
            return;
        }

        /*******************************************************************************************************************************************
         * This code appends the extend_cdn query string to all of the theme's CSS and JavaScript files, except for gtm.js (used by Google Analytics)
         * and jquery.json.min.js (used by Gravity Forms). Appending a query string might break the function of these files.
         * This code loops through the wp_styles() and wp_scripts() objects and checking if the src path of each file contains the .css or
         * .js extensions. If it does, the add_query_arg() function is used to append the extend_cdn query string to the src.
         * The extend_cdn query string is used to load the theme's CSS and JavaScript files from a CDN, which can improve performance.
         *******************************************************************************************************************************************/

        libxml_use_internal_errors(true);
$dom = new DOMDocument();
$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
libxml_clear_errors();

$images = $dom->getElementsByTagName("img");
foreach ($images as $img) {
    $src = $img->getAttribute("src");
    $srcset = $img->getAttribute("srcset");
    $dataSrc = $img->getAttribute("data-src");
    $dataSrcset = $img->getAttribute("data-srcset");

    // Append query string to images inside src, srcset, data-srcset, or data-src
    if (!empty($src) && !preg_match("/\.webp$/i", $src) && preg_match("/\.(jpe?g|png|gif)$/i", $src)) {
        $img->setAttribute("src", $src . "?extend_cdn");
    }

    if (!empty($srcset)) {
        $srcsetArray = explode(",", $srcset);
        $newSrcsetArray = array_map(function ($srcsetItem) {
            $urlParts = explode(" ", trim($srcsetItem));
            $url = $urlParts[0];
            if (!preg_match("/\.webp$/i", $url) && preg_match("/\.(jpe?g|png|gif)$/i", $url)) {
                $newUrl = $url . "?extend_cdn";
                $urlParts[0] = $newUrl;
            }
            return implode(" ", $urlParts);
        }, $srcsetArray);
        $newSrcset = implode(", ", $newSrcsetArray);
        $img->setAttribute("srcset", $newSrcset);
    }

    if (!empty($dataSrc)) {
        $dataSrcArray = explode(",", $dataSrc);
        $newDataSrcArray = array_map(function ($dataSrcItem) {
            $urlParts = explode(" ", trim($dataSrcItem));
            $url = $urlParts[0];
            if (!preg_match("/\.webp$/i", $url) && preg_match("/\.(jpe?g|png|gif)$/i", $url)) {
                $newUrl = $url . "?extend_cdn";
                $urlParts[0] = $newUrl;
            }
            return implode(" ", $urlParts);
        }, $dataSrcArray);
        $newDataSrc = implode(", ", $newDataSrcArray);
        $img->setAttribute("data-src", $newDataSrc);
    }

    if (!empty($dataSrcset)) {
        $dataSrcsetArray = explode(",", $dataSrcset);
        $newDataSrcsetArray = array_map(function ($dataSrcsetItem) {
            $urlParts = explode(" ", trim($dataSrcsetItem));
            $url = $urlParts[0];
            if (!preg_match("/\.webp$/i", $url) && preg_match("/\.(jpe?g|png|gif)$/i", $url)) {
                $newUrl = $url . "?extend_cdn";
                $urlParts[0] = $newUrl;
            }
            return implode(" ", $urlParts);
        }, $dataSrcsetArray);
        $newDataSrcset = implode(", ", $newDataSrcsetArray);
        $img->setAttribute("data-srcset", $newDataSrcset);
    }
}

$links = $dom->getElementsByTagName("link");
foreach ($links as $link) {
    $rel = $link->getAttribute("rel");
    $href = $link->getAttribute("href");

    if ($rel === "preload" && preg_match("/\.(eot|otf|ttf|woff|woff2)$/i", $href)) {
        $href = add_query_arg('extend_cdn', '', $href);
        $link->setAttribute("href", $href);
    }
}


// Perform str_replace for data URI SVG image to prevent broken webp images
$html = str_replace("data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D", "data:image/svg+xml;charset=utf-8,%3Csvg xmlns%3D", $dom->saveHTML());
$html = str_replace("data:image/svg+xml;charset=utf-8,%3Csvgxmlns%3D", "data:image/svg+xml;charset=utf-8,%3Csvg xmlns%3D", $html);

$html = str_replace("data:image/svg+xml;charset=utf-8,%20%3Csvg%20xmlns%3D", "data:image/svg+xml;charset=utf-8,%20%3Csvg xmlns%3D", $html);

return $html;




    }

    //If CDN extender is enabled don't append query string to images files
    $options = get_option('cdn_settings_tab_options');

    if (isset($options['exclude_jpg_png_webp_from_cdn']) && !empty($options['exclude_jpg_png_webp_from_cdn']))
    {
        return;
    }

    function pressablecdn_ob_call($html)
    {

        $html = extend_cache_for_images($html);

        return $html;
    }

}

if (!is_admin() && strpos($_SERVER['REQUEST_URI'], "wp-admin") === false && strpos($_SERVER['REQUEST_URI'], "wp-login.php") === false)
{
    add_action('template_redirect', 'pressablecdn_template_redirect');
}
