<?php
// Pressable Cache Management - Flush cache for a particular page

// Disable direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['flush_object_cache_for_single_page'] ) && ! empty( $options['flush_object_cache_for_single_page'] ) ) {

	add_action( 'init', 'pcm_show_flush_cache_column' );

	// Display flush cache option for users with required capabilities
	function pcm_show_flush_cache_column() {
		if ( current_user_can( 'administrator' ) || current_user_can( 'editor' ) || current_user_can( 'manage_woocommerce' ) ) {
			$column = new FlushObjectCachePageColumn();
			$column->add();
		}
	}

	// Display admin notice if cache flush option is enabled successfully
	function flush_object_cache_for_single_page_admin_notice( $message = '', $classes = 'notice-success' ) {
		if ( ! empty( $message ) ) {
			printf( '<div class="notice %2$s">%1$s</div>', $message, $classes );
		}
	}

	function flush_object_cache_for_single_page_notice() {
		$flush_object_cache_for_single_display_notice = get_option( 'flush-object-cache-for-single-page-notice', 'activating' );

		if ( 'activating' === $flush_object_cache_for_single_display_notice &&
			( current_user_can( 'administrator' ) || current_user_can( 'editor' ) || current_user_can( 'manage_woocommerce' ) )
		) {
			add_action(
				'admin_notices',
				function () {
					$screen = get_current_screen();
					if ( $screen->id !== 'toplevel_page_pressable_cache_management' ) {
						return;
					}

					$user    = wp_get_current_user();
					$message = sprintf( '<p>You can Flush Cache for Individual page or post from page preview.</p>', $user->display_name );
					flush_object_cache_for_single_page_admin_notice( $message, 'notice notice-success is-dismissible' );
				}
			);

			update_option( 'flush-object-cache-for-single-page-notice', 'activated' );
		}
	}

	add_action( 'init', 'flush_object_cache_for_single_page_notice' );
} else {
	update_option( 'flush-object-cache-for-single-page-notice', 'activating' );
}

class FlushObjectCachePageColumn {

	public function __construct() {}

	public function add() {
		add_filter( 'post_row_actions', array( $this, 'add_flush_object_cache_link' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'add_flush_object_cache_link' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_js' ) );
		add_action( 'wp_ajax_pcm_flush_object_cache_column', array( $this, 'flush_object_cache_column' ) );
	}

	public function add_flush_object_cache_link( $actions, $post ) {
		if ( current_user_can( 'administrator' ) || current_user_can( 'editor' ) || current_user_can( 'manage_woocommerce' ) ) {
			$actions['flush_object_cache_url'] = '<a data-id="' . $post->ID . '" data-nonce="' . wp_create_nonce( 'flush-object-cache_' . $post->ID ) . '" id="flush-object-cache-url-' . $post->ID . '" style="cursor:pointer;">' . __( 'Flush Cache' ) . '</a>';
		}

		return $actions;
	}

	public function flush_object_cache_column() {
		if ( ! ( current_user_can( 'administrator' ) || current_user_can( 'editor' ) || current_user_can( 'manage_woocommerce' ) ) ) {
			die(
				json_encode(
					array(
						'success' => false,
						'message' => 'Unauthorized',
					)
				)
			);
		}

		if ( wp_verify_nonce( $_GET['nonce'], 'flush-object-cache_' . $_GET['id'] ) ) {
			$url_key    = get_permalink( $_GET['id'] );
			$page_title = get_the_title( $_GET['id'] );
			update_option( 'page-title', $page_title );

			global $batcache, $wp_object_cache;

			if ( ! isset( $batcache ) || ! is_object( $batcache ) || ! method_exists( $wp_object_cache, 'incr' ) ) {
				die( json_encode( array( 'success' => false ) ) );
			}

			$batcache->configure_groups();

			$url = apply_filters( 'batcache_manager_link', $url_key );
			if ( empty( $url ) ) {
				die( json_encode( array( 'success' => false ) ) );
			}

			do_action( 'batcache_manager_before_flush', $url );

			$url     = set_url_scheme( $url, 'http' );
			$url_key = md5( $url );

			wp_cache_add( "{$url_key}_version", 0, $batcache->group );
			wp_cache_incr( "{$url_key}_version", 1, $batcache->group );

			if ( property_exists( $wp_object_cache, 'no_remote_groups' ) ) {
				$batcache_no_remote_group_key = array_search( $batcache->group, (array) $wp_object_cache->no_remote_groups );

				if ( false !== $batcache_no_remote_group_key ) {
					unset( $wp_object_cache->no_remote_groups[ $batcache_no_remote_group_key ] );
					wp_cache_set( "{$url_key}_version", $batcache->group );
					$wp_object_cache->no_remote_groups[ $batcache_no_remote_group_key ] = $batcache->group;
				}
			}

			do_action( 'batcache_manager_after_flush', $url );

			$object_cache_flush_time = date( 'jS F Y  g:ia' ) . ' UTC';
			update_option( 'flush-object-cache-for-single-page-time-stamp', $object_cache_flush_time );

			die( json_encode( array( 'success' => true ) ) );
		} else {
			die( json_encode( array( 'success' => false ) ) );
		}
	}

	public function load_js() {
		wp_enqueue_script(
			'flush-object-cache-column',
			plugin_dir_url( __DIR__ ) . 'public/js/column.js',
			array(),
			time(),
			true
		);
	}
}
