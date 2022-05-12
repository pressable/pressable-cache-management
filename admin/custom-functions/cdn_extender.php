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
  ob_start();
  ob_start( 'pressablecdn_ob_call' );
  ob_flush();
}

function pressablecdn_ob_call( $html ) {
  $cdn_extensions = array('.jpg','.jpeg','.gif','.png','.css','.bmp','.js','.ico');
  foreach ( $cdn_extensions as $cdn_extension ) {
    $html = str_replace($cdn_extension, $cdn_extension.'?extend_cdn', $html);
    /* Exclude Google Tag Manager gtm.js from Pressable CDN to fix Google tracking issue bug */
    $html  = str_replace("gtm.js?extend_cdn","gtm.js", $html);
  }
  return $html;
}

if( !is_admin() && strpos($_SERVER['REQUEST_URI'],"wp-admin") === false && strpos($_SERVER['REQUEST_URI'],"wp-login.php") === false ) {
  add_action( 'template_redirect', 'pressablecdn_template_redirect');
}
