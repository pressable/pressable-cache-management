<?php // Pressable Cache Management - Flush object cache 



// disable direct file access
if ( ! defined( 'ABSPATH' ) ) {
    
    exit;
    
}


// flush object cache button
if ( isset( $_POST['flush_object_cache_nonce']  ) ) {

// Flush cache button
function pressable_cache_button() {
    
    
    $options = get_option( 'pressable_cache_management_options');

    if(wp_verify_nonce($_POST['flush_object_cache_nonce'], 'flush_object_cache_nonce')) {
    
        wp_cache_flush();
     
        set_transient( 'set_transient_button', 50);

    }
}
add_action( 'wp_before_admin_bar_render', 'pressable_cache_button', 999 );


//Show success message when flush cache obk=ject cache is successful
function flush_cache_notice__success() {?>

    <div class="notice notice-success is-dismissible">
        <p><?php _e( 'Object Cache Flushed Successfully.', 'sample-text-domain' ); ?></p>
    </div>
    <?php
}
add_action( 'admin_notices', 'flush_cache_notice__success' );

 //Save time stamp to database if cache is flushed.
 $object_cache_flush_time = date(' jS F Y  g:ia') . "\nUTC";
            
 update_option( 'flush-obj-cache-time-stamp', $object_cache_flush_time);


    }


