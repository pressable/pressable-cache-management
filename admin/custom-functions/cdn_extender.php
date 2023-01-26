<?php
/*
Plugin Name: Pressable CDN Extender
Plugin URI: https://pressable.com/knowledgebase/modify-cache-control-header-pressable/
Description: Extend Pressable's cache-control from 7 days until 10 years for static assets
Version: 1.0
Author: Pressable
Author URI: https://pressable.com
*/


function pressablecdn_template_redirect() {
  ob_start( 'pressablecdn_ob_call' );
  ob_flush();
}
	 
/*********
* This code is a WordPress function that is used to append a query string 
* "extend_cdn" to the registered styles and scripts of the theme.
**********/

function pcm_append_querystring_theme_scripts() {
    $extensions = array('.css','.js');
    $styles = wp_styles();
    foreach ($styles->registered as &$style) {
        if(is_null($style->src) || empty($style->src)) continue;
        // check if the src path contains any of the extensions
        foreach($extensions as $ext){
            if(strpos($style->src, $ext) !== false){
                $style->src = add_query_arg( 'extend_cdn', '', $style->src );
                break;
            }
        }
    }

    $scripts = wp_scripts();
    foreach ($scripts->registered as &$script) {
        if(is_null($script->src) || empty($script->src)) continue;
        // check if the src path contains any of the extensions
        foreach($extensions as $ext){
            if(strpos($script->src, $ext) !== false){
                $script->src = add_query_arg( 'extend_cdn', '', $script->src );
                break;
            }
        }
    }
}
add_action( 'wp_enqueue_scripts', 'pcm_append_querystring_theme_scripts', PHP_INT_MAX );


/*********
* This code uses a regular expression to search for <img and src attributes in the HTML 
* And appends the query string ?extend_cdn before the file extension of the src attribute
* value for <img> tags. The code only appends the query string to  URL's with pressablecdn.com
**********/

function pressablecdn_ob_call( $html ) {

$html = preg_replace('/(<img[^>]+src[\s]*=[\s]*["\'])(https?:\/\/[^\s"\']+pressablecdn\.com[^"\']+)(["\'])/i', '$1$2?extend_cdn$3', $html);

	
  /********** 
   * The code searches for any <link> tags with href attributes containing certain font file types, 
   * and appends the same query string "extend_cdn" to the value of the href attribute.
   **********/
   
   $html = preg_replace('/(<link[^>]+href[\s]*=[\s]*["\'])([^"\']+\.(eot|otf|svg|ttf|woff|woff2))(["\'])/i', '$1$2?extend_cdn$4', $html);
   //$html = preg_replace('/(<link[^>]+href[\s]*=[\s]*["\'])(https?:\/\/' . DB_NAME . '.v2.pressablecdn.com\/)([^"\']+\.(eot|otf|svg|ttf|woff|woff2))(["\'])/i', '$1$2$3?extend_cdn$5', $html);
  
  
    /******
     * Rename instances of jquery.js?extend_cdnon.min.js to 
     * jquery.json.min.js to fix Gravityform jquery.json.min.js 
     * file from 404'ing due to extend_cdn renianming it incorrectly. 
     ****/
	  $html  = str_replace("jquery.js?extend_cdnon.min.js","jquery.json.min.js", $html);

    /* Exclude Google Tag Manager gtm.js from Pressable CDN to fix Google tracking issue bug */
    $html  = str_replace("gtm.js?extend_cdn","gtm.js", $html);

	
  return $html;
}

// }

if( !is_admin() && strpos($_SERVER['REQUEST_URI'],"wp-admin") === false && strpos($_SERVER['REQUEST_URI'],"wp-login.php") === false ) {
  add_action( 'template_redirect', 'pressablecdn_template_redirect');
}
