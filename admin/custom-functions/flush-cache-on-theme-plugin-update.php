<?php
// Custom function - Flush cache automatically on themes and plugins update

if ( ! defined( 'ABSPATH' ) ) {
    exit();
}

$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['flush_cache_theme_plugin_checkbox'] ) && ! empty( $options['flush_cache_theme_plugin_checkbox'] ) ) {

    function pcm_plugins_themes_update_completed( $upgrader_object, $hook_extra ) {

        $type = isset( $hook_extra['type'] ) ? $hook_extra['type'] : '';

        if ( ! in_array( $type, array( 'plugin', 'theme' ), true ) ) {
            return;
        }

        wp_cache_flush();

        // ── Resolve the name of the updated item ────────────────────────────
        $name = '';

        // Multiple plugins updated at once
        if ( $type === 'plugin' && isset( $hook_extra['plugins'] ) && is_array( $hook_extra['plugins'] ) ) {
            $names = array();
            foreach ( $hook_extra['plugins'] as $plugin_file ) {
                $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file, false, false );
                if ( ! empty( $plugin_data['Name'] ) ) {
                    $names[] = $plugin_data['Name'];
                }
            }
            $name = ! empty( $names ) ? implode( ', ', $names ) : 'Unknown plugin';
        }

        // Single plugin
        if ( $type === 'plugin' && empty( $name ) ) {
            if ( isset( $hook_extra['plugin'] ) ) {
                $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $hook_extra['plugin'], false, false );
                $name = ! empty( $plugin_data['Name'] ) ? $plugin_data['Name'] : $hook_extra['plugin'];
            } elseif ( isset( $upgrader_object->skin->plugin_info['Name'] ) ) {
                $name = $upgrader_object->skin->plugin_info['Name'];
            } else {
                $name = 'Unknown plugin';
            }
        }

        // Theme
        if ( $type === 'theme' ) {
            if ( isset( $hook_extra['themes'] ) && is_array( $hook_extra['themes'] ) ) {
                $theme_names = array();
                foreach ( $hook_extra['themes'] as $stylesheet ) {
                    $theme = wp_get_theme( $stylesheet );
                    if ( $theme->exists() ) {
                        $theme_names[] = $theme->get('Name');
                    }
                }
                $name = ! empty( $theme_names ) ? implode( ', ', $theme_names ) : 'Unknown theme';
            } elseif ( isset( $hook_extra['theme'] ) ) {
                $theme = wp_get_theme( $hook_extra['theme'] );
                $name  = $theme->exists() ? $theme->get('Name') : $hook_extra['theme'];
            } elseif ( isset( $upgrader_object->skin->theme_info['Name'] ) ) {
                $name = $upgrader_object->skin->theme_info['Name'];
            } else {
                $name = 'Unknown theme';
            }
        }

        // ── Build timestamp with bold item name ──────────────────────────────
        $timestamp = gmdate( 'j M Y, g:ia' ) . ' UTC — <b>' . esc_html( $name ) . ' ' . esc_html( $type ) . ' was updated</b>';
        update_option( 'flush-cache-theme-plugin-time-stamp', $timestamp );
    }

    add_action( 'upgrader_process_complete', 'pcm_plugins_themes_update_completed', 10, 2 );
}
