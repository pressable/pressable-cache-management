<?php
/**
 * Pressable Cache Management - Flush cache for a particular page.
 *
 * @package Pressable
 */

// Disable direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pressable_Flush_Object_Cache_Page_Column
 */
class Pressable_Flush_Object_Cache_Page_Column {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Initialize hooks.
	 */
	public function init() {
		$options = get_option( 'pressable_cache_management_options' );

		if ( isset( $options['flush_object_cache_for_single_page'] ) && ! empty( $options['flush_object_cache_for_single_page'] ) ) {
			add_action( 'init', array( $this, 'flush_object_cache_for_single_page_notice' ) );

			// phpcs:ignore WordPress.WP.Capabilities.Unknown
			if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_pages' ) || current_user_can( 'manage_woocommerce' ) ) {
				$this->add();
			}
		} else {
			update_option( 'flush-object-cache-for-single-page-notice', 'activating' );
		}
	}

	/**
	 * Add hooks for page and post columns.
	 */
	public function add() {
		add_filter( 'post_row_actions', array( $this, 'add_flush_object_cache_link' ), 10, 2 );
		add_filter( 'page_row_actions', array( $this, 'add_flush_object_cache_link' ), 10, 2 );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_js' ) );
		add_action( 'wp_ajax_pcm_flush_object_cache_column', array( $this, 'flush_object_cache_column' ) );
	}

	/**
	 * Add flush object cache link to page and post columns.
	 *
	 * @param array   $actions The actions.
	 * @param WP_Post $post    The post object.
	 * @return array
	 */
	public function add_flush_object_cache_link( $actions, $post ) {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown
		if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_pages' ) || current_user_can( 'manage_woocommerce' ) ) {
			$actions['flush_object_cache_url'] = '<a data-id="' . $post->ID . '" data-nonce="' . wp_create_nonce( 'flush-object-cache_' . $post->ID ) . '" id="flush-object-cache-url-' . $post->ID . '" style="cursor:pointer;">' . __( 'Flush Cache', 'pressable-cache-management' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Flush object cache column.
	 */
	public function flush_object_cache_column() {
		// phpcs:ignore WordPress.WP.Capabilities.Unknown
		if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'edit_pages' ) || current_user_can( 'manage_woocommerce' ) ) ) {
			die(
				wp_json_encode(
					array(
						'success' => false,
						'message' => 'Unauthorized',
					)
				)
			);
		}

		$nonce = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';
		$id    = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;

		if ( wp_verify_nonce( $nonce, 'flush-object-cache_' . $id ) ) {
			$url_key    = get_permalink( $id );
			$page_title = get_the_title( $id );
			update_option( 'page-title', $page_title );

			global $batcache;

			if ( ! isset( $batcache ) || ! is_object( $batcache ) ) {
				die( wp_json_encode( array( 'success' => false ) ) );
			}

			$batcache->configure_groups();

			$url = apply_filters( 'batcache_manager_link', $url_key );
			if ( empty( $url ) ) {
				die( wp_json_encode( array( 'success' => false ) ) );
			}

			do_action( 'batcache_manager_before_flush', $url );

			$url     = set_url_scheme( $url, 'http' );
			$url_key = md5( $url );

			wp_cache_add( "{$url_key}_version", 0, $batcache->group );
			wp_cache_incr( "{$url_key}_version", 1, $batcache->group );

			do_action( 'batcache_manager_after_flush', $url );

			$object_cache_flush_time = gmdate( 'jS F Y  g:ia' ) . ' UTC';
			update_option( 'flush-object-cache-for-single-page-time-stamp', $object_cache_flush_time );

			die( wp_json_encode( array( 'success' => true ) ) );
		} else {
			die( wp_json_encode( array( 'success' => false ) ) );
		}
	}

	/**
	 * Load JS.
	 */
	public function load_js() {
		wp_enqueue_script( 'flush-object-cache-column', plugin_dir_url( __DIR__ ) . 'public/js/column.js', array(), time(), true );
	}

	/**
	 * Display admin notice if cache flush option is enabled successfully.
	 *
	 * @param string $message The message.
	 * @param string $classes The classes.
	 */
	public function flush_object_cache_for_single_page_admin_notice( $message = '', $classes = 'notice-success' ) {
		if ( ! empty( $message ) ) {
			printf( '<div class="notice %2$s">%1$s</div>', esc_html( $message ), esc_attr( $classes ) );
		}
	}

	/**
	 * Flush object cache for single page notice.
	 */
	public function flush_object_cache_for_single_page_notice() {
		$flush_object_cache_for_single_display_notice = get_option( 'flush-object-cache-for-single-page-notice', 'activating' );

		// phpcs:ignore WordPress.WP.Capabilities.Unknown
		if ( 'activating' === $flush_object_cache_for_single_display_notice && ( current_user_can( 'manage_options' ) || current_user_can( 'edit_pages' ) || current_user_can( 'manage_woocommerce' ) ) ) {
			add_action(
				'admin_notices',
				function () {
					$screen = get_current_screen();
					if ( 'toplevel_page_pressable_cache_management' !== $screen->id ) {
						return;
					}

					$user    = wp_get_current_user();
					$message = sprintf( '<p>You can Flush Cache for Individual page or post from page preview.</p>', $user->display_name );
					$this->flush_object_cache_for_single_page_admin_notice( $message, 'notice notice-success is-dismissible' );
				}
			);

			update_option( 'flush-object-cache-for-single-page-notice', 'activated' );
		}
	}
}

new Pressable_Flush_Object_Cache_Page_Column();
