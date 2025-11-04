<?php // Pressable Cache Management - Validate Settings

// Disable direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// Callback: validate main options
function pressable_cache_management_callback_validate_options( $input ) {
	if ( empty( $input ) ) {
		$input = array();
	}

	// Extend batcache checkbox
	if ( ! isset( $input['extend_batcache_checkbox'] ) ) {
		$input['extend_batcache_checkbox'] = '';
	}
	$input['extend_batcache_checkbox'] = filter_var( $input['extend_batcache_checkbox'] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT );

	// Flush object cache on theme and plugin update checkbox
	if ( ! isset( $input['flush_cache_theme_plugin_checkbox'] ) ) {
		$input['flush_cache_theme_plugin_checkbox'] = '';
	}
	$input['flush_cache_theme_plugin_checkbox'] = filter_var( $input['flush_cache_theme_plugin_checkbox'] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT );

	// Flush object cache on page and post update
	if ( ! isset( $input['flush_cache_page_edit_checkbox'] ) ) {
		$input['flush_cache_page_edit_checkbox'] = '';
	}
	$input['flush_cache_page_edit_checkbox'] = filter_var( $input['flush_cache_page_edit_checkbox'] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT );

	// Flush object cache on page and post delete
	if ( ! isset( $input['flush_cache_on_page_post_delete_checkbox'] ) ) {
		$input['flush_cache_on_page_post_delete_checkbox'] = '';
	}
	$input['flush_cache_on_page_post_delete_checkbox'] = filter_var( $input['flush_cache_on_page_post_delete_checkbox'] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT );

	// Flush object cache on comment delete
	if ( ! isset( $input['flush_cache_on_comment_delete_checkbox'] ) ) {
		$input['flush_cache_on_comment_delete_checkbox'] = '';
	}
	$input['flush_cache_on_comment_delete_checkbox'] = filter_var( $input['flush_cache_on_comment_delete_checkbox'] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT );

	// Flush Batcache for individual page
	if ( ! isset( $input['flush_object_cache_for_single_page'] ) ) {
		$input['flush_object_cache_for_single_page'] = '';
	}
	$input['flush_object_cache_for_single_page'] = filter_var( $input['flush_object_cache_for_single_page'] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT );

	// Flush Batcache for WooCommerce individual page
	$input['flush_batcache_for_woo_product_individual_page_checkbox'] = isset( $input['flush_batcache_for_woo_product_individual_page_checkbox'] ) ? filter_var( $input['flush_batcache_for_woo_product_individual_page_checkbox'] == 1 ? 1 : 0, FILTER_SANITIZE_NUMBER_INT ) : 0;

	return $input;
}

// Exclude pages from Batcache
if ( isset( $input['exempt_from_batcache'] ) ) {
	$input['exempt_from_batcache'] = sanitize_text_field( $input['exempt_from_batcache'] );
}

// Callback: validate Edge Cache options
function egde_cache_settings_tab_callback_validate_options( $input ) {
	return $input;
}

// Callback: validate branding options
function remove_pressable_branding_tab_callback_validate_options( $input ) {
	// Turn On/Off Pressable branding option
	$radio_options = pressable_cache_management_options_radio_button();

	if ( ! isset( $input['branding_on_off_radio_button'] ) ) {
		$input['branding_on_off_radio_button'] = null;
	}
	if ( ! array_key_exists( $input['branding_on_off_radio_button'], $radio_options ) ) {
		$input['branding_on_off_radio_button'] = null;
	}

	return $input;
}
