<?php //Pressable Cache Management - Flush cache for a single page from page preview


$options = get_option( 'pressable_cache_management_options' );

if ( isset( $options['flush_object_cache_for_single_page'] ) && ! empty( $options['flush_object_cache_for_single_page'] ) ) {

	class PcmFlushCacheAdminbar {


		public function __construct() {
		}

		public function add() {
			if ( is_admin() ) {
				add_action(
					'wp_before_admin_bar_render',
					array(
						$this,
						'PcmFlushCacheAdminbar',
					)
				);
				add_action(
					'admin_enqueue_scripts',
					array(
						$this,
						'load_toolbar_js',
					)
				);
				//run this js to flush cache for single page when branding is removed
				add_action(
					'admin_enqueue_scripts',
					array(
						$this,
						'load_remove_branding_toolbar_js',
					)
				);
				add_action(
					'admin_enqueue_scripts',
					array(
						$this,
						'load_toolbar_css',
					)
				);
			} else {

				if ( is_admin() || is_admin_bar_showing() ) {

					add_action(
						'wp_before_admin_bar_render',
						array(
							$this,
							'pcm_toolbar_for_page_preview',
						)
					);
					add_action(
						'wp_enqueue_scripts',
						array(
							$this,
							'load_toolbar_js',
						)
					);
					//run this js to flush cache for single page when branding is removed
					add_action(
						'admin_enqueue_scripts',
						array(
							$this,
							'load_remove_branding_toolbar_js',
						)
					);
					add_action(
						'wp_enqueue_scripts',
						array(
							$this,
							'load_toolbar_css',
						)
					);
					add_action(
						'wp_footer',
						array(
							$this,
							'print_my_inline_script',
						)
					);
				}
			}

			// Run function to flush cache for a single page
			add_action(
				'wp_ajax_pcm_delete_current_page_cache',
				array(
					$this,
					'pcm_delete_current_page_cache',

				)
			);

		}

		public function pcm_delete_current_page_cache() {

			if ( ! wp_verify_nonce( $_GET['nonce'], 'pcm_nonce' ) ) {

				die(
					json_encode(
						array(
							'Security Error!',
							'error',
							'alert',
						)
					)
				);
			}

			global $batcache, $wp_object_cache;

			// Do not load if our advanced-cache.php isn't loaded
			if ( ! isset( $batcache ) || ! is_object( $batcache ) || ! method_exists( $wp_object_cache, 'incr' ) ) {
				return;
			}

			$batcache->configure_groups();

			$_GET['path'] = urldecode( esc_url_raw( $_GET['path'] ) );

			// Security check to see if path is secured
			if ( preg_match( '/\.{2,}/', $_GET['path'] ) ) {
				die( 'Suspected Directory Traversal Attack' );
			}

			$homepage = get_home_url();

			$url_path = $_GET['path'];

			//join homepage and path together to form a complete url
			$pageurl = $homepage . $url_path;

			$url = $pageurl;

			$url = apply_filters( 'batcache_manager_link', $url );

			if ( empty( $url ) ) {
				return false;
			}

			do_action( 'batcache_manager_before_flush', $url );

			// Force url to http batcache cannot flush without this
			$url     = set_url_scheme( $url, 'http' );
			$url_key = md5( $url );

			if ( is_object( $batcache ) ) {

				wp_cache_add( "{$url_key}_version", 0, $batcache->group );
				wp_cache_incr( "{$url_key}_version", 1, $batcache->group );

			}

			if ( property_exists( $wp_object_cache, 'no_remote_groups' ) ) {

				$batcache_no_remote_group_key = $wp_object_cache->no_remote_groups;

				$batcache_no_remote_group_key = array_search( $batcache->group, (array) $wp_object_cache->no_remote_groups );
				if ( false !== $batcache_no_remote_group_key ) {
					// The *_version key needs to be replicated remotely, otherwise invalidation won't work.
					// The race condition here should be acceptable.
					unset( $wp_object_cache->no_remote_groups[ $batcache_no_remote_group_key ] );
					wp_cache_set( "{$url_key}_version", $batcache->group );
					$wp_object_cache->no_remote_groups[ $batcache_no_remote_group_key ] = $batcache->group;
				}
			}
			do_action( 'batcache_manager_after_flush', $url );

			//Save time stamp to database if cache is flushed for particular page.
			$object_cache_flush_time = date( ' jS F Y  g:ia' ) . "\nUTC";
			update_option( 'flush-object-cache-for-single-page-time-stamp', $object_cache_flush_time );

		}

		public function load_toolbar_css() {

			wp_enqueue_style( 'pressable-cache-management-toolbar', plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/toolbar.css', array(), time(), 'all' );
		}

		function load_toolbar_js() {

			wp_enqueue_script(
				'pcm-toolbar"',
				plugin_dir_url( dirname( __FILE__ ) ) . 'public/js/toolbar.js',
				array(
					'jquery',
				),
				time(),
				true
			);

		}

		//run this js to flush cache for single page when branding is removed
		function load_remove_branding_toolbar_js() {

			wp_enqueue_script(
				'pcm-toolbar"',
				plugin_dir_url( dirname( __FILE__ ) ) . 'public/js/toolbar_remove_branding.js',
				array(
					'jquery',
				),
				time(),
				true
			);

		}

		public function print_my_inline_script() {
			?>
			<script type="text/javascript">
				var pcm_ajaxurl = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
				var pcm_nonce = "<?php echo wp_create_nonce( 'pcm_nonce' ); ?>";
			</script>
			<?php
		}

		public function pcm_toolbar_for_page_preview() {
			global $wp_admin_bar;

			$remove_pressable_branding_tab_options = false;

			//Check if branding Pressable branding is enabled or disabled
			$remove_pressable_branding_tab_options = get_option( 'remove_pressable_branding_tab_options' );
			// $branding = $remove_pressable_branding_tab_options['branding_on_off_radio_button'];

			if ( $remove_pressable_branding_tab_options && 'disable' == $remove_pressable_branding_tab_options['branding_on_off_radio_button'] ) {

				$wp_admin_bar->add_node(
					array(
						'id'    => 'pcm-toolbar-parent-remove-branding',
						'title' => 'Flush Batcache',

						'class' => 'pcm-toolbar-childd',

					)
				);

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

			} else {

				$wp_admin_bar->add_node(
					array(
						'id'    => 'pcm-toolbar-parent',
						'title' => 'Flush Batcache',
					)
				);

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

			}
		}

		public function PcmFlushCacheAdminbar() {
		}

	}

	// Display flush button on admin bar on page preview
	add_action( 'init', 'pcm_show_flush_cache_option_for_single_page' );

	// Display flush cache option for only admin users
	function pcm_show_flush_cache_option_for_single_page() {
		$current_user = wp_get_current_user();

		if ( ! current_user_can( 'administrator' ) ) {

			return;

		} else {

			$toolbar = new PcmFlushCacheAdminbar();
			$toolbar->add();
		}
	}
} else {

	function load_toolbar_css() {

		wp_enqueue_style( 'pressable-cache-management-toolbar', plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/toolbar.css', array(), time(), 'all' );
	}

	add_action( 'init', 'load_toolbar_css' );

}
