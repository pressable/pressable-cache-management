<?php
// Plugin Name: Exempt Images .jpg, .jpeg, .png, .gif, .webp From CDN

// Disable direct file access
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('IS_PRESSABLE')) {
    return;
}

function pcm_jpg_png_webp_cdn_exempter($html) {
    // Define the CDN URL and server name
    $cdn_url = DB_NAME . '.v2.pressablecdn.com';
    $server_name = $_SERVER['SERVER_NAME'];

    // Define an array of image extensions
    $image_extensions = array('.jpg', '.jpeg', '.png', '.gif', '.webp');

    // Iterate over each image extension and replace CDN URLs
    foreach ($image_extensions as $extension) {
        $pattern = '/(' . preg_quote($cdn_url, '/') . ')(.*?' . preg_quote($extension, '/') . ')/i';
        $replacement = $server_name . '$2';
        $html = preg_replace($pattern, $replacement, $html);
    }

    return $html;
}

function pcm_jpg_png_webp_cdn_template_redirect() {
    ob_start('pcm_jpg_png_webp_cdn_exempter');
}

add_action('template_redirect', 'pcm_jpg_png_webp_cdn_template_redirect');
