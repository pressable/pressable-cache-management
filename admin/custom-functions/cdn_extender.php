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

// Only apply the modification to pages that are not RSS feed pages.
if (strpos($_SERVER['REQUEST_URI'], '/feed/') === false)
{

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

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $images = $dom->getElementsByTagName("img");
        foreach ($images as $img)
        {
            $src = $img->getAttribute("src");
            if (preg_match("/\.webp$/i", $src))
            {
                continue;
            }
            if (preg_match("/data:image\/svg\+xml;/i", $src))
            {
                continue;
            }
            else
            {
                $img->setAttribute("src", $src . "?extend_cdn");
            }
            /*
             * Skips links inside src and srcset attributes that contain data:image/svg+xml;
             * and does not append "extend_cdn" to prevent imagify webp generated images from breaking
            */

            $srcset = $img->getAttribute("srcset");
            if (!empty($srcset))
            {
                $srcset = preg_replace_callback("/(https?:\/\/[^\s]+)\.webp/i", function ($matches)
                {
                    return $matches[1] . "?extend_cdn";
                }
                , $srcset);
                $srcset = preg_replace_callback("/data:image\/svg\+xml;/i", function ($matches)
                {
                    return "";
                }
                , $srcset);
                $img->setAttribute("srcset", $srcset);
            }
        }
        $fonts = $dom->getElementsByTagName("link");
        foreach ($fonts as $font)
        {
            $href = $font->getAttribute("href");
            /**********
             * The code searches for any <link> tags with href attributes containing certain font file types,
             * and appends the same query string "extend_cdn" to the value of the href attribute.
             **********/
            if (preg_match("/\.(eot|otf|ttf|woff|woff2)$/i", $href))
            {
                if (!in_array($href, ['gtm.js', 'jquery.json.min.js']))
                {
                    /******
                     * Exlude jquery.json.min.js and gtm.js. Appending query string to
                     * these files can cause Gravityform and Google analytics plugin not
                     * to function correctly.
                     ****/
                    $font->setAttribute("href", $href . "?extend_cdn");
                }
            }
        }
        return $dom->saveHTML();
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
