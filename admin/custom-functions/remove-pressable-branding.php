<?php
/**
 * Pressable Cache Management - Custom function to turn on/off Pressable branding.
 *
 * @package Pressable
 */

/******************************
 * Show branding Option.
 *******************************/

$pressable_branding = false;

$hide_pressable_branding_tab_options = get_option( 'remove_pressable_branding_tab_options' );

// Check if options are set before processing.
if ( isset( $hide_pressable_branding_tab_options['branding_on_off_radio_button'] ) && ! empty( $hide_pressable_branding_tab_options['branding_on_off_radio_button'] ) ) {
	$hide_pressable_branding_tab_options = sanitize_text_field( $hide_pressable_branding_tab_options['branding_on_off_radio_button'] );
}

// Set radion button state to default.
if ( 'enable' === $hide_pressable_branding_tab_options ) {
	$hide_pressable_branding_tab_options = get_option( 'remove_pressable_branding_tab_options' );
} else {

	$pressable_branding = false;
	$pressable_branding = get_option( 'remove_pressable_branding_tab_options' );

	// Check if options are set before processing.
	if ( isset( $hide_pressable_branding_tab_options['branding_on_off_radio_button'] ) && ! empty( $hide_pressable_branding_tab_options['branding_on_off_radio_button'] ) ) {
		$hide_pressable_branding_tab_options = sanitize_text_field( $hide_pressable_branding_tab_options['branding_on_off_radio_button'] );
	}

	// Set radio button state to default.
	if ( 'disable' === $hide_pressable_branding_tab_options ) {
		$hide_pressable_branding_tab_options = get_option( 'remove_pressable_branding_tab_options' );
	}
}
