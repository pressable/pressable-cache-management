<?php // Custom function - Flush cache automatically on themes and plugins update

// Disable direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

// call option from checkbox to see if an option is selected
$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['flush_cache_theme_plugin_checkbox'] ) && ! empty( $options['flush_cache_theme_plugin_checkbox'] ) ) {
	function pcm_plugins_themes_update_completed( $upgrader_object, $options ) {
		if ( isset( $options['type'] ) && ( $options['type'] === 'plugin' || $options['type'] === 'theme' ) ) {
			wp_cache_flush();
		}

		// Set the object cache flush time and format the output
		$object_cache_flush_time = date( ' jS F Y  g:ia' ) . ' UTC — <b>';

		// Check for plugin/theme name
		if ( isset( $options['name'] ) ) {
			$object_cache_flush_time .= $options['name'];
		} elseif ( $options['type'] === 'plugin' && isset( $upgrader_object->skin->plugin_info['Name'] ) ) {
				$object_cache_flush_time .= $upgrader_object->skin->plugin_info['Name'];
		} elseif ( $options['type'] === 'theme' && isset( $upgrader_object->skin->theme_info['Name'] ) ) {
			$object_cache_flush_time .= $upgrader_object->skin->theme_info['Name'];
		} else {
			$object_cache_flush_time .= 'Unknown';
		}

		$object_cache_flush_time .= ' ' . $options['type'] . ' was updated</b>';
		update_option( 'flush-cache-theme-plugin-time-stamp', $object_cache_flush_time );
	}

	// Handle multiple plugin/theme updates
	if ( isset( $options['type'] ) && is_array( $options['type'] ) && count( $options['type'] ) > 1 ) {
		$object_cache_flush_time = '<b>Multiple ' . htmlspecialchars( $options['type'][0] ) . 's were updated</b>';
		update_option( 'flush-cache-theme-plugin-time-stamp', $object_cache_flush_time );
	}

	// Hook into upgrader process completion
	add_action( 'upgrader_process_complete', 'pcm_plugins_themes_update_completed', 10, 2 );
}
