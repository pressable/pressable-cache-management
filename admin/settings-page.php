<?php
// Pressable Cache Management - Settings Page

// Disable direct file access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Display the plugin settings page
function pressable_cache_management_display_settings_page() {
	// Check if user is allowed access
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Retrieve active tab from $_GET param
	$default_tab = null;
	$tab         = isset( $_GET['tab'] ) ? $_GET['tab'] : $default_tab;

	$remove_pressable_branding_tab_options = get_option( 'remove_pressable_branding_tab_options' );
	?>

	<div class="wrap branding-<?php echo ( is_array( $remove_pressable_branding_tab_options ) ) ? esc_html( json_encode( $remove_pressable_branding_tab_options ) ) : esc_html( $remove_pressable_branding_tab_options ); ?>">
		<!-- Page title -->
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

		<!-- Tabs -->
		<nav class="nav-tab-wrapper">
			<a href="admin.php?page=pressable_cache_management" class="nav-tab nav-tab-object-cache 
			<?php
			if ( $tab === null ) :
				?>
				nav-tab-active<?php endif; ?>">Object Cache</a>
			<a href="admin.php?page=pressable_cache_management&tab=edge_cache_settings_tab" class="nav-tab nav-tab-edge-cache 
			<?php
			if ( $tab === 'edge_cache_settings_tab' ) :
				?>
				nav-tab-active<?php endif; ?>">Edge Cache</a>
			<a href="admin.php?page=pressable_cache_management&tab=remove_pressable_branding_tab" class="nav-tab nav-tab-hidden 
			<?php
			if ( $tab === 'remove_pressable_branding_tab' ) :
				?>
				nav-tab-active<?php endif; ?>">Hidden Tab Remove Branding</a>
		</nav>

		<!-- Tab content -->
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				if ( $default_tab == $tab ) {
					settings_fields( 'pressable_cache_management_options' );
					do_settings_sections( 'pressable_cache_management' );
					submit_button( 'Save Settings', 'custom-class' );
				} elseif ( $tab == 'edge_cache_settings_tab' ) {
					settings_fields( 'edge_cache_settings_tab_options' );
					do_settings_sections( 'edge_cache_settings_tab' );
				} elseif ( $tab == 'remove_pressable_branding_tab' ) {
					settings_fields( 'remove_pressable_branding_tab_options' );
					do_settings_sections( 'remove_pressable_branding_tab' );
					submit_button( 'Save Settings', 'custom-class' );
				}

				// Enqueue custom CSS
				wp_enqueue_style( 'pressable_cache_management', plugin_dir_url( __DIR__ ) . 'public/css/style.css', array(), null, 'screen' );
				?>
				<style type="text/css">
					.nav-tab-hidden,
					#footer-built-with-love.branding-disable,
					.branding-disable h2,
					.branding-disable .pressablecmlogo {
						display: none !important;
					}
				</style>
			</form>
		</div>
	</div>
	<?php
}

// Footer message logic
function pcm_footer_msg() {
	if ( 'not-exists' === get_option( 'remove_pressable_branding_tab_options', 'not-exists' ) ) {
		add_option( 'remove_pressable_branding_tab_options', '' );
		$pcm_enable_pressable_branding = array(
			'branding_on_off_radio_button' => 'enable',
		);
		update_option( 'remove_pressable_branding_tab_options', $pcm_enable_pressable_branding );
	}

	add_filter( 'admin_footer_text', 'pcm_replace_default_footer' );
}

function pcm_replace_default_footer( $footer_text ) {
	$remove_pressable_branding_tab_options = get_option( 'remove_pressable_branding_tab_options' );

	if ( is_admin() && isset( $_GET['page'] ) && $_GET['page'] === 'pressable_cache_management' ) {
		if ( $remove_pressable_branding_tab_options && 'enable' == $remove_pressable_branding_tab_options['branding_on_off_radio_button'] ) {
			return 'Built with 
            <a href="admin.php?page=pressable_cache_management&tab=remove_pressable_branding_tab" style="text-decoration: none; color: transparent;"><span class="heart" style="color:red; font-size:24px;">&#x2665;</span></a> by The Pressable CS Team.';
		} else {
			echo '<span id="footer-thankyou">Built with 
            <a href="admin.php?page=pressable_cache_management&tab=remove_pressable_branding_tab" style="text-decoration: none; color: transparent;"><span class="heart" style="color:red; font-size:24px;">&#x2665;</span></a></span>';
			return '<style>#footer-thankyou:contains("Developed by Webarts"){ display: none; }</style>';
		}
	} else {
		return '';
	}
}

add_action( 'admin_init', 'pcm_footer_msg' );
