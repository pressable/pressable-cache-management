<?php
/**
 * Pressable Edge Cache Purge Functionality
 * Mirrors the official repo's purge-edge-cache.php exactly,
 * with branded admin notices applied.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( isset( $_POST['purge_edge_cache_nonce'] ) ) {

    if ( ! function_exists( 'pcm_pressable_edge_cache_purge_local' ) ) {

        function pcm_pressable_edge_cache_purge_local() {
            // 1. Verify nonce + capability
            if (
                ! isset( $_POST['purge_edge_cache_nonce'] ) ||
                ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['purge_edge_cache_nonce'] ) ), 'purge_edge_cache_nonce' ) ||
                ! current_user_can( 'manage_options' )
            ) {
                return;
            }

            // 2. Ensure Edge Cache Plugin exists
            if ( ! class_exists( 'Edge_Cache_Plugin' ) ) {
                add_action( 'admin_notices', function() {
                    if ( function_exists( 'pcm_branded_notice' ) ) {
                        pcm_branded_notice( esc_html__( 'Error: Edge Cache Plugin is not active.', 'pressable_cache_management' ), '#dd3a03' );
                    } else {
                        printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html__( 'Error: Edge Cache Plugin is not active.', 'pressable_cache_management' ) );
                    }
                });
                return;
            }

            // 3. Get Edge Cache instance and current status
            $edge_cache    = Edge_Cache_Plugin::get_instance();
            $status_method = method_exists( $edge_cache, 'get_ec_status' ) ? 'get_ec_status' : null;
            $enable_method = method_exists( $edge_cache, 'enable_ec' )     ? 'enable_ec'     : null;
            $server_status = $status_method ? $edge_cache->$status_method() : null;
            $auto_enabled  = false;

            // 4. If disabled, handle based on availability of enable_ec()
            if ( Edge_Cache_Plugin::EC_DISABLED === $server_status ) {
                if ( null !== $enable_method ) {
                    $enabled = $edge_cache->$enable_method();
                    if ( $enabled ) {
                        $auto_enabled = true;
                    } else {
                        add_action( 'admin_notices', function() {
                            if ( function_exists( 'pcm_branded_notice' ) ) {
                                pcm_branded_notice( esc_html__( 'Edge Cache was disabled and could not be auto-enabled. Purge aborted.', 'pressable_cache_management' ), '#dd3a03' );
                            } else {
                                printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html__( 'Edge Cache was disabled and could not be auto-enabled. Purge aborted.', 'pressable_cache_management' ) );
                            }
                        });
                        return;
                    }
                } else {
                    add_action( 'admin_notices', function() {
                        if ( function_exists( 'pcm_branded_notice' ) ) {
                            pcm_branded_notice( esc_html__( 'Edge Cache is disabled on the server. Enable Edge Cache.', 'pressable_cache_management' ), '#f59e0b' );
                        } else {
                            printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', esc_html__( 'Edge Cache is disabled on the server. Enable Edge Cache.', 'pressable_cache_management' ) );
                        }
                    });
                    return;
                }
            }

            // 5. Purge domain cache
            $result = method_exists( $edge_cache, 'purge_domain_now' )
                ? $edge_cache->purge_domain_now( 'dashboard-auto-purge' )
                : false;

            if ( $result ) {
                update_option( 'edge-cache-purge-time-stamp', gmdate( 'jS F Y g:ia' ) . ' UTC' );
                // Clear the Batcache status transient so the badge re-probes immediately.
                // Without this the badge can sit on 'active' for up to 90s after a purge
                // even though Batcache is now in a transitional broken state.
                do_action( 'pcm_after_edge_cache_purge' );
                $message = $auto_enabled
                    ? esc_html__( 'Edge Cache was disabled on the server. It has been automatically enabled and purged successfully.', 'pressable_cache_management' )
                    : esc_html__( 'Edge Cache purged successfully.', 'pressable_cache_management' );

                add_action( 'admin_notices', function() use ( $message ) {
                    if ( function_exists( 'pcm_branded_notice' ) ) {
                        pcm_branded_notice( $message, '#03fcc2' );
                    } else {
                        printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $message ) );
                    }
                });
            } else {
                add_action( 'admin_notices', function() {
                    if ( function_exists( 'pcm_branded_notice' ) ) {
                        pcm_branded_notice( esc_html__( 'Edge Cache purge failed. Please try again.', 'pressable_cache_management' ), '#dd3a03' );
                    } else {
                        printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html__( 'Edge Cache purge failed. Please try again.', 'pressable_cache_management' ) );
                    }
                });
            }
        }

        add_action( 'init', 'pcm_pressable_edge_cache_purge_local' );
    }
}

// Prevent duplicate section callback declarations
if ( ! function_exists( 'pressable_cache_management_callback_section_edge_cache' ) ) {
    function pressable_cache_management_callback_section_edge_cache() {
        echo '<p>' . esc_html__( 'These settings enable you to manage Edge Cache.', 'pressable_cache_management' ) . '</p>';
    }
}

if ( ! function_exists( 'pressable_cache_management_callback_section_cache' ) ) {
    function pressable_cache_management_callback_section_cache() {
        echo '<p>' . esc_html__( 'These settings enable you to manage the object cache.', 'pressable_cache_management' ) . '</p>';
    }
}
