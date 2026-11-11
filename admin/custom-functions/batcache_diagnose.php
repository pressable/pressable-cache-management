<?php
/**
 * Pressable Cache Management - Batcache diagnosis (admin side)
 *
 * When the status check finds Batcache is not caching, these helpers work out why and who:
 *  - pcm_batcache_run_trace()      one traced request that names the plugin/theme behind each
 *                                  cookie, cache-blocking header or batcache_cancel() call
 *                                  (see batcache_trace.php), and reads Batcache's configuration.
 *  - pcm_batcache_config_issues()  Batcache switched off or replaced (WP_CACHE, drop-in, max_age).
 *  - pcm_batcache_search_code()    fallback: find a cookie name in the active plugins and theme.
 *  - pcm_batcache_cache_plugins()  third-party page cache plugins that are active.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Why Batcache cannot run at all, as a list of codes; empty when it is loaded and enabled.
 * $env comes from the traced request (see PCM_Batcache_Trace::finish()), because only a
 * visitor's request shows the real configuration.
 */
function pcm_batcache_config_issues( $env ) {
    if ( ! is_array( $env ) || ! isset( $env['wp_cache'] ) ) {
        return array();
    }
    if ( ! $env['wp_cache'] ) {
        return array( 'wp_cache_off' );
    }
    if ( empty( $env['batcache'] ) ) {
        return array( 'no_batcache' );
    }
    if ( isset( $env['max_age'] ) && $env['max_age'] < 1 ) {
        return array( 'max_age_zero' );
    }
    return array();
}

// Active plugins that ship their own page cache; these do not work alongside Batcache and Edge Cache.
function pcm_batcache_cache_plugins() {
    $known = array(
        'wp-rocket'              => 'WP Rocket',
        'w3-total-cache'         => 'W3 Total Cache',
        'litespeed-cache'        => 'LiteSpeed Cache',
        'wp-super-cache'         => 'WP Super Cache',
        'wp-fastest-cache'       => 'WP Fastest Cache',
        'hummingbird-performance' => 'Hummingbird',
        'cache-enabler'          => 'Cache Enabler',
        'comet-cache'            => 'Comet Cache',
        'breeze'                 => 'Breeze',
        'sg-cachepress'          => 'SiteGround Optimizer',
        'swift-performance-lite' => 'Swift Performance',
        'swift-performance'      => 'Swift Performance',
        'wp-optimize'            => 'WP-Optimize',
        'nitropack'              => 'NitroPack',
        'easy-optimizer'         => 'Easy Optimizer',
        'hyper-cache'            => 'Hyper Cache',
        'powered-cache'          => 'Powered Cache',
    );
    $found = array();
    foreach ( (array) get_option( 'active_plugins', array() ) as $plugin ) {
        $slug = strtok( $plugin, '/' );
        if ( isset( $known[ $slug ] ) ) {
            $found[ $slug ] = $known[ $slug ];
        }
    }
    return array_values( $found );
}

/**
 * Which plugin, theme or must-use plugin a file belongs to.
 * Returns array( kind, slug, name, path ) or null for WordPress core and anything else.
 */
function pcm_batcache_component( $file ) {
    if ( ! $file || ! preg_match( '#/(mu-plugins|plugins|themes)/([^/]+)(/.*)?$#', wp_normalize_path( $file ), $m ) ) {
        return null;
    }
    $kind = 'themes' === $m[1] ? 'theme' : ( 'plugins' === $m[1] ? 'plugin' : 'mu-plugin' );
    $slug = $m[2];
    $rest = isset( $m[3] ) ? $m[3] : '';
    $name = $slug;
    if ( 'plugin' === $kind ) {
        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        foreach ( get_plugins() as $path => $data ) {
            if ( $path === $slug || 0 === strpos( $path, $slug . '/' ) ) {
                $name = $data['Name'];
                break;
            }
        }
    } elseif ( 'theme' === $kind ) {
        $theme = wp_get_theme( $slug );
        if ( $theme->exists() ) {
            $name = $theme->get( 'Name' );
        }
    }
    return array( 'kind' => $kind, 'slug' => $slug, 'name' => $name, 'path' => $slug . $rest );
}

/**
 * Make one traced request to the homepage. Returns array( 'found' => ..., 'env' => ... ) or null if
 * the trace did not run. 'found' is what blocks caching and where it comes from:
 * array( signal key => array( label, how, hook, component|null, line, function, candidates ) ) with
 * signal keys "cookie:<name>", "header:cache-control", "cancel". 'env' is the visitor's Batcache
 * configuration for pcm_batcache_config_issues().
 */
function pcm_batcache_run_trace() {
    $token = wp_generate_password( 32, false );
    set_transient( 'pcm_trace_token', $token, MINUTE_IN_SECONDS );
    delete_transient( 'pcm_batcache_trace' );

    // The "wp" cookie prefix makes the edge and Batcache pass the request straight to PHP.
    wp_remote_get( home_url( '/' ), array(
        'timeout'     => 20,
        'redirection' => 3,
        'user-agent'  => 'Pressable-Cache-Management batcache-trace',
        'headers'     => array( 'Accept' => 'text/html' ),
        'cookies'     => array( new WP_Http_Cookie( array( 'name' => 'wp_pcm_trace', 'value' => $token ) ) ),
    ) );

    $trace = get_transient( 'pcm_batcache_trace' );
    delete_transient( 'pcm_trace_token' );
    delete_transient( 'pcm_batcache_trace' );
    if ( ! is_array( $trace ) || empty( $trace['token'] ) || ! hash_equals( $token, (string) $trace['token'] ) ) {
        return null;
    }

    $out = array();
    foreach ( (array) $trace['found'] as $key => $f ) {
        $item = array(
            'label'      => isset( $f['label'] ) ? (string) $f['label'] : '',
            'how'        => isset( $f['how'] ) ? (string) $f['how'] : '',
            'hook'       => isset( $f['hook'] ) ? (string) $f['hook'] : '',
            'line'       => isset( $f['line'] ) ? (int) $f['line'] : 0,
            'function'   => isset( $f['function'] ) ? (string) $f['function'] : '',
            'component'  => pcm_batcache_component( isset( $f['file'] ) ? $f['file'] : '' ),
            'candidates' => array(),
        );
        // Set while the theme's functions.php was loading.
        if ( ! $item['component'] && 'stack' === $item['how'] && 'after_setup_theme' === $item['hook'] ) {
            $item['component'] = pcm_batcache_component( get_stylesheet_directory() . '/functions.php' );
        }
        // Set by an output-buffer callback: the plugins that own the buffers are the candidates.
        if ( 'ob' === $item['how'] ) {
            $item['component'] = null;
            foreach ( isset( $f['ob'] ) ? (array) $f['ob'] : array() as $handler ) {
                $c = pcm_batcache_component( isset( $handler['file'] ) ? $handler['file'] : '' );
                if ( $c && 'pressable-cache-management' !== $c['slug'] ) {
                    $item['candidates'][ $c['kind'] . ':' . $c['slug'] ] = $c + array( 'line' => isset( $handler['line'] ) ? (int) $handler['line'] : 0 );
                }
            }
            $item['candidates'] = array_values( $item['candidates'] );
            if ( 1 === count( $item['candidates'] ) ) {
                $item['component'] = $item['candidates'][0];
                $item['line']      = $item['candidates'][0]['line'];
            }
        }
        $out[ $key ] = $item;
    }
    return array( 'found' => $out, 'env' => isset( $trace['env'] ) ? $trace['env'] : null );
}

/**
 * Fallback when the trace cannot name the source of a cookie: look for the cookie name as a
 * string in the active plugins, the theme and must-use plugins. Returns up to three
 * components (kind, slug, name, path, line), files that also call setcookie() first.
 *
 * ponytail: plain file scan capped at 4 seconds and 800 KB per file; results are cached for
 * an hour per cookie name. Network-activated plugins on multisite are not scanned.
 */
function pcm_batcache_search_code( $cookie ) {
    $cache_key = 'pcm_cookie_src_' . md5( $cookie );
    $cached    = get_transient( $cache_key );
    if ( is_array( $cached ) ) {
        return $cached;
    }

    // Names such as wp_woocommerce_session_<hash> are built at runtime: search for the fixed part.
    $stem = preg_replace( '/[_-]?[0-9a-f]{8,}$/i', '', $cookie );
    if ( 0 === strcasecmp( $cookie, 'PHPSESSID' ) ) {
        $needles = array( 'session_start(' );
    } elseif ( strlen( $stem ) < 3 ) {
        return array();
    } elseif ( $stem === $cookie ) {
        $needles = array( "'" . $cookie . "'", '"' . $cookie . '"' );
    } else {
        $needles = array( "'" . $stem, '"' . $stem );
    }

    $paths = array();
    foreach ( (array) get_option( 'active_plugins', array() ) as $plugin ) {
        if ( 0 === strpos( $plugin, 'pressable-cache-management/' ) ) {
            continue;
        }
        $paths[] = false === strpos( $plugin, '/' ) ? WP_PLUGIN_DIR . '/' . $plugin : WP_PLUGIN_DIR . '/' . dirname( $plugin );
    }
    $paths[] = get_stylesheet_directory();
    $paths[] = get_template_directory();
    $paths[] = WPMU_PLUGIN_DIR;

    $hits     = array();
    $deadline = microtime( true ) + 4;
    foreach ( array_unique( $paths ) as $path ) {
        if ( is_file( $path ) ) {
            $files = array( new SplFileInfo( $path ) );
        } elseif ( is_dir( $path ) ) {
            try {
                $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $path, FilesystemIterator::SKIP_DOTS ) );
            } catch ( Exception $e ) {
                continue;
            }
        } else {
            continue;
        }
        foreach ( $files as $file ) {
            if ( microtime( true ) > $deadline ) {
                break 2;
            }
            if ( 'php' !== strtolower( $file->getExtension() ) || $file->getSize() > 800000 || false !== strpos( $file->getPathname(), '/pressable-cache-management/' ) ) {
                continue;
            }
            $code = @file_get_contents( $file->getPathname() );
            if ( ! $code ) {
                continue;
            }
            foreach ( $needles as $needle ) {
                $pos = strpos( $code, $needle );
                if ( false === $pos ) {
                    continue;
                }
                $c = pcm_batcache_component( $file->getPathname() );
                if ( $c ) {
                    $id = $c['kind'] . ':' . $c['slug'];
                    $c += array( 'line' => substr_count( $code, "\n", 0, $pos ) + 1, 'sets' => false !== stripos( $code, 'setcookie' ) || false !== stripos( $code, 'session_start' ) );
                    // Keep one file per component, preferring a file that itself sets cookies.
                    if ( ! isset( $hits[ $id ] ) || ( $c['sets'] && ! $hits[ $id ]['sets'] ) ) {
                        $hits[ $id ] = $c;
                    }
                }
                break;
            }
        }
    }

    $hits = array_values( $hits );
    usort( $hits, function( $a, $b ) {
        return (int) $b['sets'] - (int) $a['sets'];
    } );
    $hits = array_slice( $hits, 0, 3 );
    set_transient( $cache_key, $hits, HOUR_IN_SECONDS );
    return $hits;
}

// "Name (plugin) — path/file.php:123", escaped.
function pcm_batcache_component_html( $c ) {
    $kinds = array(
        'plugin'    => __( 'plugin', 'pressable_cache_management' ),
        'theme'     => __( 'theme', 'pressable_cache_management' ),
        'mu-plugin' => __( 'must-use plugin', 'pressable_cache_management' ),
    );
    $html = '<span class="pcm-bc-source">' . esc_html( $c['name'] ) . '</span> (' . esc_html( $kinds[ $c['kind'] ] ) . ')';
    if ( ! empty( $c['path'] ) && $c['path'] !== $c['slug'] ) {
        $html .= ' &mdash; <code class="pcm-bc-file">' . esc_html( $c['path'] . ( ! empty( $c['line'] ) ? ':' . $c['line'] : '' ) ) . '</code>';
    }
    return $html;
}

/**
 * One sentence saying where a blocker comes from, escaped HTML. $trace is one item from
 * pcm_batcache_run_trace() or null; $code is the result of pcm_batcache_search_code() or empty.
 */
function pcm_batcache_source_html( $trace, $code = array() ) {
    if ( $trace && $trace['component'] ) {
        $c    = $trace['component'] + array( 'line' => $trace['line'] );
        $html = esc_html__( 'Source:', 'pressable_cache_management' ) . ' ' . pcm_batcache_component_html( $c );
        if ( 'callback' === $trace['how'] && $trace['function'] ) {
            /* translators: 1: function name, 2: hook name */
            $html .= ' ' . sprintf( esc_html__( 'in %1$s on the %2$s hook.', 'pressable_cache_management' ), '<code class="pcm-bc-file">' . esc_html( $trace['function'] ) . '()</code>', '<code class="pcm-bc-file">' . esc_html( $trace['hook'] ) . '</code>' );
        } elseif ( 'loading' === $trace['how'] ) {
            $html .= ' ' . esc_html__( 'while the plugin was loading.', 'pressable_cache_management' );
        } elseif ( 'ob' === $trace['how'] ) {
            $html .= ' ' . esc_html__( 'from its output buffer, as the page was sent.', 'pressable_cache_management' );
        } else {
            $html .= ' ' . esc_html__( '(most likely: this code was running when it appeared).', 'pressable_cache_management' );
        }
        return $html . ' <span class="pcm-bc-how">' . esc_html__( 'Found by tracing a live request.', 'pressable_cache_management' ) . '</span>';
    }

    $candidates = $trace && $trace['candidates'] ? $trace['candidates'] : $code;
    if ( $candidates ) {
        $intro = $trace && $trace['candidates']
            ? esc_html__( 'Sent from an output buffer owned by one of:', 'pressable_cache_management' )
            : esc_html__( 'The name appears in the code of:', 'pressable_cache_management' );
        return $intro . ' ' . implode( '; ', array_map( 'pcm_batcache_component_html', $candidates ) ) . '.';
    }

    if ( $trace && 'early' === $trace['how'] ) {
        return esc_html__( 'Source: set before plugins finished loading, so it comes from a plugin\'s main file, a must-use plugin or wp-config.php.', 'pressable_cache_management' );
    }
    return '';
}
