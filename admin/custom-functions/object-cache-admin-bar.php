<?php
/**
 * Pressable Cache Management - Adds a cache purge button to the admin bar.
 *
 * @package Pressable
 */

/**************************************
 * Pressable Cache Purge Adds a
 * Cache Purge button to the admin bar
 * by Jess Nunez modified by Tarhe Otughwor.
 *
 * Implemented all three cache purge options: Object, Edge, and Combined.
 *************************************/


// --- JavaScript for Admin Bar Buttons (using unique prefixes) ---

add_action( 'admin_footer', 'pcm_abar_object_js' );

/**
 * Function for Flush Object Cache button.
 */
function pcm_abar_object_js() {
	?>
	<script type="text/javascript">
			// Object Cache Purge.
			jQuery("li#wp-admin-bar-cache-purge .ab-item").on("click", function() {
				var data = {
					'action': 'flush_pressable_cache',
				};

				jQuery.post(ajaxurl, data, function(response) {
					alert(response.trim());
				});
			});
	</script>
	<?php
}

add_action( 'admin_footer', 'pcm_abar_edge_js' );

/**
 * Function for Purge Edge Cache button.
 */
function pcm_abar_edge_js() {

	?>
	<script type="text/javascript">
			// Edge Cache Purge.
			jQuery("li#wp-admin-bar-edge-purge .ab-item").on("click", function() {
				var data = {
					'action': 'pressable_edge_cache_purge',
				};

				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					data: data,
					success: function(response) {
						alert(response.trim());
					},
					error: function() {
						alert('An error occurred during the Edge Cache purge request.');
					}
				});
			});
	</script>
	<?php
}

add_action( 'admin_footer', 'pcm_abar_combined_js' );

/**
 * Function for Flush Object & Edge Cache button.
 */
function pcm_abar_combined_js() {

	?>
	<script type="text/javascript">
			// Combined Cache Purge.
			jQuery("li#wp-admin-bar-combined-cache-purge .ab-item").on("click", function() {
				var data = {
					'action': 'flush_combined_cache',
				};

				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					data: data,
					success: function(response) {
						alert(response.trim());
					},
					error: function() {
						alert('An error occurred during the combined cache flush request.');
					}
				});
			});
	</script>
	<?php
}


/**
 * Load plugin admin bar icon.
 */
function pcm_abar_load_css() {
	wp_enqueue_style(
		'pressable-cache-management-toolbar',
		plugin_dir_url( __DIR__ ) . 'public/css/toolbar.css',
		array(),
		time(),
		'all'
	);
}

add_action( 'init', 'pcm_abar_load_css' );


// --- WordPress AJAX Hooks ---

add_action( 'wp_ajax_flush_pressable_cache', 'pcm_abar_flush_object_callback' );
add_action( 'wp_ajax_pressable_edge_cache_purge', 'pcm_abar_purge_edge_callback' );
add_action( 'wp_ajax_flush_combined_cache', 'pcm_abar_flush_combined_callback' );


// --- AJAX Callback Functions ---

/**
 * Handles the single Flush Object Cache request.
 */
function pcm_abar_flush_object_callback() {
	// phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_pages' ) && ! current_user_can( 'manage_woocommerce' ) ) {
		echo esc_html__( 'You do not have permission to flush the Object Cache.', 'pressable-cache-management' );
		wp_die();
	}

	wp_cache_flush();

	$object_cache_flush_time = gmdate( 'jS F Y g:ia' ) . ' UTC';
	update_option( 'flush-obj-cache-time-stamp', $object_cache_flush_time );

	echo esc_html__( 'Object Cache Flushed Successfully! 🗑️', 'pressable-cache-management' );
	wp_die();
}


/**
 * Handles the single Purge Edge Cache request.
 */
function pcm_abar_purge_edge_callback() {
	// phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_pages' ) && ! current_user_can( 'manage_woocommerce' ) ) {
		echo esc_html__( 'You do not have permission to purge the Edge Cache.', 'pressable-cache-management' );
		wp_die();
	}

	if ( ! class_exists( 'Edge_Cache_Plugin' ) ) {
		echo esc_html__( 'Error: Edge Cache Plugin is not active. Purge aborted.', 'pressable_cache_management' );
		wp_die();
	}

	$edge_cache   = Edge_Cache_Plugin::get_instance();
	$purge_method = method_exists( $edge_cache, 'purge_domain_now' ) ? 'purge_domain_now' : null;

	if ( ! $purge_method ) {
		echo esc_html__( 'Error: Edge Cache Plugin purge method unavailable. Purge aborted.', 'pressable-cache-management' );
		wp_die();
	}

	$result = $edge_cache->$purge_method( 'admin-bar-single-edge-purge' );

	if ( $result ) {
		$edge_cache_purged_time = gmdate( 'jS F Y g:ia' ) . ' UTC';
		update_option( 'edge-cache-purge-time-stamp', $edge_cache_purged_time );
		echo esc_html__( 'Edge Cache purged successfully! 🚀', 'pressable-cache-management' );
	} else {
		echo esc_html__( 'Edge Cache purge failed. It might be disabled or rate-limited.', 'pressable-cache-management' );
	}

	wp_die();
}


/**
 * Handles the Flush Object & Edge Cache request.
 */
function pcm_abar_flush_combined_callback() {
	// phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'edit_pages' ) && ! current_user_can( 'manage_woocommerce' ) ) {
		echo esc_html__( 'You do not have permission to flush the combined cache.', 'pressable-cache-management' );
		wp_die();
	}

	$messages = array();

	// --- Flush Object Cache ---
	wp_cache_flush();
	$object_cache_flush_time = gmdate( 'jS F Y g:ia' ) . ' UTC';
	update_option( 'flush-obj-cache-time-stamp', $object_cache_flush_time );
	$messages[] = 'Object Cache Flushed successfully.';

	// --- Flush Edge Cache ---
	if ( class_exists( 'Edge_Cache_Plugin' ) ) {
		$edge_cache   = Edge_Cache_Plugin::get_instance();
		$purge_method = method_exists( $edge_cache, 'purge_domain_now' ) ? 'purge_domain_now' : null;

		if ( $purge_method ) {
			$result = $edge_cache->$purge_method( 'admin-bar-combined-purge' );

			if ( $result ) {
				$edge_cache_purged_time = gmdate( 'jS F Y g:ia' ) . ' UTC';
				update_option( 'edge-cache-purge-time-stamp', $edge_cache_purged_time );
				$messages[] = 'Edge Cache Purged successfully.';
			} else {
				$messages[] = 'Edge Cache purge failed (possibly disabled or rate-limited).';
			}
		} else {
			$messages[] = 'Edge Cache Plugin active, but purge method unavailable.';
		}
	} else {
		$messages[] = 'Edge Cache Plugin not found; skipping Edge Cache purge.';
	}

	echo "\n- " . esc_html( implode( "\n- ", $messages ) );
	wp_die();
}


// --- Admin Bar Menu ---

/**
 * Check if the user can view the admin bar menu.
 */
function pcm_abar_can_view() {
	// phpcs:ignore WordPress.WP.Capabilities.Unknown
	return current_user_can( 'manage_options' ) || current_user_can( 'edit_pages' ) || current_user_can( 'manage_woocommerce' );
}

add_action( 'admin_bar_menu', 'pcm_abar_add_menu', 100 );

/**
 * Adds the Admin Bar cache menu with dynamic Edge Cache detection.
 *
 * @param WP_Admin_Bar $wp_admin_bar The admin bar object.
 */
function pcm_abar_add_menu( $wp_admin_bar ) {
	if ( is_network_admin() || ! pcm_abar_can_view() ) {
		return;
	}

	$remove_pressable_branding_tab_options = get_option( 'remove_pressable_branding_tab_options' );
	$is_branding_disabled                  = $remove_pressable_branding_tab_options &&
		'disable' === $remove_pressable_branding_tab_options['branding_on_off_radio_button'];

	$parent_id    = $is_branding_disabled
		? 'pcm-wp-admin-toolbar-parent-remove-branding'
		: 'pcm-wp-admin-toolbar-parent';
	$parent_title = $is_branding_disabled ? 'Cache Control' : 'Cache Management';

	// ✅ Dynamic Edge Cache detection and auto-enable.
	$edge_cache_is_enabled = false;

	if ( class_exists( 'Edge_Cache_Plugin' ) ) {
		$edge_cache    = Edge_Cache_Plugin::get_instance();
		$status_method = method_exists( $edge_cache, 'get_ec_status' ) ? 'get_ec_status' : null;
		$enable_method = method_exists( $edge_cache, 'enable_ec' ) ? 'enable_ec' : null;

		$server_status = null;
		if ( null !== $status_method ) {
			$server_status = $edge_cache->$status_method();
		}

		if ( Edge_Cache_Plugin::EC_DISABLED === $server_status && null !== $enable_method ) {
			$enabled = $edge_cache->$enable_method();
			if ( $enabled ) {
				$edge_cache_is_enabled = true;
				sleep( 2 );
			} else {
				add_action(
					'admin_notices',
					function () {
						printf(
							'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
							esc_html__( 'Edge Cache was disabled and could not be auto-enabled. Purge options will remain unavailable.', 'pressable_cache_management' )
						);
					}
				);
			}
		} elseif ( Edge_Cache_Plugin::EC_ENABLED === $server_status ) {
			$edge_cache_is_enabled = true;
		}
	}

	// 1. Parent Node.
	$wp_admin_bar->add_node(
		array(
			'id'    => $parent_id,
			'title' => $parent_title,
		)
	);

	// 2. Flush Object Cache.
	$wp_admin_bar->add_menu(
		array(
			'id'     => 'cache-purge',
			'title'  => 'Flush Object Cache',
			'parent' => $parent_id,
			'meta'   => array( 'class' => 'pcm-wp-admin-toolbar-child' ),
		)
	);

	// 3. Edge Cache Options (Only if enabled).
	if ( $edge_cache_is_enabled ) {
		$wp_admin_bar->add_menu(
			array(
				'id'     => 'edge-purge',
				'title'  => 'Purge Edge Cache',
				'parent' => $parent_id,
				'href'   => '',
				'meta'   => array( 'class' => 'pcm-wp-admin-toolbar-child' ),
			)
		);

		$wp_admin_bar->add_menu(
			array(
				'id'     => 'combined-cache-purge',
				'title'  => 'Flush Object & Edge Cache',
				'parent' => $parent_id,
				'meta'   => array( 'class' => 'pcm-wp-admin-toolbar-child' ),
			)
		);
	}

	// 4. Cache Settings (Admin only).
	if ( current_user_can( 'manage_options' ) ) {
		$wp_admin_bar->add_menu(
			array(
				'id'     => 'settings',
				'title'  => 'Cache Settings',
				'parent' => $parent_id,
				'href'   => 'admin.php?page=pressable_cache_management',
				'meta'   => array( 'class' => 'pcm-wp-admin-toolbar-child' ),
			)
		);
	}
}

