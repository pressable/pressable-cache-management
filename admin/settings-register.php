<?php
// Pressable Cache Management - Register Settings

// Disable direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Register plugin settings
function pressable_cache_management_register_settings() {
	// Save options for object cache tab
	register_setting(
		'pressable_cache_management_options',
		'pressable_cache_management_options',
		'pressable_cache_management_callback_validate_options'
	);

	// Save options for edge cache tab
	register_setting(
		'edge_cache_tab_options',
		'edge_cache_settings_tab_options',
		'edge_cache_settings_tab_callback_validate_options'
	);

	// Save options for branding tab
	register_setting(
		'remove_pressable_branding_tab_options',
		'remove_pressable_branding_tab_options',
		'remove_pressable_branding_tab_callback_validate_options'
	);

	$remove_pressable_branding_tab_options = get_option( 'remove_pressable_branding_tab_options' );

	if ( $remove_pressable_branding_tab_options && 'disable' == $remove_pressable_branding_tab_options['branding_on_off_radio_button'] ) {
		add_settings_section(
			'pressable_cache_management_section_cache',
			esc_html__( 'Cache Control Management', 'pressable_cache_management' ),
			'pressable_cache_management_callback_section_cache',
			'pressable_cache_management'
		);
	} else {
		add_settings_section(
			'pressable_cache_management_section_cache',
			esc_html__( 'Cache Management By', 'pressable_cache_management' ),
			'pressable_cache_management_callback_section_cache',
			'pressable_cache_management'
		);
	}

	// Edge Cache settings tab page
	add_settings_section(
		'pressable_cache_management_section_edge_cache',
		esc_html__( 'Manage Edge Cache Settings', 'edge_cache_settings_tab' ),
		'pressable_cache_management_callback_section_edge_cache',
		'edge_cache_settings_tab'
	);

	// Remove Pressable branding tab page
	add_settings_section(
		'pressable_cache_management_section_branding',
		esc_html__( 'Show or Hide Plugin Branding', 'remove_pressable_branding_tab' ),
		'pressable_cache_management_callback_section_branding',
		'remove_pressable_branding_tab'
	);

	// Verify if the options exist
	if ( false == get_option( 'pressable_cache_management_options' ) ) {
		add_option( 'pressable_cache_management_options' );
	}

	/*
	 * Object Cache Management Tab
	 */
	add_settings_field(
		'flush_cache_button',
		esc_html__( 'Flush Object Cache', 'pressable_cache_management' ),
		'pressable_cache_management_callback_field_button',
		'pressable_cache_management',
		'pressable_cache_management_section_cache',
		array(
			'id'    => 'flush_cache_button',
			'label' => esc_html__( 'Flush object cache (Database)', 'pressable_cache_management' ),
		)
	);

	add_settings_field(
		'extend_batcache_checkbox',
		esc_html__( 'Extend Batcache', 'pressable_cache_management' ),
		'pressable_cache_management_callback_field_extend_cache_checkbox',
		'pressable_cache_management',
		'pressable_cache_management_section_cache',
		array(
			'id'    => 'extend_batcache_checkbox',
			'label' => esc_html__( 'Extend Batcache storage time by 24 hours', 'pressable_cache_management' ),
		)
	);

	add_settings_field(
		'flush_cache_theme_plugin_checkbox',
		esc_html__( 'Flush Cache on Update', 'pressable_cache_management' ),
		'pressable_cache_management_callback_field_plugin_theme_update_checkbox',
		'pressable_cache_management',
		'pressable_cache_management_section_cache',
		array(
			'id'    => 'flush_cache_theme_plugin_checkbox',
			'label' => esc_html__( 'Flush cache automatically on plugin & theme update', 'pressable_cache_management' ),
		)
	);

	add_settings_field(
		'flush_cache_page_edit_checkbox',
		esc_html__( 'Flush Cache on Edit', 'pressable_cache_management' ),
		'pressable_cache_management_callback_field_page_edit_checkbox',
		'pressable_cache_management',
		'pressable_cache_management_section_cache',
		array(
			'id'    => 'flush_cache_page_edit_checkbox',
			'label' => esc_html__( 'Flush cache automatically when page/post/post_types are updated', 'pressable_cache_management' ),
		)
	);

	add_settings_field(
		'flush_cache_on_page_post_delete_checkbox',
		esc_html__( 'Flush Cache on Page Delete', 'pressable_cache_management' ),
		'pressable_cache_management_callback_field_page_post_delete_checkbox',
		'pressable_cache_management',
		'pressable_cache_management_section_cache',
		array(
			'id'    => 'flush_cache_on_page_post_delete_checkbox',
			'label' => esc_html__( 'Flush cache automatically when published pages/posts are deleted', 'pressable_cache_management' ),
		)
	);

	add_settings_field(
		'flush_cache_on_comment_delete_checkbox',
		esc_html__( 'Flush Cache on Comment Delete', 'pressable_cache_management' ),
		'pressable_cache_management_callback_field_comment_delete_checkbox',
		'pressable_cache_management',
		'pressable_cache_management_section_cache',
		array(
			'id'    => 'flush_cache_on_comment_delete_checkbox',
			'label' => esc_html__( 'Flush cache automatically when comments are deleted', 'pressable_cache_management' ),
		)
	);

	add_settings_field(
		'flush_object_cache_for_single_page',
		esc_html__( 'Flush Batcache for Individual Pages', 'pressable_cache_management' ),
		'pressable_cache_management_callback_field_flush_batcache_particular_page_checbox',
		'pressable_cache_management',
		'pressable_cache_management_section_cache',
		array(
			'id'    => 'flush_object_cache_for_single_page',
			'label' => esc_html__( 'Flush Batcache for individual pages', 'pressable_cache_management' ),
		)
	);

	add_settings_field(
		'flush_batcache_for_woo_product_individual_page_checkbox',
		esc_html__( 'Flush Batcache for Woo Product Pages', 'pressable_cache_management' ),
		'pressable_cache_management_callback_field_flush_batcache_woo_product_page_checbox',
		'pressable_cache_management',
		'pressable_cache_management_section_cache',
		array(
			'id'    => 'flush_batcache_for_woo_product_individual_page_checkbox',
			'label' => esc_html__( 'Flush Batcache for WooCommerce product pages', 'pressable_cache_management' ),
		)
	);

	add_settings_field(
		'exempt_from_batcache',
		esc_html__( 'Exclude Page from Batcache & Edge Cache', 'pressable_cache_management' ),
		'pressable_cache_management_callback_field_exempt_batcache_text',
		'pressable_cache_management',
		'pressable_cache_management_section_cache',
		array(
			'id'    => 'exempt_from_batcache',
			'label' => esc_html__( 'To exclude multiple pages separate with comma  ex /your-site.com/, /about-us/, /info/', 'pressable_cache_management' ),
		)
	);

	/*
	 * Edge Cache Management Tab
	 */
	add_settings_field(
		'edge_cache_on_off_radio_button',
		esc_html__( 'Turn On/Off Edge Cache', 'edge_cache_settings_tab' ),
		'pressable_cache_management_callback_field_extend_edge_cache_radio_button',
		'edge_cache_settings_tab',
		'pressable_cache_management_section_edge_cache',
		array(
			'id'    => 'edge_cache_on_off_radio_button',
			'label' => esc_html__( 'Turn on/off Edge Cache', 'edge_cache_settings_tab' ),
		)
	);

	/*
	 * Display Purge Edge Cache button
	 */
		add_settings_field(
			'purge_edge_cache_button',
			esc_html__( 'Purge Edge Cache', 'edge_cache_settings_tab' ),
			'pressable_edge_cache_flush_management_callback_field_button',
			'edge_cache_settings_tab',
			'pressable_cache_management_section_edge_cache',
			array(
				'id'    => 'purge_edge_cache_button',
				'label' => esc_html__( 'Purge Edge Cache', 'edge_cache_settings_tab' ),
			)
		);

	/*
	 * Remove Pressable Branding Tab
	 */
	add_settings_field(
		'branding_on_off_radio_button',
		esc_html__( 'Hide or Show Plugin Branding', 'remove_pressable_branding_tab' ),
		'pressable_cache_management_callback_field_extend_remove_branding_radio_button',
		'remove_pressable_branding_tab',
		'pressable_cache_management_section_branding',
		array(
			'id'    => 'branding_on_off_radio_button',
			'label' => esc_html__( 'Hide or show plugin branding', 'remove_pressable_branding_tab' ),
		)
	);
}

add_action( 'admin_init', 'pressable_cache_management_register_settings' );
