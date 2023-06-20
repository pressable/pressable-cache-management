<?php // Pressable Cache Management  - Validate Settings

// Disable direct file access
if (!defined("ABSPATH"))
{
    exit();
}

// callback: validate main options
function pressable_cache_management_callback_validate_options($input)
{
    if (empty($input))
    {
        $input = [];
    }

    // Extend batcache checkbox
    if (!isset($input["extend_batcache_checkbox"]))
    {
        $input["extend_batcache_checkbox"] = "";
    }

    $input["extend_batcache_checkbox"] = filter_var($input["extend_batcache_checkbox"] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT);

    // Flush object cache on theme and plugin update checkbox
    if (!isset($input["flush_cache_theme_plugin_checkbox"]))
    {
        $input["flush_cache_theme_plugin_checkbox"] = "";
    }

    $input["flush_cache_theme_plugin_checkbox"] = filter_var($input["flush_cache_theme_plugin_checkbox"] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT);

    // Flusch object cache on page and post update checkbox
    if (!isset($input["flush_cache_page_edit_checkbox"]))
    {
        $input["flush_cache_page_edit_checkbox"] = "";
    }

    $input["flush_cache_page_edit_checkbox"] = filter_var($input["flush_cache_page_edit_checkbox"] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT);

    // Flusch object cache on page and post delete
    if (!isset($input["flush_cache_on_page_post_delete_checkbox"]))
    {
        $input["flush_cache_on_page_post_delete_checkbox"] = "";
    }

    $input["flush_cache_on_page_post_delete_checkbox"] = filter_var($input["flush_cache_on_page_post_delete_checkbox"] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT);

    // Flush object cache on comment delete
    if (!isset($input["flush_cache_on_comment_delete_checkbox"]))
    {
        $input["flush_cache_on_comment_delete_checkbox"] = "";
    }

    $input["flush_cache_on_comment_delete_checkbox"] = filter_var($input["flush_cache_on_comment_delete_checkbox"] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT);

    // Flush Batcache for individual page
    if (!isset($input["flush_object_cache_for_single_page"]))
    {
        $input["flush_object_cache_for_single_page"] = "";
    }

    $input["flush_object_cache_for_single_page"] = filter_var($input["flush_object_cache_for_single_page"] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT);

    // Flush Batcache for WooCommerce individual page
    $input["flush_batcache_for_woo_product_individual_page_checkbox"] = isset($input["flush_batcache_for_woo_product_individual_page_checkbox"]) ? filter_var($input["flush_batcache_for_woo_product_individual_page_checkbox"] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT) : 0;

    // Enable Caching for pages which has wpp_ cookies
    if (!isset($input["cache_wpp_cookies_pages"]))
    {
        $input["cache_wpp_cookies_pages"] = "";
    }

    $input["cache_wpp_cookies_pages"] = filter_var($input["cache_wpp_cookies_pages"] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT);

    // Exclude Google Ads URL's with query string gclid from Batcache
    if (!isset($input["exclude_query_string_gclid_checkbox"]))
    {
        $input["exclude_query_string_gclid_checkbox"] = "";
    }

    $input["cache_wpp_cookies_pages"] = filter_var($input["exclude_query_string_gclid_checkbox"] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT);

    return $input;
}

// Exclude pages from Batcache
if (isset($input["exempt_from_batcache"]))
{
    $input["exempt_from_batcache"] = sanitize_text_field($input["exempt_from_batcache"]);
}

// callback: validate cdn options
function cdn_settings_tab_callback_validate_options($input)
{
    // Turn On/Off CDN
    //     $radio_options = pressable_cache_management_options_radio_button();
    //     if (!isset($input['cdn_on_off_radio_button']))
    //     {
    //         $input['cdn_on_off_radio_button'] = null;
    //     }
    //     if (!array_key_exists($input['cdn_on_off_radio_button'], $radio_options))
    //     {
    //         $input['cdn_on_off_radio_button'] = null;
    //     }
    //CDN Cache Extender
    if (!isset($input["cdn_cache_extender"]))
    {
        $input["cdn_cache_extender"] = "";
    }

    $input["cdn_cache_extender"] = filter_var($input["cdn_cache_extender"] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT);

    //Exlude images and WEBP
    if (!isset($input["exclude_jpg_png_webp_from_cdn"]))
    {
        $input["exclude_jpg_png_webp_from_cdn"] = "";
    }

    $input["exclude_jpg_png_webp_from_cdn"] = filter_var($input["exclude_jpg_png_webp_from_cdn"] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT);

    //Exclude .json and .js file
    if (!isset($input["exclude_json_from_cdn"]))
    {
        $input["exclude_json_from_cdn"] = "";
    }

    $input["exclude_json_from_cdn"] = filter_var($input["exclude_json_from_cdn"] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT);

    //Exclude all .css file
    if (!isset($input["exclude_css_from_cdn"]))
    {
        $input["exclude_css_from_cdn"] = "";
    }

    $input["exclude_css_from_cdn"] = filter_var($input["exclude_css_from_cdn"] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT);

    //Todo:
    //Exclude all font files from CDN
    //     if (!isset($input["exclude_font_files_from_cdn"])) {
    //         $input["exclude_font_files_from_cdn"] = "";
    //     }
    //     $input["exclude_font_files_from_cdn"] = filter_var(
    //         $input["exclude_font_files_from_cdn"] == 1 ? 1 : 0,
    //         FILTER_SANITIZE_NUMBER_INT
    //     );
    // Exclude a particular file from CDN
    if (isset($input["exclude_particular_file_from_cdn"]))
    {
        $input["exclude_particular_file_from_cdn"] = sanitize_text_field($input["exclude_particular_file_from_cdn"]);
    }

    return $input;
}


// callback: validate Edge Cache options
function egde_cache_settings_tab_callback_validate_options($input)
{
    

    return $input;
}


// callback: validate api connection options
function pressable_api_authentication_tab_callback_validate_options($input)
{
    // Site ID
    if (isset($input["pressable_site_id"]))
    {
        $input["pressable_site_id"] = sanitize_text_field($input["pressable_site_id"]);
    }

    // API Client ID
    if (isset($input["api_client_id"]))
    {
        $input["api_client_id"] = sanitize_text_field($input["api_client_id"]);
    }

    // API Client secrect
    if (isset($input["api_client_secret"]))
    {
        $input["capi_client_secret"] = sanitize_text_field($input["api_client_secret"]);
    }

    return $input;
}

// callback: validate branding options
function remove_pressable_branding_tab_callback_validate_options($input)
{
    // Turn On/Off Pressable branding option
    $radio_options = pressable_cache_management_options_radio_button();

    if (!isset($input["branding_on_off_radio_button"]))
    {
        $input["branding_on_off_radio_button"] = null;
    }
    if (!array_key_exists($input["branding_on_off_radio_button"], $radio_options))
    {
        $input["branding_on_off_radio_button"] = null;
    }

    return $input;
}
