<?php //Pressable Cache Management - Settings Callbacks

// Disable direct file access
if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

/*
 * Process all call-backs from the plugin forms
*/

// callback: object cache section section
function pressable_cache_management_callback_section_cache() {

	$remove_pressable_branding_tab_options = false;

	$remove_pressable_branding_tab_options = get_option( 'remove_pressable_branding_tab_options' );

	if ( $remove_pressable_branding_tab_options && 'disable' == $remove_pressable_branding_tab_options['branding_on_off_radio_button'] ) {

	} else {

		//Pressable branding logo
		echo '<div><img width="230" height="50" class="pressablecmlogo" src="' . plugin_dir_url( __FILE__ ) . '/assets/img/pressable-logo-primary.svg' . '" > </div>';
	}

	echo '<p>' . esc_html__( 'These settings enable you to manage the object cache.', 'pressable_cache_management' ) . '</p>';

}

function pressable_cache_management_callback_section_cdn() {

	echo '<p>' . esc_html__( 'These settings enable you to manage your site CDN.', 'pressable_cache_management' ) . '</p>';

}

function pressable_cdn_enable_api() {

	$api_tab = 'admin.php?page=pressable_cache_management&tab=pressable_api_authentication_tab';
	echo '<p>' . esc_html__( 'You must configure the API settings before you can manage the CDN. ', 'pressable_cache_management' ) . sprintf( '<a href="%s">%s</a>', $api_tab, esc_html__( 'Setup API Authentication ', 'pressable_cache_management' ) ) . '</p>';

}

// callback: API Authentication tab page description
function pressable_cache_management_callback_section_authentication() {

	$pcm_con_auth    = get_option( 'pressable_api_admin_notice__status' );
	$site_id_con_res = get_option( 'pcm_site_id_con_res' );

	if ( $site_id_con_res === 'OK' && $pcm_con_auth === 'activated' ) {

		echo '<p>' . esc_html__( 'Your website is now connected to the control panel &#128994;', 'pressable_cache_management' ) . '</p>';

	} else {

		$mpcp_url = 'https://my.pressable.com/api/applications';
		echo '<p>' . esc_html__( 'Connect your site to the hosting control panel  ', 'pressable_cache_management' ) . sprintf( '<a href="%s">%s</a>', $mpcp_url, esc_html__( 'Setup API Keys Here', 'pressable_cache_management' ) ) . '&#32;' . '&#128308;' . '</p>';

	}
}

// callback: Hide Pressable branding tab page description
function pressable_cache_management_callback_section_branding() {

	echo '<p>' . esc_html__( 'This setting allows you to show or hide the plugin branding.', 'pressable_cache_management' ) . '</p>';

}

/**
 ********************************
 * Object Cache                 *
 * Management Tab               *
 ********************************
 *
 */

// Flush object cache button
function pressable_cache_management_callback_field_button( $args ) {

	$options = get_option( 'pressable_cache_management_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	echo '</form>';

	echo '<form method="post" id="flush_object_cache_nonce">

         <span id="flush_cache_button">
        <input id="pressable_cache_management_options_' . $id . '" name="pressable_cache_management_options[' . $id . ']" type="submit" size="40" value="' . __( 'Flush Cache', 'pressable_cache_management' ) . '" class="flushcache"/><input type="hidden" name="flush_object_cache_nonce" value="' . wp_create_nonce( 'flush_object_cache_nonce' ) . '" <br/><label class="rad-text for="pressable_cache_management_options_' . $id . '">' . $label . '</label>
         </span>

    </form>';

	echo '</br>';
	//Display time stamp when object cache was last flushed
	echo '<small><strong>Last flushed at: </strong></small> ' . ( get_option( 'flush-obj-cache-time-stamp' ) );

}

// Extend batcache checkbox
function pressable_cache_management_callback_field_extend_cache_checkbox( $args ) {

	$options = get_option( 'pressable_cache_management_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	echo '<input type="checkbox" id="pressable_cache_management_options_' . $id . '" name="pressable_cache_management_options[' . $id . ']" value="1"' . $checked . ' />';
	echo '<span class="slider round"></span>
</label>';
	// echo '</br>';
	echo '<label class="rad-text for="pressable_cache_management_options_' . $id . '">' . $label . '</label>';

}

// Flush site cache on Theme/Plugin update checkbox
function pressable_cache_management_callback_field_plugin_theme_update_checkbox( $args ) {

	$options = get_option( 'pressable_cache_management_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	echo '<input type="checkbox" id="pressable_cache_management_options_' . $id . '" name="pressable_cache_management_options[' . $id . ']" value="1"' . $checked . ' />';
	echo '<span class="slider round"></span>
</label>';

	echo '<label class="rad-text for="pressable_cache_management_options_' . $id . '">' . $label . '</label>';
	echo '</br>';
	echo '</br>';
	//Display time stamp when object cache was last flushed when theme plugin
	echo '<small><strong>Last flushed at: </strong></small>' . ( get_option( 'flush-cache-theme-plugin-time-stamp' ) );

}

// Flush site object cache on page & post update checkbox
function pressable_cache_management_callback_field_page_edit_checkbox( $args ) {

	$options = get_option( 'pressable_cache_management_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	echo '<input type="checkbox" id="pressable_cache_management_options_' . $id . '" name="pressable_cache_management_options[' . $id . ']" value="1"' . $checked . ' />';
	echo '<span class="slider round"></span>
</label>';
	// echo '</br>';
	echo '<label class="rad-text for="pressable_cache_management_options_' . $id . '">' . $label . '</label>';
	echo '</br>';
	echo '</br>';
	//Display time stamp when object cache was last flushed when page or post was updated
	echo '<small><strong>Last flushed at: </strong></small>' . ( get_option( 'flush-cache-page-edit-time-stamp' ) );

}

//Flush cache for a single page
function pressable_cache_management_callback_field_flush_batcache_particular_page_checbox( $args ) {

	$options = get_option( 'pressable_cache_management_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	echo '<input type="checkbox" id="pressable_cache_management_options_' . $id . '" name="pressable_cache_management_options[' . $id . ']" value="1"' . $checked . ' />';
	echo '<span class="slider round"></span>
</label>';
	// echo '</br>';
	echo '<label class="rad-text for="pressable_cache_management_options_' . $id . '">' . $label . '</label>';
	echo '</br>';
	echo '</br>';
	//Display time stamp when object cache was last flushed
	echo '<small><strong>Last flushed at:</strong></small> ' . ( get_option( 'flush-object-cache-for-single-page-time-stamp' ) ) . '<small></small>';

}

//TODO: exempt pages
// // Callback: text field for exempt page from batcache
// function pressable_cache_management_callback_field_exempt_batcache_text($args)
// {
//     $options = get_option('pressable_cache_management_options');
//     $id = isset($args['id']) ? $args['id'] : '';
//     $label = isset($args['label']) ? $args['label'] : '';
//     $value = isset($options[$id]) ? sanitize_text_field($options[$id]) : '';
//     echo '<input autocomplete="off" id="pressable_cache_management_options_' . $id . '" name="pressable_cache_management_options[' . $id . ']" type="text" size="40" value="' . $value . '"><br/>';
//     echo '<label class="rad-text for="pressable_cache_management_options_' . $id . '">' . $label . '</label>';
// }
/*


/**
 ********************************
 * CDN Cache                    *
 * Management Tab               *
 ********************************
 **/

// Radio button options to turn on/off cdn
function pressable_cache_management_options_radio_button() {

	return array(

		'enable'  => esc_html__( 'Enable CDN (Recommended)', 'pressable_cache_management' ),
		'disable' => esc_html__( 'Disable CDN', 'pressable_cache_management' ),

	);

}

function pressable_cache_management_callback_field_extend_cdn_radio_button( $args ) {

	$options = get_option( 'cdn_settings_tab_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$selected_option = isset( $options[ $id ] ) ? sanitize_text_field( $options[ $id ] ) : '';

	$radio_options = pressable_cache_management_options_radio_button();

	foreach ( $radio_options as $value => $label ) {

		$checked = checked( $selected_option === $value, true, false );

		// $checked = 'checked';
		echo '<label class="rad-label">';
		echo '<input type="radio" class="rad-input" name="cdn_settings_tab_options[' . $id . ']" type="radio" value="' . $value . '"' . $checked . ' name="rad">';
		echo '<div class="rad-design"></div>';
		echo '<span class="rad-text">' . $label . '</span></label>';
		echo '</label>';

	}

}

// Purge the CDN cache
function pressable_cdn_cache_flush_management_callback_field_button( $args ) {

	$options = get_option( 'cdn_settings_tab_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	echo '</form>';

	echo ' <form method="post" id="purge_cache_nonce"> 

         <span id="purge_cdn_cache_button">

              <input id="cdn_settings_tab_options_' . $id . '" name="cdn_settings_tab_options[' . $id . ']" type="submit" size="40" value="' . __( 'Purge CDN Cache', 'pressable_cache_management' ) . '" class="purgecacahe"/><input type="hidden" name="purge_cache_nonce" value="' . wp_create_nonce( 'purge_cache_nonce' ) . '" <br/><label class="rad-text for="cdn_settings_tab_options_' . $id . '">' . $label . '</label>


         </span>

    </form>';
	echo '</br>';
	//Display time stamp when object cache was last flushed
	echo '<small><strong>Last purged at: </strong></small>' . ( get_option( 'cdn-cache-purge-time-stamp' ) );

}

//Extend the Pressable CDN cache
function pressable_cdn_cache_extender_callback_field_checkbox( $args ) {

	$options = get_option( 'cdn_settings_tab_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	echo '<input type="checkbox" id="cdn_settings_tab_options_' . $id . '" name="cdn_settings_tab_options[' . $id . ']" value="1"' . $checked . ' />';
	echo '<span class="slider round"></span>
</label>';
	// echo '</br>';
	echo '<label class="rad-text for="cdn_settings_tab_options_' . $id . '">' . $label . '</label>';

}

//Exlude all images and WEBP from CDN caching
function pressable_cache_management_callback_field_exclude_cdn_image_webp_checkbox( $args ) {

	$options = get_option( 'cdn_settings_tab_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	echo '<input type="checkbox" id="cdn_settings_tab_options_' . $id . '" name="cdn_settings_tab_options[' . $id . ']" value="1"' . $checked . ' />';
	echo '<span class="slider round"></span>
</label>';
	echo '<label class="rad-text for="cdn_settings_tab_options_' . $id . '">' . $label . '</label>';

}

//Exlude all .CSS files from CDN caching
function pressable_cache_management_callback_field_exclude_cdn_image_css_checkbox( $args ) {

	$options = get_option( 'cdn_settings_tab_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	echo '<input type="checkbox" id="cdn_settings_tab_options_' . $id . '" name="cdn_settings_tab_options[' . $id . ']" value="1"' . $checked . ' />';
	echo '<span class="slider round"></span>
</label>';
	echo '<label class="rad-text for="cdn_settings_tab_options_' . $id . '">' . $label . '</label>';

}

//Exlude all .json and .js from CDN caching
function pressable_cache_management_callback_field_exclude_json_checkbox( $args ) {

	$options = get_option( 'cdn_settings_tab_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	echo '<input type="checkbox" id="cdn_settings_tab_options_' . $id . '" name="cdn_settings_tab_options[' . $id . ']" value="1"' . $checked . ' />';
	echo '<span class="slider round"></span>
</label>';
	// echo '</br>';
	echo '<label class="rad-text for="cdn_settings_tab_options_' . $id . '">' . $label . '</label>';

}

// Callback: text field to exclude a particular file from CDN
function pressable_cache_management_callback_field_exclude_partucular_file_text( $args ) {

	$options = get_option( 'cdn_settings_tab_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$value = isset( $options[ $id ] ) ? sanitize_text_field( $options[ $id ] ) : '';

	echo '<input autocomplete="off" id="cdn_settings_tab_options_' . $id . '" name="cdn_settings_tab_options[' . $id . ']" type="text" placeholder="eg file_to_exclude.js" size="75" value="' . $value . '"><br/>';
	echo '<label class="rad-text for="cdn_settings_tab_options_' . $id . '">' . $label . '</label>';

}

/**
 ********************************
 * Authentication               *
 * Management Tab               *
 ********************************
 */

/**
 ********************************
 * Create Pressable site id options table and connections
 * table if it's not existing which is useful when a site
 * is migrated to Pressable
 ********************************
 */

$option_name = 'pressable_site_id';
$option_res  = 'pcm_site_id_con_res';
$options     = get_option( 'pressable_api_authentication_tab_options' );

//Add pressable site id table to DB if not exist for site which is migrated  to Pressable
if ( 'not-exists' === get_option( 'pressable_site_id', 'not-exists' ) ) {

	//Add the options table if they don't exisit
	add_option( 'pcm_site_id_con_res', 'Not Found' );
	add_option( 'pressable_site_id', '' );

} else {

	//If the site exist records the connection response as successful
	add_option( 'pcm_site_id_con_res', 'OK' );

}

/**
 **********************************************************
 * Update the site id with the one on the database if
 * it different from the one stored on the options
 * this prevents the site id on the clone from overwriting
 * the new site id in the database if a site is cloned
 **********************************************************
 */

if ( is_bool( $options ) && get_option( 'pressable_api_authentication_tab_options' ) == false ) {

	$db_site_id     = get_option( 'pressable_site_id' );
	$site_id_option = array(
		'pressable_site_id' => $db_site_id,

	);

	update_option( 'pressable_api_authentication_tab_options', $site_id_option );

} elseif ( $pagenow == 'admin.php' && $options['pressable_site_id'] !== get_option( $option_name ) ) {
	return;

} elseif ( $options['pressable_site_id'] && $options['pressable_site_id'] !== get_option( $option_name ) ) {

	$db_site_id     = get_option( 'pressable_site_id' );
	$site_id_option = array(
		'pressable_site_id' => $db_site_id,

	);

	update_option( 'pressable_api_authentication_tab_options', $site_id_option );
}

/**
 ***********************************************************
 * Check if the Pressable site id exist in the database.
 * CSS to hide site id field is located on style CSS while
 * the function to hide the field is on settings-register.php
 *************************************************************
 */

function pressable_cache_management_callback_field_site_id_text( $args ) {

	$options = get_option( 'pressable_api_authentication_tab_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	//     $value = isset($options[$id]) ? sanitize_text_field($options[$id]) : '';
	$value = isset( $options['pressable_site_id'] ) ? sanitize_text_field( update_option( 'pressable_site_id', $options['pressable_site_id'] ) ) : '';

	update_option( 'pressable_site_id', $value );
	//Get the site id from the db
	$value = get_option( 'pressable_site_id' );

	$site_id_con_res = get_option( 'pcm_site_id_con_res' );
	//$auth_tab = get_option('pressable_api_authentication_tab_options');
	//(!empty($options['pressable_site_id'])

	/**
	 ******************************************
	 * Conditions to check from the api result
	 * stored on the db if the connection is
	 * successful
	 ******************************************
	 */

	if ( $site_id_con_res === 'OK' ) {

		$options = get_option( 'pressable_api_authentication_tab_options' );

		$id    = isset( $args['id'] ) ? $args['id'] : '';
		$label = isset( $args['label'] ) ? $args['label'] : '';

		// $value = isset($options[$id]) ? sanitize_text_field($options[$id]) : '';
		//update the site ID on the DB with the value from the textbox
		$value = isset( $options['pressable_site_id'] ) ? sanitize_text_field( update_option( 'pressable_site_id', $options['pressable_site_id'] ) ) : '';

		$value = get_option( 'pressable_site_id' );

		echo '<input autocomplete="off" id="pressable_api_authentication_tab_options_' . $id . '" name="pressable_api_authentication_tab_options[' . $id . ']" type="text" size="40" value="' . $value . '"><br />';
		echo '<label class="rad-text for="pressable_api_authentication_tab_options_' . $id . '">' . $label . '</label>';

		//       update_option('pressable_site_id', $radio_option);

	} elseif ( $site_id_con_res === 'Not Found' ) {

		$options = get_option( 'pressable_api_authentication_tab_options' );

		$id    = isset( $args['id'] ) ? $args['id'] : '';
		$label = isset( $args['label'] ) ? $args['label'] : '';

		// $value = isset($options[$id]) ? sanitize_text_field($options[$id]) : '';
		//update the site ID on the DB with the value from the textbox
		$value = isset( $options[ $id ] ) ? sanitize_text_field( update_option( 'pressable_site_id', $options[ $id ] ) ) : '';

		$value = get_option( 'pressable_site_id' );

		echo '<input autocomplete="off" id="pressable_api_authentication_tab_options_' . $id . '" name="pressable_api_authentication_tab_options[' . $id . ']" type="text" size="40" value="' . $value . '"><br />';
		echo '<label class="rad-text for="pressable_api_authentication_tab_options_' . $id . '">' . $label . '</label>';

	} elseif ( $site_id_con_res === '' ) {

		$options = get_option( 'pressable_api_authentication_tab_options' );

		$id    = isset( $args['id'] ) ? $args['id'] : '';
		$label = isset( $args['label'] ) ? $args['label'] : '';

		// $value = isset($options[$id]) ? sanitize_text_field($options[$id]) : '';
		//update the site ID on the DB with the value from the textbox
		$value = isset( $options[ $id ] ) ? sanitize_text_field( update_option( 'pressable_site_id', $options[ $id ] ) ) : '';

		$value = get_option( 'pressable_site_id' );

	}

}

/**
 *****************************************
 * Check if authentication is successful
 * for API key and site ID then enable
 * CDN by defualt
 *****************************************
 */

$pcm_con_auth    = get_option( 'pressable_api_admin_notice__status' );
$site_id_con_res = get_option( 'pcm_site_id_con_res' );

if ( $site_id_con_res === 'OK' && $pcm_con_auth === 'activated' ) {

	//Auto enable CDN radio options if authentication is successful
	$radio_option = array(
		'cdn_on_off_radio_button'          => 'enable',
		'exclude_jpg_png_webp_from_cdn'    => '0',
		'exclude_particular_file_from_cdn' => '',
		'cdn_cache_extender'               => '0',
		'exclude_css_from_cdn'             => '0',

	);

	//Update the option table with new enabled value
	add_option( 'cdn_settings_tab_options', $radio_option );
}

//include css stle to hide site id when added
add_action( 'init', 'pcm_register_css_style' );

function pcm_register_css_style() {

	// enque css script
	wp_enqueue_style( 'pressable_cache_management', plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/style.css', array(), false, 'screen' );

}

// Callback: text field for Pressable API Client ID
function pressable_cache_management_callback_field_id_text( $args ) {

	$options = get_option( 'pressable_api_authentication_tab_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	//     $value = isset($options[$id]) ? sanitize_text_field($options[$id]) : '';
	$value = isset( $options[ $id ] ) ? sanitize_text_field( update_option( 'pcm_client_id', $options[ $id ] ) ) : '';

	$pcm_con_auth    = get_option( 'pressable_api_admin_notice__status' );
	$site_id_con_res = get_option( 'pcm_site_id_con_res' );

	$value = get_option( 'pcm_client_id' );

	/*
	 * Conceal api client client ID to show only the first five characters.
	 *
	 * The rest should be replaced with "&#9679;", a Unicode black circle.
	 *
	 * Concealed keys will look something like: "XYZ●●●●●●●●●●●●●●●●●●●●●●●".
	*/

	if ( $site_id_con_res === 'OK' && $pcm_con_auth === 'activated' ) {

		//Conceal client id credentials
		$value = str_pad( substr( $value, 0, 5 ), 3 + 4 * ( strlen( $value ) - 4 ), '&#9679;', STR_PAD_RIGHT );

		echo '<input autocomplete="off" id="pressable_api_authentication_tab_options_' . $id . '" name="pressable_api_authentication_tab_options[' . $id . ']" type="text" size="40" value="' . $value . '"><br/>';
		echo '<label class="rad-text for="pressable_api_authentication_tab_options_' . $id . '">' . $label . '</label>';

	} else {
		echo '<input autocomplete="off" id="pressable_api_authentication_tab_options_' . $id . '" name="pressable_api_authentication_tab_options[' . $id . ']" type="text" size="40" value="' . $value . '"><br/>';
		echo '<label class="rad-text for="pressable_api_authentication_tab_options_' . $id . '">' . $label . '</label>';

	}
}
// Callback: text field for Pressable API Secret ID
function pressable_cache_management_callback_field_secret_text( $args ) {

	$options = get_option( 'pressable_api_authentication_tab_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	//     $value = isset($options[$id]) ? sanitize_text_field($options[$id]) : '';
	$value = isset( $options[ $id ] ) ? sanitize_text_field( update_option( 'pcm_client_secret', $options[ $id ] ) ) : '';

	$pcm_con_auth    = get_option( 'pressable_api_admin_notice__status' );
	$site_id_con_res = get_option( 'pcm_site_id_con_res' );

	$value = get_option( 'pcm_client_secret' );

	/*
	 * Mask api client client secret to show only the first five characters.
	 *
	 * The rest should be replaced with "&#9679;", a Unicode black circle.
	 *
	 * Concealed keys will look something like: "XYZ●●●●●●●●●●●●●●●●●●●●●●●".
	*/

	if ( $site_id_con_res === 'OK' && $pcm_con_auth === 'activated' ) {

		//Conceal client id credentials
		$value = str_pad( substr( $value, 0, 5 ), 3 + 4 * ( strlen( $value ) - 4 ), '&#9679;', STR_PAD_RIGHT );

		echo '<input autocomplete="off" id="pressable_api_authentication_tab_options_' . $id . '" name="pressable_api_authentication_tab_options[' . $id . ']" type="text" size="40" value="' . $value . '"><br/>';
		echo '<label class="rad-text for="pressable_api_authentication_tab_options_' . $id . '">' . $label . '</label>';

	} else {
		echo '<input autocomplete="off" id="pressable_api_authentication_tab_options_' . $id . '" name="pressable_api_authentication_tab_options[' . $id . ']" type="text" size="40" value="' . $value . '"><br/>';
		echo '<label class="rad-text for="pressable_api_authentication_tab_options_' . $id . '">' . $label . '</label>';

	}
}

/**
 ********************************
 * Remove Pressable             *
 * Branding Tab                 *
 ********************************
 *
 */

// Radio button options
function pressable_cache_management_options_remove_branding_radio_button() {

	return array(

		'enable'  => esc_html__( 'Show Pressable Branding', 'pressable_cache_management' ),
		'disable' => esc_html__( 'Hide Pressable Branding', 'pressable_cache_management' ),

	);

}

function pressable_cache_management_callback_field_extend_remove_branding_radio_button( $args ) {

	$options = get_option( 'remove_pressable_branding_tab_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$selected_option = isset( $options[ $id ] ) ? sanitize_text_field( $options[ $id ] ) : '';

	$radio_options = pressable_cache_management_options_remove_branding_radio_button();

	foreach ( $radio_options as $value => $label ) {

		$checked = checked( $selected_option === $value, true, false );

		echo '<label class="rad-label">';
		echo '<input type="radio" class="rad-input" name="remove_pressable_branding_tab_options[' . $id . ']" type="radio" value="' . $value . '"' . $checked . ' name="rad">';
		echo '<div class="rad-design"></div>';
		echo '<span class="rad-text">' . $label . '</span></label>';
		echo '</label>';

	}

}
