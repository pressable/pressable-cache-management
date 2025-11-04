<?php
/**
 * Pressable Cache Management - Flush cache for individual page from page preview.
 *
 * @package Pressable
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pressable_Flush_Single_Page_Toolbar
 */
class Pressable_Flush_Single_Page_Toolbar {

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
		if ( is_admin() || is_admin_bar_showing() ) {
			add_action( 'wp_before_admin_bar_render', array( $this, 'pcm_toolbar_for_page_preview' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'load_toolbar_js' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'load_toolbar_js' ) );
			add_action( 'wp_footer', array( $this, 'print_my_inline_script' ) );
			add_action( 'admin_footer', array( $this, 'print_my_inline_script' ) );
		}

		add_action( 'wp_ajax_pcm_delete_current_page_cache', array( $this, 'pcm_delete_current_page_cache' ) );
		add_action( 'wp_ajax_pcm_purge_current_page_edge_cache', array( $this, 'pcm_purge_current_page_edge_cache' ) );
	}

	/**
	 * Delete current page cache.
	 */
	public function pcm_delete_current_page_cache() {
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'pcm_nonce' ) ) {
			die(
				wp_json_encode(
					array(
						'Security Error!',
						'error',
						'alert',
					)
				)
			);
		}

		global $batcache;

		// Do not load if our advanced-cache.php isn't loaded.
		if ( ! isset( $batcache ) || ! is_object( $batcache ) ) {
			return;
		}

		$batcache->configure_groups();

		$path = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : '';

		// Security check to see if path is secured.
		if ( preg_match( '/\.{2,}/', $path ) ) {
			die( 'Suspected Directory Traversal Attack' );
		}

		$homepage = get_home_url();
		$pageurl  = $homepage . $path;
		$url      = $pageurl;

		$url = apply_filters( 'batcache_manager_link', $url );

		if ( empty( $url ) ) {
			return;
		}

		do_action( 'batcache_manager_before_flush', $url );

		// Force url to http batcache cannot flush without this.
		$url = set_url_scheme( $url, 'http' );

		// Single page URL to be flushed.
		update_option( 'single-page-url-flushed', $url );
		$url_key = md5( $url );

		if ( is_object( $batcache ) ) {
			wp_cache_add( "{$url_key}_version", 0, $batcache->group );
			wp_cache_incr( "{$url_key}_version", 1, $batcache->group );
		}

		do_action( 'batcache_manager_after_flush', $url );

		// Save time stamp to database if object cache is flushed for particular page.
		$object_cache_flush_time = gmdate( ' jS F Y  g:ia' ) . "\nUTC";
		update_option( 'flush-object-cache-for-single-page-time-stamp', $object_cache_flush_time );
	}

	/**
	 * Purge current page edge cache.
	 */
	public function pcm_purge_current_page_edge_cache() {
		if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['nonce'] ) ), 'pcm_nonce' ) ) {
			die(
				wp_json_encode(
					array(
						'Security Error!',
						'error',
						'alert',
					)
				)
			);
		}

		$path = isset( $_GET['path'] ) ? sanitize_text_field( wp_unslash( $_GET['path'] ) ) : '';

		// Security check to see if path is secured.
		if ( preg_match( '/\.{2,}/', $path ) ) {
			die( 'Suspected Directory Traversal Attack' );
		}

		$homepage = get_home_url();
		$pageurl  = $homepage . $path;
		$url      = $pageurl;

		// Purged URL.
		update_option( 'edge-cache-single-page-url-purged', $url );

		if ( empty( $url ) ) {
			return;
		}

		// Purge Edge Cache for the specific URL using the correct class and method.
		if ( class_exists( 'Edge_Cache_Plugin' ) ) {
			$edge_cache = Edge_Cache_Plugin::get_instance();
			$urls       = array( $url );

			// Use the correct method with context parameter.
			$result = $edge_cache->purge_uris_now( $urls );

			// Save time stamp to database if edge cache is purged for particular page.
			$edge_cache_purge_time = gmdate( ' jS F Y  g:ia' ) . "\nUTC";
			update_option( 'single-page-edge-cache-purge-time-stamp', $edge_cache_purge_time );

			// Return the actual result from the purge operation.
			die( wp_json_encode( array( 'success' => $result ) ) );
		}

		die( wp_json_encode( array( 'success' => false ) ) );
	}

	/**
	 * Load toolbar CSS.
	 */
	public function load_toolbar_css() {
		wp_enqueue_style( 'pressable-cache-management-toolbar', plugin_dir_url( __DIR__ ) . 'public/css/toolbar.css', array(), time(), 'all' );
	}

	/**
	 * Load toolbar JS.
	 */
	public function load_toolbar_js() {
		wp_enqueue_script( 'pcm-toolbar', plugin_dir_url( __DIR__ ) . 'public/js/toolbar.js', array( 'jquery' ), time(), true );
	}

	/**
	 * Print inline script.
	 */
	public function print_my_inline_script() {
		?>
		<script type="text/javascript">
			var pcm_ajaxurl = "<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>";
			var pcm_nonce = "<?php echo esc_js( wp_create_nonce( 'pcm_nonce' ) ); ?>";
		</script>
		<?php
	}

	/**
	 * Add toolbar for page preview.
	 */
	public function pcm_toolbar_for_page_preview() {
		global $wp_admin_bar;

		$remove_pressable_branding_tab_options = get_option( 'remove_pressable_branding_tab_options' );

		// Check if branding Pressable branding is enabled or disabled.
		$is_branding_disabled = ( isset( $remove_pressable_branding_tab_options['branding_on_off_radio_button'] ) && 'disable' === $remove_pressable_branding_tab_options['branding_on_off_radio_button'] );

		// Check if Edge Cache is enabled.
		$edge_cache_enabled     = get_option( 'edge-cache-enabled' );
		$show_edge_cache_option = ( 'enabled' === $edge_cache_enabled );

		if ( $is_branding_disabled ) {
			$wp_admin_bar->add_node(
				array(
					'id'    => 'pcm-toolbar-parent-remove-branding',
					'title' => 'Flush Cache',
					'class' => 'pcm-toolbar-child',
				)
			);

			// Add Flush Batcache submenu.
			$wp_admin_bar->add_menu(
				array(
					'id'     => 'pcm-toolbar-parent-remove-branding-clear-cache-of-this-page',
					'title'  => 'Flush Batcache for This Page',
					'parent' => 'pcm-toolbar-parent-remove-branding',
					'meta'   => array(
						'class' => 'pcm-toolbar-child',
					),
				)
			);

			// Add Purge Edge Cache submenu only if Edge Cache is enabled.
			if ( $show_edge_cache_option ) {
				$wp_admin_bar->add_menu(
					array(
						'id'     => 'pcm-toolbar-parent-remove-branding-purge-edge-cache-of-this-page',
						'title'  => 'Purge Edge Cache for This Page',
						'parent' => 'pcm-toolbar-parent-remove-branding',
						'meta'   => array(
							'class' => 'pcm-toolbar-child',
						),
					)
				);
			}
		} else {
			$wp_admin_bar->add_node(
				array(
					'id'    => 'pcm-toolbar-parent',
					'title' => 'Flush Cache',
				)
			);

			// Add Flush Batcache submenu.
			$wp_admin_bar->add_menu(
				array(
					'id'     => 'pcm-toolbar-parent-clear-cache-of-this-page',
					'title'  => 'Flush Batcache for This Page',
					'parent' => 'pcm-toolbar-parent',
					'meta'   => array(
						'class' => 'pcm-toolbar-child',
					),
				)
			);

			// Add Purge Edge Cache submenu only if Edge Cache is enabled.
			if ( $show_edge_cache_option ) {
				$wp_admin_bar->add_menu(
					array(
						'id'     => 'pcm-toolbar-parent-purge-edge-cache-of-this-page',
						'title'  => 'Purge Edge Cache for This Page',
						'parent' => 'pcm-toolbar-parent',
						'meta'   => array(
							'class' => 'pcm-toolbar-child',
						),
					)
				);
			}
		}
	}
}

$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['flush_object_cache_for_single_page'] ) && ! empty( $options['flush_object_cache_for_single_page'] ) ) {
	// phpcs:ignore WordPress.WP.Capabilities.Unknown
	if ( current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' ) ) {
		new Pressable_Flush_Single_Page_Toolbar();
	}
}

