<?php
// Pressable Cache Management - Adds a cache purge button to the admin bar


/**************************************
 * Pressable Cache Purge Adds a
 * Cache Purge button to the admin bar
 * by Jess Nunez modified by Tarhe Otughwor
 *
 * Implemented all three cache purge options: Object, Edge, and Combined.
 *************************************/


// --- JavaScript for Admin Bar Buttons (using unique prefixes) ---

add_action('admin_footer', 'pcm_abar_object_js');

// Function for Flush Object Cache button
function pcm_abar_object_js()
{ ?>
     <script type="text/javascript" >
          // Object Cache Purge
          jQuery("li#wp-admin-bar-cache-purge .ab-item").on( "click", function() {
              var data = {
                  'action': 'flush_pressable_cache',
              };

              jQuery.post(ajaxurl, data, function(response) {
                  alert( response.trim() );
              });
          });
     </script>
        <?php
}

add_action('admin_footer', 'pcm_abar_edge_js');

// Function for Purge Edge Cache button
function pcm_abar_edge_js()
{ ?>
     <script type="text/javascript" >
          // Edge Cache Purge
          jQuery("li#wp-admin-bar-edge-purge .ab-item").on( "click", function() {
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

add_action('admin_footer', 'pcm_abar_combined_js');

// Function for Flush Object & Edge Cache button
function pcm_abar_combined_js()
{ ?>
     <script type="text/javascript" >
          // Combined Cache Purge
          jQuery("li#wp-admin-bar-combined-cache-purge .ab-item").on( "click", function() {
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


// Load plugin admin bar icon (RENAMED to fix redeclaration error)
function pcm_abar_load_css()
{
    // Keeping original logic for CSS enqueue to preserve icons/styling
    wp_enqueue_style('pressable-cache-management-toolbar', plugin_dir_url(dirname(__FILE__)) . 'public/css/toolbar.css', array() , time() , "all");
}

add_action('init', 'pcm_abar_load_css');


// --- WordPress AJAX Hooks (using unique prefixes for local callbacks) ---

// 1. Flush Object Cache
add_action('wp_ajax_flush_pressable_cache', 'pcm_abar_flush_object_callback');
// 2. Purge Edge Cache
add_action('wp_ajax_pressable_edge_cache_purge', 'pcm_abar_purge_edge_callback');
// 3. Combined Flush
add_action('wp_ajax_flush_combined_cache', 'pcm_abar_flush_combined_callback');

// NOTE: Obsolete CDN/API hooks (pressable_cdn_cache_purge) are removed.


// --- AJAX Callback Functions ---

/**
 * Handles the single Flush Object Cache request.
 */
function pcm_abar_flush_object_callback()
{
    // Check user capability: administrator, editor, or shop manager
    if (!current_user_can('administrator') && !current_user_can('editor') && !current_user_can('manage_woocommerce')) {
        echo 'You do not have permission to flush the Object Cache.';
        wp_die();
    }

    wp_cache_flush();

    // Save time stamp to database
    $object_cache_flush_time = date(' jS F Y g:ia') . "\nUTC";
    update_option('flush-obj-cache-time-stamp', $object_cache_flush_time);

    $response = "Object Cache Flushed Successfully! 🗑️";
    echo $response;
    wp_die();
}

/**
 * Handles the single Purge Edge Cache request (using local plugin methods).
 */
function pcm_abar_purge_edge_callback()
{
    // Check user capability
    if (!current_user_can('administrator') && !current_user_can('editor') && !current_user_can('manage_woocommerce')) {
        echo 'You do not have permission to purge the Edge Cache.';
        wp_die();
    }

    if (!class_exists('Edge_Cache_Plugin')) {
        echo esc_html__('Error: Edge Cache Plugin is not active. Purge aborted.', 'pressable_cache_management');
        wp_die();
    }

    $edge_cache = Edge_Cache_Plugin::get_instance();
    $purge_method  = method_exists($edge_cache, 'purge_domain_now') ? 'purge_domain_now' : null;

    if (!$purge_method) {
        echo esc_html__('Error: Edge Cache Plugin purge method is unavailable. Purge aborted.', 'pressable_cache_management');
        wp_die();
    }

    // Purge the entire domain cache
    $result = $edge_cache->$purge_method('admin-bar-single-edge-purge');

    if ($result) {
        $edge_cache_purged_time = date(' jS F Y g:ia') . "\nUTC";
        update_option('edge-cache-purge-time-stamp', $edge_cache_purged_time);
        $message = __('Edge Cache purged successfully! 🚀', 'pressable_cache_management');
    } else {
        $message = esc_html__('Edge Cache purge failed. It might be disabled or rate-limited.', 'pressable_cache_management');
    }

    echo $message;
    wp_die();
}

/**
 * Handles the Flush Object & Edge Cache request.
 */
function pcm_abar_flush_combined_callback()
{
    // Check user capability
    if (!current_user_can('administrator') && !current_user_can('editor') && !current_user_can('manage_woocommerce')) {
        echo 'You do not have permission to flush the combined cache.';
        wp_die();
    }

    $messages = [];

    // --- 1. Flush Object Cache ---
    wp_cache_flush();
    $object_cache_flush_time = date(' jS F Y g:ia') . "\nUTC";
    update_option('flush-obj-cache-time-stamp', $object_cache_flush_time);
    $messages[] = "Object Cache Flushed successfully.";

    // --- 2. Flush Edge Cache ---
    if (class_exists('Edge_Cache_Plugin')) {
        $edge_cache = Edge_Cache_Plugin::get_instance();
        $purge_method = method_exists($edge_cache, 'purge_domain_now') ? 'purge_domain_now' : null;

        if ($purge_method) {
            $result = $edge_cache->$purge_method('admin-bar-combined-purge');

            if ($result) {
                $edge_cache_purged_time = date(' jS F Y g:ia') . "\nUTC";
                update_option('edge-cache-purge-time-stamp', $edge_cache_purged_time);
                $messages[] = "Edge Cache Purged successfully.";
            } else {
                $messages[] = "Edge Cache purge failed (possibly disabled or rate-limited).";
            }
        } else {
             $messages[] = "Edge Cache Plugin active, but purge method is unavailable.";
        }
    } else {
         $messages[] = "Edge Cache Plugin not found; skipping Edge Cache purge.";
    }


    echo "\n- " . implode("\n- ", $messages);
    wp_die();
}


// --- Admin Bar Logic (Unified and Fixed) ---

// Helper function to check required capabilities
function pcm_abar_can_view() {
    // Administrator, Editor, or Shop Manager
    return current_user_can('administrator') || current_user_can('editor') || current_user_can('manage_woocommerce');
}

// Register the single function to add the menu item (UNIFIED to prevent double menu)
add_action('admin_bar_menu', 'pcm_abar_add_menu', 100);

/**
 * Unified function to add the Admin Bar cache menu, handling both branded and non-branded modes.
 */
function pcm_abar_add_menu($wp_admin_bar)
{
    // Exit if not in the right context or user lacks permissions
    if (is_network_admin() || !pcm_abar_can_view()) {
        return;
    }

    // Determine branding status and set Parent Node details
    $remove_pressable_branding_tab_options = get_option('remove_pressable_branding_tab_options');
    $is_branding_disabled = $remove_pressable_branding_tab_options && 'disable' == $remove_pressable_branding_tab_options['branding_on_off_radio_button'];

    $parent_id = $is_branding_disabled ? 'pcm-wp-admin-toolbar-parent-remove-branding' : 'pcm-wp-admin-toolbar-parent';
    $parent_title = $is_branding_disabled ? 'Cache Control' : 'Cache Management';
    $edge_cache_is_enabled = get_option('edge-cache-enabled') === 'enabled';

    // 1. Add Parent Node
    $wp_admin_bar->add_node(array(
        'id' => $parent_id,
        'title' => $parent_title
    ));

    // 2. Add Flush Object Cache
    $wp_admin_bar->add_menu(array(
        'id' => 'cache-purge',
        'title' => 'Flush Object Cache',
        'parent' => $parent_id,
        'meta' => array(
            "class" => "pcm-wp-admin-toolbar-child"
        )
    ));

    // 3. Add Edge Cache Options (Only if enabled)
    if ($edge_cache_is_enabled) {
        // Purge Edge Cache
        $wp_admin_bar->add_menu(array(
            'id' => 'edge-purge',
            'title' => 'Purge Edge Cache',
            'parent' => $parent_id,
            'href' => '',
            'meta' => array(
                'class' => 'pcm-wp-admin-toolbar-child'
            )
        ));

        // Flush Object & Edge Cache (Combined)
         $wp_admin_bar->add_menu(array(
            'id' => 'combined-cache-purge',
            'title' => 'Flush Object & Edge Cache',
            'parent' => $parent_id,
            'meta' => array(
                "class" => "pcm-wp-admin-toolbar-child"
            )
        ));
    }

    // 4. Add Cache Settings (Admin only)
    if (current_user_can('administrator')) {
        $wp_admin_bar->add_menu(array(
            'id' => 'settings',
            'title' => 'Cache Settings',
            'parent' => $parent_id,
            'href' => 'admin.php?page=pressable_cache_management',
            'meta' => array(
                "class" => "pcm-wp-admin-toolbar-child"
            )
        ));
    }
}

