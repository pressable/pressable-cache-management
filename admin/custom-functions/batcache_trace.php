<?php
/**
 * Pressable Cache Management - Batcache diagnostic tracer
 *
 * Finds WHICH plugin, theme or code blocks page caching. It only runs on the single
 * diagnostic request the status check makes when Batcache is not working (see
 * pcm_batcache_run_trace() in batcache_diagnose.php). That request carries a one-time
 * token in a "wp_pcm_trace" cookie; the "wp" prefix makes the edge and Batcache skip
 * the request, so it always reaches PHP and is never stored in any cache.
 *
 * Every other request leaves this file at the first line below: the cost is one isset().
 *
 * How it attributes: after every hook callback it looks for something new that blocks
 * caching (a Set-Cookie header, a Cache-Control header with no-store / no-cache /
 * private / max-age=0, or batcache_cancel()). The callback that just ran is the source.
 * Callbacks are not wrapped or changed; a tiny check is placed after each one.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $_COOKIE['wp_pcm_trace'] ) || ! is_string( $_COOKIE['wp_pcm_trace'] ) ) {
    return;
}

$pcm_trace_token = get_transient( 'pcm_trace_token' );
if ( ! is_string( $pcm_trace_token ) || '' === $pcm_trace_token || ! hash_equals( $pcm_trace_token, $_COOKIE['wp_pcm_trace'] ) ) {
    unset( $pcm_trace_token );
    return;
}

// Placed after each hook callback; passes filter values through untouched.
class PCM_Trace_Check {
    private $trace;
    private $hook;
    private $cb;

    public function __construct( $trace, $hook, $cb ) {
        $this->trace = $trace;
        $this->hook  = $hook;
        $this->cb    = $cb;
    }

    public function __invoke( $value = null ) {
        $this->trace->check( array( 'how' => 'callback', 'hook' => $this->hook, 'cb' => $this->cb ) );
        return $value;
    }
}

class PCM_Batcache_Trace {
    private $token;
    private $seen   = array(); // signal key => true
    private $found  = array(); // signal key => finding
    private $paired = array(); // hook => callback count when its checks were last placed
    private $ob     = array(); // output-buffer handlers active when the page finished

    public function __construct( $token ) {
        $this->token = $token;
        // Anything already there was set before this plugin loaded (an earlier plugin's
        // main file, a must-use plugin or wp-config.php).
        foreach ( $this->signals() as $key => $label ) {
            $this->seen[ $key ]  = true;
            $this->found[ $key ] = array( 'label' => $label, 'how' => 'early' );
        }
        add_action( 'all', array( $this, 'on_hook' ) );
        add_action( 'shutdown', array( $this, 'finish' ), PHP_INT_MAX );
    }

    // What currently blocks caching: signal key => label.
    private function signals() {
        $s = array();
        foreach ( headers_list() as $h ) {
            if ( 0 === stripos( $h, 'Set-Cookie:' ) ) {
                $name = trim( (string) strtok( substr( $h, 11 ), '=' ) );
                if ( '' !== $name ) {
                    $s[ 'cookie:' . $name ] = $name;
                }
            } elseif ( 0 === stripos( $h, 'Cache-Control:' ) && preg_match( '/no-store|no-cache|private|max-?age=0\b/i', $h ) ) {
                $s['header:cache-control'] = trim( substr( $h, 14 ) );
            }
        }
        if ( isset( $GLOBALS['batcache'] ) && is_object( $GLOBALS['batcache'] ) && ! empty( $GLOBALS['batcache']->cancel ) ) {
            $s['cancel'] = 'batcache_cancel()';
        }
        return $s;
    }

    public function check( $ctx ) {
        try {
            foreach ( $this->signals() as $key => $label ) {
                if ( isset( $this->seen[ $key ] ) ) {
                    continue;
                }
                $this->seen[ $key ]  = true;
                $this->found[ $key ] = array( 'label' => $label ) + $this->describe( $ctx );
            }
        } catch ( Throwable $e ) {
            // Diagnostics must never break the page.
        }
    }

    private function describe( $ctx ) {
        $out = array( 'how' => $ctx['how'], 'hook' => isset( $ctx['hook'] ) ? $ctx['hook'] : '' );
        if ( 'callback' === $ctx['how'] ) {
            $out += self::where( $ctx['cb'] );
            // WordPress flushing the output buffers: the header came from a buffer callback.
            if ( isset( $out['function'] ) && 'wp_ob_end_flush_all' === $out['function'] ) {
                $out['how'] = 'ob';
            }
        } elseif ( ! empty( $ctx['file'] ) ) {
            $out['file'] = $ctx['file'];
        } else {
            // Set outside a callback we watch: the innermost plugin/theme code still running is the likely source.
            foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 40 ) as $frame ) {
                if ( empty( $frame['file'] ) || __FILE__ === $frame['file'] ) {
                    continue;
                }
                if ( preg_match( '#/(mu-plugins|plugins|themes)/#', $frame['file'] ) && false === strpos( $frame['file'], '/pressable-cache-management/' ) ) {
                    $out['file'] = $frame['file'];
                    $out['line'] = isset( $frame['line'] ) ? $frame['line'] : 0;
                    break;
                }
            }
        }
        return $out;
    }

    // File, line and name of a callback.
    public static function where( $cb ) {
        try {
            if ( is_string( $cb ) && false !== strpos( $cb, '::' ) ) {
                $cb = explode( '::', $cb, 2 );
            }
            if ( is_array( $cb ) ) {
                $ref  = new ReflectionMethod( $cb[0], $cb[1] );
                $name = ( is_object( $cb[0] ) ? get_class( $cb[0] ) : $cb[0] ) . '::' . $cb[1];
            } elseif ( is_object( $cb ) && ! $cb instanceof Closure ) {
                $ref  = new ReflectionMethod( $cb, '__invoke' );
                $name = get_class( $cb );
            } else {
                $ref  = new ReflectionFunction( $cb );
                $name = $cb instanceof Closure ? 'closure' : (string) $cb;
            }
            return array( 'file' => (string) $ref->getFileName(), 'line' => (int) $ref->getStartLine(), 'function' => $name );
        } catch ( Throwable $e ) {
            return array();
        }
    }

    // Runs before the callbacks of every hook.
    public function on_hook( ...$args ) {
        global $wp_filter;
        $hook = $args[0];

        // Something appeared between callbacks (a plugin's main file loading, template code).
        $this->check( array(
            'how'  => 'plugin_loaded' === $hook ? 'loading' : 'stack',
            'hook' => $hook,
            'file' => 'plugin_loaded' === $hook && isset( $args[1] ) && is_string( $args[1] ) ? $args[1] : '',
        ) );

        if ( 'shutdown' === $hook && ! $this->ob ) {
            foreach ( ob_list_handlers() as $handler ) {
                if ( 'default output handler' !== $handler && 0 !== stripos( $handler, 'batcache' ) ) {
                    $this->ob[] = array( 'function' => $handler ) + self::where( $handler );
                }
            }
        }

        if ( empty( $wp_filter[ $hook ] ) || ! is_object( $wp_filter[ $hook ] ) || ! isset( $wp_filter[ $hook ]->callbacks ) ) {
            return;
        }
        $h = $wp_filter[ $hook ];
        $n = count( $h->callbacks, COUNT_RECURSIVE );
        if ( isset( $this->paired[ $hook ] ) && $this->paired[ $hook ] === $n ) {
            return;
        }
        // Put a check right after every callback. The callbacks themselves are left exactly as they are.
        foreach ( $h->callbacks as $priority => $callbacks ) {
            $new = array();
            foreach ( $callbacks as $id => $cb ) {
                if ( $cb['function'] instanceof PCM_Trace_Check ) {
                    continue;
                }
                $new[ $id ]            = $cb;
                $new[ $id . "\0pcm" ] = array( 'function' => new PCM_Trace_Check( $this, $hook, $cb['function'] ), 'accepted_args' => 1 );
            }
            $h->callbacks[ $priority ] = $new;
        }
        $this->paired[ $hook ] = count( $h->callbacks, COUNT_RECURSIVE );
    }

    // After the output buffers are flushed: keep only what still blocks caching and store it.
    public function finish() {
        try {
            $this->check( array( 'how' => 'ob', 'hook' => 'shutdown' ) );
            $found = array_intersect_key( $this->found, $this->signals() );
            foreach ( $found as $key => $f ) {
                if ( 'ob' === $f['how'] ) {
                    $found[ $key ]['ob'] = $this->ob;
                }
            }
            // Batcache's configuration as an anonymous visitor gets it. It cannot be read in
            // wp-admin: the platform turns WP_CACHE off for staff-proxied admin sessions.
            $bc  = isset( $GLOBALS['batcache'] ) && is_object( $GLOBALS['batcache'] ) ? $GLOBALS['batcache'] : null;
            $env = array(
                'wp_cache' => defined( 'WP_CACHE' ) && WP_CACHE,
                'batcache' => $bc && function_exists( 'batcache_cancel' ),
                'max_age'  => $bc && isset( $bc->max_age ) ? (int) $bc->max_age : null,
            );
            set_transient( 'pcm_batcache_trace', array( 'token' => $this->token, 'found' => $found, 'env' => $env ), 2 * MINUTE_IN_SECONDS );
        } catch ( Throwable $e ) {
            // Nothing to report.
        }
    }
}

new PCM_Batcache_Trace( $pcm_trace_token );
unset( $pcm_trace_token );
