<?php //Pressable Cache Management - Custom function to turn on/off Pressable branding

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/******************************
 * Show branding Option
 *******************************/

$hide_pressable_branding_tab_options = get_option( 'remove_pressable_branding_tab_options' );

// Safely extract the branding radio value
$branding_state = '';
if ( is_array( $hide_pressable_branding_tab_options ) && isset( $hide_pressable_branding_tab_options['branding_on_off_radio_button'] ) ) {
    $branding_state = sanitize_text_field( $hide_pressable_branding_tab_options['branding_on_off_radio_button'] );
}

// 'enable' = show branding (default), 'disable' = hide branding
// Currently no runtime logic is needed for either state — the value is consumed
// by admin-menu.php, settings-register.php, and other files via get_option().

