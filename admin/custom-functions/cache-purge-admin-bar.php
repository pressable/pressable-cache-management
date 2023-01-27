<?php  //Pressable Cache Purge Adds a Cache Purge button to the admin bar


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

add_action( 'admin_bar_menu', 'cache_add_item', 100 );

function cache_add_item( $admin_bar ) {

	if ( is_admin() ) {
		global $pagenow;

		$admin_bar->add_menu(
			array(
				'id'    => 'cache-purge',
				'title' => 'Object Cache Purge',
				'href'  => '#',
			)
		);
		// $admin_bar->add_menu( array( 'id'=>'settings','title'=>'Cache Settings', 'parent'=> 'cache-purge', 'href'=>'admin.php?page=pressable_cache_management' ) );

	}
}



add_action( 'admin_footer', 'cache_purge_action_js' );

function cache_purge_action_js() { ?>
  <script type="text/javascript" >
	 jQuery("li#wp-admin-bar-cache-purge .ab-item").on( "click", function() {
		var data = {
					  'action': 'pressable_cache_purge',
					};

		jQuery.post(ajaxurl, data, function(response) {
		   alert( response );
		});

	  });
  </script>


<style type="text/css">
	
/*#wp-admin-bar-cache-purge .ab-item { 
  background-color: #0AD8C7;
}
*/
</style>


	<?php
}

add_action( 'wp_ajax_pressable_cache_purge', 'pressable_cache_purge_callback' );


function pressable_cache_purge_callback() {
	wp_cache_flush();

	//Save time stamp to database if cache is flushed.
	$object_cache_flush_time = date( ' jS F Y  g:ia' ) . "\nUTC";

	update_option( 'flush-obj-cache-time-stamp', $object_cache_flush_time );
	$response = 'Object Cache Purged';
	echo $response;
	wp_die();
}
