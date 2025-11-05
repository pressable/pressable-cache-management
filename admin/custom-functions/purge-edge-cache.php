<?php
/**
 * Pressable Edge Cache Purge Functionality (Dashboard Control)
 *
 * Handles purge requests directly from the WordPress dashboard.
 * - Checks the Edge Cache status first
 * - Displays admin notices for all outcomes
 *
 * @package Pressable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Edge Cache Purge Logic
 */
if ( isset( $_POST['purge_edge_cache_nonce'] ) ) {

	if ( ! function_exists( 'pcm_pressable_edge_cache_purge_local' ) ) {
		/**
		 * Purge the Edge Cache.
		 */
		function pcm_pressable_edge_cache_purge_local() {
			// 1. Verify security nonce and permission.
			if (
				! isset( $_POST['purge_edge_cache_nonce'] ) ||
				! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['purge_edge_cache_nonce'] ) ), 'purge_edge_cache_nonce' ) ||
				! current_user_can( 'manage_options' )
			) {
				return;
			}

			// 2. Ensure Edge Cache Plugin exists.
			if ( ! class_exists( 'Edge_Cache_Plugin' ) ) {
				add_action(
					'admin_notices',
					function () {
						printf(
							'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
							esc_html__( 'Error: Edge Cache Plugin is not active.', 'pressable_cache_management' )
						);
					}
				);
				return;
			}

			// 3. Get Edge Cache instance and current status.
			$edge_cache = Edge_Cache_Plugin::get_instance();

			$status_method = method_exists( $edge_cache, 'get_ec_status' ) ? 'get_ec_status' : null;
			$enable_method = method_exists( $edge_cache, 'enable_ec' ) ? 'enable_ec' : null;

			$server_status = $status_method ? $edge_cache->$status_method() : null;
			$auto_enabled  = false;

			// 4. If disabled, handle based on availability of enable_ec().
			if ( Edge_Cache_Plugin::EC_DISABLED === $server_status ) {
				if ( null !== $enable_method ) {
					// Try to enable Edge Cache automatically.
					$enabled = $edge_cache->$enable_method();
					if ( $enabled ) {
						$auto_enabled = true;
						sleep( 2 ); // allow enable to take effect.
					} else {
						// Could not enable Edge Cache.
						add_action(
							'admin_notices',
							function () {
								printf(
									'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
									esc_html__( 'Edge Cache was disabled and could not be auto-enabled. Purge aborted.', 'pressable_cache_management' )
								);
							}
						);
						return;
					}
				} else {
					// Edge Cache cannot be enabled automatically — stop purge and show notice.
					add_action(
						'admin_notices',
						function () {
							printf(
								'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
								esc_html__( 'Edge Cache is disabled on the server. Enable Edge Cache.', 'pressable_cache_management' )
							);
						}
					);
					return; // do not purge.
				}
			}

			// 5. Purge domain cache if method exists.
			if ( method_exists( $edge_cache, 'purge_domain_now' ) ) {
				$result = $edge_cache->purge_domain_now( 'dashboard-auto-purge' );
			} else {
				$result = false;
			}

			if ( $result ) {
				update_option( 'edge-cache-purge-time-stamp', gmdate( 'jS F Y g:ia' ) . ' UTC' );

				$message = $auto_enabled
					? __( 'Edge Cache was disabled on the server. It has been automatically enabled and purged successfully.', 'pressable_cache_management' )
					: __( 'Edge Cache purged successfully.', 'pressable_cache_management' );

				add_action(
					'admin_notices',
					function () use ( $message ) {
						printf(
							'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
							esc_html( $message )
						);
					}
				);
			} else {
				add_action(
					'admin_notices',
					function () {
						printf(
							'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
							esc_html__( 'Edge Cache purge failed. Please try again.', 'pressable_cache_management' )
						);
					}
				);
			}
		}
		add_action( 'init', 'pcm_pressable_edge_cache_purge_local' );
	}
}

if ( ! function_exists( 'pressable_cache_management_callback_section_edge_cache' ) ) {
	/**
	 * Callback for the Edge Cache section.
	 */
	function pressable_cache_management_callback_section_edge_cache() {
		echo '<p>' . esc_html__( 'Manage Edge Cache settings below.', 'pressable_cache_management' ) . '</p>';
	}
}

if ( ! function_exists( 'pressable_cache_management_callback_section_cache' ) ) {
	/**
	 * Callback for the Cache section.
	 */
	function pressable_cache_management_callback_section_cache() {
		echo '<p>' . esc_html__( 'Cache management options.', 'pressable_cache_management' ) . '</p>';
	}
}
