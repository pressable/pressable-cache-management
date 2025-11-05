<?php
/**
 * Pressable Cache Management - Settings Callbacks.
 *
 * @package Pressable_Cache_Management
 */

// Disable direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**********************************************
 * Process all call-backs from the plugin forms
 **********************************************/

/**
 * Callback: object cache section section.
 */
function pressable_cache_management_callback_section_cache() {

	$remove_pressable_branding_tab_options = false;

	$remove_pressable_branding_tab_options = get_option( 'remove_pressable_branding_tab_options' );

	if ( ! $remove_pressable_branding_tab_options || 'disable' !== $remove_pressable_branding_tab_options['branding_on_off_radio_button'] ) {
		// Pressable branding logo.
		printf(
			'<div><img width="230" height="50" class="pressablecmlogo" src="%s" > </div>',
			esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/pressable-logo-primary.svg' )
		);
	}

	echo '<p>' . esc_html__( 'These settings enable you to manage the object cache.', 'pressable_cache_management' ) . '</p>';

	// Check if the site uses Cloudflare.
	$response = wp_remote_get( get_site_url(), array( 'timeout' => 120 ) );
	$headers  = wp_remote_retrieve_headers( $response );

	if ( isset( $headers['server'] ) && stripos( $headers['server'], 'cloudflare' ) !== false ) {
		// Cloudflare is present in the website header.

		// Check Batcache status.
		$site_url = get_site_url();
		$response = wp_remote_get( $site_url, array( 'timeout' => 120 ) );

		if ( is_wp_error( $response ) || strpos( $response['body'], 'batcache' ) === false ) {
			echo '<p style="text-align:right; font-weight:bold">Batcache Status: Broken &#128308;</p>';
			echo '<p style="text-align:right; font-size: smaller;">Disable Cloudflare proxy and caching and try again &#x1F7E0</p></br>';
		} else {
			echo '<p style="text-align:right; font-weight:bold">Batcache Status: OK &#x1F7E2;</p>';
		}
	} else {
		// Cloudflare is not present in the website header.
		// Check for Batcache.
		$site_url = get_site_url();
		$response = wp_remote_get( $site_url, array( 'timeout' => 120 ) );

		if ( is_wp_error( $response ) || strpos( $response['body'], 'batcache' ) === false ) {
			echo '<p style="text-align:right; font-weight:bold">Batcache Status: Broken &#128308;</p></br>';
		} else {
			echo '<p style="text-align:right; font-weight:bold">Batcache Status: OK &#x1F7E2;</p>';
		}
	}
}

/**
 * RESTORED: This function is required by admin/settings-validate.php but was accidentally removed during cleanup.
 *
 * @return array
 */
function pressable_cache_management_options_radio_button() {

	return array(

		'enable'  => esc_html__( 'Enable CDN (Recommended)', 'pressable_cache_management' ),
		'disable' => esc_html__( 'Disable CDN', 'pressable_cache_management' ),

	);
}

// Removed redundant code related to CDN and old API flags.
$pcm_con_auth = 'pressable_api_admin_notice__status';

// If the option already exists, update it to "OK".
if ( get_option( $pcm_con_auth ) !== false ) {
	update_option( $pcm_con_auth, 'OK' );
} else {
	add_option( $pcm_con_auth, 'OK' );
}

/**
 * Callback: Edge cache section description.
 */
function pressable_cache_management_callback_section_edge_cache() {

	echo '<p>' . esc_html__( 'These settings enables you to manage Edge Cache settings.', 'pressable_cache_management' ) . '</p>';
}

/**
 * Callback: Hide Pressable branding tab page description.
 */
function pressable_cache_management_callback_section_branding() {

	echo '<p>' . esc_html__( 'This setting allows you to show or hide the plugin branding.', 'pressable_cache_management' ) . '</p>';
}

/**
 * *******************************
 * Object Cache                 *
 * Management Tab               *
 * *******************************
 */

/**
 * Flush object cache button.
 *
 * @param array $args Arguments passed by the settings API.
 */
function pressable_cache_management_callback_field_button( $args ) {

	$options = get_option( 'pressable_cache_management_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	echo '</form>';

	echo '<form method="post" id="flush_object_cache_nonce">
		 <span id="flush_cache_button">';

	printf(
		'<input id="pressable_cache_management_options_%1$s" name="pressable_cache_management_options[%1$s]" type="submit" size="40" value="%2$s" class="flushcache"/>' .
		'<input type="hidden" name="flush_object_cache_nonce" value="%3$s" />' .
		'<br/><label class="rad-text" for="pressable_cache_management_options_%1$s">%4$s</label>',
		esc_attr( $id ),
		esc_attr__( 'Flush Cache', 'pressable_cache_management' ),
		esc_attr( wp_create_nonce( 'flush_object_cache_nonce' ) ),
		esc_html( $label )
	);

	echo '</span>
	</form>';

	echo '</br>';
	// Display time stamp when object cache was last flushed.
	echo '<small><strong>' . esc_html__( 'Last flushed at:', 'pressable_cache_management' ) . ' </strong></small> ' . esc_html( get_option( 'flush-obj-cache-time-stamp' ) );
}


/**
 * Extend batcache checkbox.
 *
 * @param array $args Arguments passed by the settings API.
 */
function pressable_cache_management_callback_field_extend_cache_checkbox( $args ) {

	$options = get_option( 'pressable_cache_management_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	printf(
		'<input type="checkbox" id="pressable_cache_management_options_%1$s" name="pressable_cache_management_options[%1$s]" value="1" %2$s />',
		esc_attr( $id ),
		$checked // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	echo '<span class="slider round"></span>
</label>';
	printf(
		'<label class="rad-text" for="pressable_cache_management_options_%1$s">%2$s</label>',
		esc_attr( $id ),
		esc_html( $label )
	);
}

/**
 * Flush site cache on Theme/Plugin update checkbox.
 *
 * @param array $args Arguments passed by the settings API.
 */
function pressable_cache_management_callback_field_plugin_theme_update_checkbox( $args ) {

	$options = get_option( 'pressable_cache_management_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	printf(
		'<input type="checkbox" id="pressable_cache_management_options_%1$s" name="pressable_cache_management_options[%1$s]" value="1" %2$s />',
		esc_attr( $id ),
		$checked // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	echo '<span class="slider round"></span>
</label>';

	printf(
		'<label class="rad-text" for="pressable_cache_management_options_%1$s">%2$s</label>',
		esc_attr( $id ),
		esc_html( $label )
	);
	echo '</br>';
	echo '</br>';
	// Display time stamp when object cache was last flushed when theme plugin.
	echo '<small><strong>' . esc_html__( 'Last flushed at:', 'pressable_cache_management' ) . ' </strong></small>' . esc_html( get_option( 'flush-cache-theme-plugin-time-stamp' ) );
}

/**
 * Flush site object cache on page & post update checkbox.
 *
 * @param array $args Arguments passed by the settings API.
 */
function pressable_cache_management_callback_field_page_edit_checkbox( $args ) {

	$options = get_option( 'pressable_cache_management_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	printf(
		'<input type="checkbox" id="pressable_cache_management_options_%1$s" name="pressable_cache_management_options[%1$s]" value="1" %2$s />',
		esc_attr( $id ),
		$checked // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	echo '<span class="slider round"></span>
</label>';
	printf(
		'<label class="rad-text" for="pressable_cache_management_options_%1$s">%2$s</label>',
		esc_attr( $id ),
		esc_html( $label )
	);
	echo '</br>';
	echo '</br>';
	// Display time stamp when object cache was last flushed when page or post was updated.
	echo '<small><strong>' . esc_html__( 'Last flushed at:', 'pressable_cache_management' ) . ' </strong></small>' . esc_html( get_option( 'flush-cache-page-edit-time-stamp' ) );
}


/**
 * Flush site object cache when page, post and posttypes are updated checkbox.
 *
 * @param array $args Arguments passed by the settings API.
 */
function pressable_cache_management_callback_field_page_post_delete_checkbox( $args ) {

	$options = get_option( 'pressable_cache_management_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	printf(
		'<input type="checkbox" id="pressable_cache_management_options_%1$s" name="pressable_cache_management_options[%1$s]" value="1" %2$s />',
		esc_attr( $id ),
		$checked // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	echo '<span class="slider round"></span>
</label>';
	printf(
		'<label class="rad-text" for="pressable_cache_management_options_%1$s">%2$s</label>',
		esc_attr( $id ),
		esc_html( $label )
	);
	echo '</br>';
	echo '</br>';
	// Display time stamp when object cache was last flushed when page or post was deleted.
	echo '<small><strong>' . esc_html__( 'Last flushed at:', 'pressable_cache_management' ) . ' </strong></small>' . esc_html( get_option( 'flush-cache-on-page-post-delete-time-stamp' ) );
}


/**
 * Flush cache when comment is deleted checkbox.
 *
 * @param array $args Arguments passed by the settings API.
 */
function pressable_cache_management_callback_field_comment_delete_checkbox( $args ) {

	$options = get_option( 'pressable_cache_management_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	printf(
		'<input type="checkbox" id="pressable_cache_management_options_%1$s" name="pressable_cache_management_options[%1$s]" value="1" %2$s />',
		esc_attr( $id ),
		$checked // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	echo '<span class="slider round"></span>
</label>';
	printf(
		'<label class="rad-text" for="pressable_cache_management_options_%1$s">%2$s</label>',
		esc_attr( $id ),
		esc_html( $label )
	);
	echo '</br>';
	echo '</br>';
	// Display time stamp when object cache was last flushed when comment was deleted.
	echo '<small><strong>' . esc_html__( 'Last flushed at:', 'pressable_cache_management' ) . ' </strong></small>' . esc_html( get_option( 'flush-cache-on-comment-delete-time-stamp' ) );
}


/**
 * Flush cache for a single page.
 *
 * @param array $args Arguments passed by the settings API.
 */
function pressable_cache_management_callback_field_flush_batcache_particular_page_checbox( $args ) {

	$options = get_option( 'pressable_cache_management_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	printf(
		'<input type="checkbox" id="pressable_cache_management_options_%1$s" name="pressable_cache_management_options[%1$s]" value="1" %2$s />',
		esc_attr( $id ),
		$checked // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	echo '<span class="slider round"></span>
</label>';
	printf(
		'<label class="rad-text" for="pressable_cache_management_options_%1$s">%2$s</label>',
		esc_attr( $id ),
		esc_html( $label )
	);
	echo '</br>';
	echo '</br>';
	// Display time stamp when object cache was last flushed.
	echo '<small><strong>' . esc_html__( 'Last flushed at:', 'pressable_cache_management' ) . '</strong></small> ' . esc_html( get_option( 'flush-object-cache-for-single-page-time-stamp' ) ) . '<small></small>';
	echo '</br>';
	echo '<small><strong>' . esc_html__( 'Page URL:', 'pressable_cache_management' ) . '</strong></small> ' . esc_html( get_option( 'single-page-url-flushed' ) ) . '<small></small>';
}




/**
 * Flush cache for WooCommerce product single page.
 *
 * @param array $args Arguments passed by the settings API.
 */
function pressable_cache_management_callback_field_flush_batcache_woo_product_page_checbox( $args ) {

	$options = get_option( 'pressable_cache_management_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$checked = isset( $options[ $id ] ) ? checked( $options[ $id ], 1, false ) : '';

	echo '<div class="container">';
	echo '<label class="switch">';
	printf(
		'<input type="checkbox" id="pressable_cache_management_options_%1$s" name="pressable_cache_management_options[%1$s]" value="1" %2$s />',
		esc_attr( $id ),
		$checked // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);
	echo '<span class="slider round"></span>
</label>';
	printf(
		'<label class="rad-text" for="pressable_cache_management_options_%1$s">%2$s</label>',
		esc_attr( $id ),
		esc_html( $label )
	);
	echo '</br>';
}


/**
 * Callback: text field to exempt individual page from batcache.
 *
 * @param array $args Arguments passed by the settings API.
 */
function pressable_cache_management_callback_field_exempt_batcache_text( $args ) {
	$options = get_option( 'pressable_cache_management_options' );
	$id      = isset( $args['id'] ) ? $args['id'] : '';
	$label   = isset( $args['label'] ) ? $args['label'] : '';
	$value   = isset( $options[ $id ] ) ? sanitize_text_field( $options[ $id ] ) : '';

	printf(
		'<input autocomplete="off" id="pressable_cache_management_options_%1$s" name="pressable_cache_management_options[%1$s]" type="text" placeholder="%2$s" size="70" value="%3$s"><br/>',
		esc_attr( $id ),
		esc_attr__( 'Exclude single page ex  /pagename/', 'pressable_cache_management' ),
		esc_attr( $value )
	);
	printf(
		'<label class="rad-text" for="pressable_cache_management_options_%1$s">%2$s</label>',
		esc_attr( $id ),
		esc_html( $label )
	);
}

/**
 * *******************************
 * Edge Cache                    *
 * Management Tab               *
 * *******************************
 */


/**
 * Radio button options to turn on/off Edge Cache.
 *
 * @return array
 */
function pressable_cache_management_options_radio_edge_cache_button() {

	return array(
		'enable' => esc_html__( 'Enable Edge Cache', 'pressable_cache_management' ),
	);
}

/**
 * AJAX handler function to check Edge Cache status on demand.
 */
function pcm_ajax_check_edge_cache_status() {
	// Nonce is not strictly needed for a GET/read operation, but good practice if it were part of a larger form.
	// We check user capability only.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
		return;
	}

	if ( class_exists( 'Edge_Cache_Plugin' ) ) {
		$edge_cache = Edge_Cache_Plugin::get_instance();

		// This is the authoritative check against the server.
		$server_status = $edge_cache->get_ec_status();

		$is_enabled  = false;
		$status_text = 'Unknown';
		$status_flag = 'Error';

		// Translate server status to client-side flags and descriptive text.
		if ( Edge_Cache_Plugin::EC_ENABLED === $server_status ) {
			$is_enabled  = true;
			$status_text = 'Enabled';
			$status_flag = 'Success';
		} elseif ( Edge_Cache_Plugin::EC_DISABLED === $server_status ) {
			$is_enabled  = false;
			$status_text = 'Disabled';
			$status_flag = 'Success';
		} elseif ( Edge_Cache_Plugin::EC_DDOS === $server_status ) {
			$status_text = 'Defensive Mode (DDoS)';
			$status_flag = 'Warning';
		}

		// We update the options here as well, so subsequent page loads/direct hits are faster.
		update_option( 'edge-cache-status', $status_flag );
		update_option( 'edge-cache-enabled', $is_enabled ? 'enabled' : 'disabled' );

		wp_send_json_success(
			array(
				'enabled'                      => $is_enabled,
				'status_text'                  => $status_text,
				'status_flag'                  => $status_flag,
				// Pass the HTML for the Enable/Disable control.
				'html_controls_enable_disable' => pressable_cache_management_generate_enable_disable_content( $is_enabled ),
			)
		);
	} else {
		wp_send_json_error(
			array(
				'message'                      => 'Edge Cache dependency is not available.',
				'html_controls_enable_disable' => '<p class="notice notice-error" style="padding: 10px;">' . esc_html__( 'Error: Edge Cache dependency is missing.', 'pressable_cache_management' ) . '</p>',
			)
		);
	}
}
add_action( 'wp_ajax_pcm_check_edge_cache_status', 'pcm_ajax_check_edge_cache_status' );


/**
 * Helper function to generate only the Enable/Disable button form HTML.
 *
 * @param bool $is_enabled Whether the Edge Cache is currently enabled.
 * @return string The HTML containing only the Enable/Disable control.
 */
function pressable_cache_management_generate_enable_disable_content( $is_enabled ) {
	ob_start();

	// Button form/label.

	if ( $is_enabled ) {
		$id           = 'disable_edge_cache_nonce';
		$value        = __( 'Disable Edge Cache', 'pressable_cache_management' );
		$submit_class = 'purgecacahe';

		echo '</form>';
		echo '<form method="post" id="' . esc_attr( $id ) . '">';
		echo '<span id="' . esc_attr( $id ) . '">';
		echo '<input id="edge_cache_settings_tab_options_disable" name="edge_cache_settings_tab_options[edge_cache_on_off_radio_button]" type="submit" size="40" value="' . esc_attr( $value ) . '" class="' . esc_attr( $submit_class ) . '"/>';
		printf(
			'<input type="hidden" name="%1$s" value="%2$s" />',
			esc_attr( $id ),
			esc_attr( wp_create_nonce( $id ) )
		);
		echo '</span>';
		echo '</form>';
	} else {
		$id           = 'enable_edge_cache_nonce';
		$value        = __( 'Enable Edge Cache', 'pressable_cache_management' );
		$submit_class = 'purgecacahe';

		echo '</form>';
		echo '<form method="post" id="' . esc_attr( $id ) . '">';
		echo '<span id="' . esc_attr( $id ) . '">';
		echo '<input id="edge_cache_settings_tab_options_enable" name="edge_cache_settings_tab_options[edge_cache_on_off_radio_button]" type="submit" size="40" value="' . esc_attr( $value ) . '" class="' . esc_attr( $submit_class ) . '"/>';
		printf(
			'<input type="hidden" name="%1$s" value="%2$s" />',
			esc_attr( $id ),
			esc_attr( wp_create_nonce( $id ) )
		);
		echo '</span>';
		echo '</form>';
	}

	return ob_get_clean();
}

/**
 * Renders the placeholder div and the synchronization script.
 *
 * @param array $args Arguments passed by the settings API (unused).
 */
function pressable_cache_management_callback_field_extend_edge_cache_radio_button( $args ) {
	// Renders the single placeholder div and the synchronization script.
	?>
	<style>
		.edge-cache-loader {
			display: flex;
			align-items: center;
			height: 30px;
			font-style: italic;
			color: #777;
		}
		.edge-cache-loader::before {
			content: '';
			border: 4px solid #f3f3f3;
			border-top: 4px solid #3498db;
			border-radius: 50%;
			width: 14px;
			height: 14px;
			animation: spin 1s linear infinite;
			margin-right: 10px;
		}
		@keyframes spin {
			0% { transform: rotate(0deg); }
			100% { transform: rotate(360deg); }
		}
		/* Style for grayed-out button. */
		.disabled-button-style {
			opacity: 0.6;
			cursor: not-allowed;
			pointer-events: none; /* Prevents clicks on disabled elements. */
		}
		.disabled-button-style:hover {
			opacity: 0.6 !important;
			box-shadow: none !important;
		}
	</style>
	<div id="edge-cache-control-wrapper" style="min-height: 30px;">
		<div class="edge-cache-loader"><?php esc_html_e( 'Checking Edge Cache status...', 'pressable_cache_management' ); ?></div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		var wrapper = $('#edge-cache-control-wrapper');
		var purgeButton = $('#purge-edge-cache-button-input');

		// This check ensures AJAX only fires if the element exists and hasn't already been checked.
		if (wrapper.length && !wrapper.data('status-checked')) {
			wrapper.data('status-checked', true); 
			
			// --- Set initial state for Purge button (Always disabled/grayed out before AJAX response) ---
			purgeButton.prop('disabled', true).addClass('disabled-button-style');

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'pcm_check_edge_cache_status'
				},
				success: function(response) {
					if (response.success && response.data.html_controls_enable_disable) {
						
						// 1. Update Enable/Disable content.
						wrapper.html(response.data.html_controls_enable_disable);
						
						// 2. Update Purge Button state (Gray out/Enable).
						if (response.data.enabled) {
							purgeButton.prop('disabled', false).removeClass('disabled-button-style');
						} else {
							// Keep grayed out.
							purgeButton.prop('disabled', true).addClass('disabled-button-style');
						}
						
					} else {
						// Handle failure.
						var errorMessage = response.data && response.data.message ? response.data.message : 'Failed to retrieve Edge Cache status.';
						wrapper.html('<p class="notice notice-error" style="padding: 10px;">' + errorMessage + '</p>');
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					// Handle AJAX network/server error.
					var errorMessage = 'AJAX Error: Could not connect to the status server.';
					wrapper.html('<p class="notice notice-error" style="padding: 10px;">' + errorMessage + '</p>');
				}
			});
		}
	});
	</script>
	<?php
}

/**
 * Purge Edge Cache.
 *
 * @param array $args Arguments passed by the settings API.
 */
function pressable_edge_cache_flush_management_callback_field_button( $args ) {
	$options = get_option( 'edge_cache_settings_tab_options' );

	$id = isset( $args['id'] ) ? $args['id'] : '';

	// Disabled attribute and class for initial state.
	$disabled_attr  = ' disabled="disabled"';
	$disabled_class = ' disabled-button-style';
	$submit_class   = 'purgecacahe' . $disabled_class;

	echo '</form>';

	echo '<form method="post" id="purge_edge_cache_nonce_form_static"> 
		 <span id="purge_edge_cache_button_span_static">';

	printf(
		'<input id="purge-edge-cache-button-input" 
					 name="edge_cache_settings_tab_options[%1$s]" 
					 type="submit" 
					 size="40" 
					 value="Purge Edge Cache" 
					 class="%2$s" %3$s/>',
		esc_attr( $id ),
		esc_attr( $submit_class ),
		$disabled_attr // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	);

	printf(
		'<input type="hidden" name="purge_edge_cache_nonce" value="%s" />',
		esc_attr( wp_create_nonce( 'purge_edge_cache_nonce' ) )
	);

	echo '</span>
	</form>';
	echo '<br/>';
	// Display timestamp when object cache was last flushed.
	echo '<small><strong>' . esc_html__( 'Last purged at:', 'pressable_cache_management' ) . ' </strong></small>' . esc_html( get_option( 'edge-cache-purge-time-stamp' ) );
	echo '<br/>';
	echo '<br/>';
	echo '<small><strong>' . esc_html__( 'Single URL last purged at:', 'pressable_cache_management' ) . '</strong></small> ' . esc_html( get_option( 'single-page-edge-cache-purge-time-stamp' ) ) . '<small></small>';
	echo '<br/>';
	echo '<small><strong>' . esc_html__( 'Single URL:', 'pressable_cache_management' ) . '</strong></small> ' . esc_html( get_option( 'edge-cache-single-page-url-purged' ) ) . '<small></small>';
	echo '<br/>';
	echo '<br/>';
	echo '<p style="font-size:12px;">'
	. esc_html( __( 'Purging cache will temporarily slow down your site for all visitors while the cache rebuilds.', 'pressable_cache_management' ) ) . '</p>';
}


/**
 * *******************************
 * Remove Pressable             *
 * Branding Tab                 *
 * *******************************
 */

/**
 * Radio button options.
 *
 * @return array
 */
function pressable_cache_management_options_remove_branding_radio_button() {
	return array(
		'enable'  => esc_html__( 'Show Pressable Branding', 'pressable_cache_management' ),
		'disable' => esc_html__( 'Hide Pressable Branding', 'pressable_cache_management' ),
	);
}

/**
 * Callback for remove branding radio buttons.
 *
 * @param array $args Arguments passed by the settings API.
 */
function pressable_cache_management_callback_field_extend_remove_branding_radio_button( $args ) {

	$options = get_option( 'remove_pressable_branding_tab_options' );

	$id    = isset( $args['id'] ) ? $args['id'] : '';
	$label = isset( $args['label'] ) ? $args['label'] : '';

	$selected_option = isset( $options[ $id ] ) ? sanitize_text_field( $options[ $id ] ) : '';

	$radio_options = pressable_cache_management_options_remove_branding_radio_button();

	foreach ( $radio_options as $value => $label ) {

		$checked = checked( $selected_option === $value, true, false );

		echo '<label class="rad-label">';
		printf(
			'<input type="radio" class="rad-input" name="remove_pressable_branding_tab_options[%1$s]" value="%2$s" %3$s>',
			esc_attr( $id ),
			esc_attr( $value ),
			$checked // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		);
		echo '<div class="rad-design"></div>';
		printf( '<span class="rad-text">%s</span></label>', esc_html( $label ) );
		echo '</label>';

	}
}