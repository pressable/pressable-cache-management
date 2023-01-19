<?php // Pressable Cache Management  - Flush cache for a particular page


// disable direct file access
if (!defined('ABSPATH'))
{

    exit;

}

$options = get_option('pressable_cache_management_options');

if (isset($options['flush_object_cache_for_single_page']) && !empty($options['flush_object_cache_for_single_page']))
{

    add_action('init', 'pcm_show_flush_cache_column');

    // Display flush cache option for only admin users
    function pcm_show_flush_cache_column()
    {
        $current_user = wp_get_current_user();

        if (!current_user_can('administrator'))
        {

            return;

        }
        else
        {

            //Call class to flush single page cache
            $column = new FlushObjectCachePageColumn();
            $column->add();
        }
    }

    //Display admin notice if cache flushe option is enabled successfully
    function flush_object_cache_for_single_page_admin_notice($message = '', $classes = 'notice-success')
    {

        if (!empty($message))
        {
            printf('<div class="notice %2$s">%1$s</div>', $message, $classes);
        }
    }

    function flush_object_cache_for_single_page_notice()
    {

        $flush_object_cache_for_single_display_notice = get_option('flush-object-cache-for-single-page-notice', 'activating');

        if ('activating' === $flush_object_cache_for_single_display_notice && current_user_can('manage_options'))
        {

            add_action('admin_notices', function ()
            {

                $screen = get_current_screen();

                //Display admin notice for this plugin page only
                if ($screen->id !== 'toplevel_page_pressable_cache_management') return;

                $user = $GLOBALS['current_user'];
                $message = sprintf('<p>You can Flush Cache for Individual page or post from page preview.</p>', $user->display_name);

                flush_object_cache_for_single_page_admin_notice($message, 'notice notice-success is-dismissible');
            });

            update_option('flush-object-cache-for-single-page-notice', 'activated');

        }
    }
    add_action('init', 'flush_object_cache_for_single_page_notice');

}

else
{

    /**Update option from the database if the option is dectivated
     used by admin notice to display and remove notice**/
    update_option('flush-object-cache-for-single-page-notice', 'activating');
}

class FlushObjectCachePageColumn
{
    public function __construct()
    {
    }

    public function add()
    {
        add_filter('post_row_actions', array(
            $this,
            'add_flush_object_cache_link'
        ) , 10, 2);
        add_filter('page_row_actions', array(
            $this,
            'add_flush_object_cache_link'
        ) , 10, 2);

        add_action('admin_enqueue_scripts', array(
            $this,
            'load_js'
        ));
        add_action('wp_ajax_pcm_flush_object_cache_column', array(
            $this,
            "flush_object_cache_column"
        ));
    }

    public function add_flush_object_cache_link($actions, $post)
    {
        $actions['flush_object_cache_url'] = '<a data-id="' . $post->ID . '" data-nonce="' . wp_create_nonce('flush-object-cache_' . $post->ID) . '" id="flush-object-cache-url-' . $post->ID . '" style="cursor:pointer;">' . __('Flush Cache') . '</a>';

        return $actions;
    }

    public function flush_object_cache_column()
    {
        if (wp_verify_nonce($_GET["nonce"], 'flush-object-cache_' . $_GET["id"]))
        {

            $url_key = get_permalink($_GET["id"]);

            $page_title = get_the_title($_GET["id"]);

            // Update the page title that the cache was flushed on
            update_option('page-title', $page_title);

            /****
             * Method to flush batache for a single page
             * Refer to Batache manager Github repo for method to flush batache for a single page
             * https://github.com/spacedmonkey/batcache-manager/blob/master/batcache-manager.php
             ****/
            global $batcache, $wp_object_cache;

            // Do not load if our advanced-cache.php isn't loaded
            if (!isset($batcache) || !is_object($batcache) || !method_exists($wp_object_cache, 'incr'))
            {
                return;
            }

            $batcache->configure_groups();

            $url = $url_key;

            $url = apply_filters('batcache_manager_link', $url);

            if (empty($url))
            {
                return false;
            }

            do_action('batcache_manager_before_flush', $url);

            // Force url to https
            $url = set_url_scheme($url, 'http');
            $url_key = md5($url);

            if (is_object($batcache))
            {

                wp_cache_add("{$url_key}_version", 0, $batcache->group);
                wp_cache_incr("{$url_key}_version", 1, $batcache->group);

            }

            if (property_exists($wp_object_cache, 'no_remote_groups'))

            {

                $batcache_no_remote_group_key = $wp_object_cache->no_remote_groups;

                $batcache_no_remote_group_key = array_search($batcache->group, (array)$wp_object_cache->no_remote_groups);

                if (false !== $batcache_no_remote_group_key)
                {
                    // The *_version key needs to be replicated remotely, otherwise invalidation won't work.
                    // The race condition here should be acceptable.
                    unset($wp_object_cache->no_remote_groups[$batcache_no_remote_group_key]);
                    wp_cache_set("{$url_key}_version", $batcache->group);
                    $wp_object_cache->no_remote_groups[$batcache_no_remote_group_key] = $batcache->group;
                }

            }
            do_action('batcache_manager_after_flush', $url);

            //      return $retval;
            //Save time stamp to database if cache is flushed for particular page.
            $object_cache_flush_time = date(' jS F Y  g:ia') . "\nUTC";
            update_option('flush-object-cache-for-single-page-time-stamp', $object_cache_flush_time);

            die(json_encode(array(
                "success" => true
            )));
        }

        {
            die(json_encode(array(
                "success" => false
            )));
        }
    }

    public function load_js()
    {

        wp_enqueue_script('flush-object-cache-column', plugin_dir_url(dirname(__FILE__)) . 'public/js/column.js', array() , time() , true);

    }
}
