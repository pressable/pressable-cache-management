<?php
/**
 * Pressable Cache Management - Settings Page (Redesigned v3)
 * Fixes: double notices, visible timestamps, red→green hover button,
 *        correct feature list matching official repo, branded notices.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── Kill WP's default "Settings saved." on our page – one branded notice only ──
// Priority 0 runs before settings_errors (priority 10), so we can remove it first.
add_action( 'admin_notices', 'pcm_kill_default_settings_notice', 0 );
function pcm_kill_default_settings_notice() {
    if ( ! isset( $_GET['page'] ) || sanitize_key( $_GET['page'] ) !== 'pressable_cache_management' ) return;
    remove_action( 'admin_notices', 'settings_errors', 10 );
}

add_action( 'admin_notices', 'pcm_branded_settings_saved_notice', 5 );
function pcm_branded_settings_saved_notice() {
    if ( ! isset( $_GET['page'] ) || sanitize_key( $_GET['page'] ) !== 'pressable_cache_management' ) return;
    if ( ! isset( $_GET['settings-updated'] ) || sanitize_key( $_GET['settings-updated'] ) !== 'true' ) return;

    $wrap = 'display:inline-flex;align-items:center;justify-content:space-between;gap:24px;'
          . 'border-left:4px solid #03fcc2;background:#fff;border-radius:0 8px 8px 0;'
          . 'padding:12px 16px;box-shadow:0 2px 8px rgba(4,0,36,.07);'
          . 'margin:10px 0 10px 8px;font-family:sans-serif;min-width:260px;max-width:480px;';
    $btn  = 'background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;line-height:1;padding:0;flex-shrink:0;';
    $id   = 'pcm-settings-saved-notice';
    echo '<div id="' . $id . '" style="' . $wrap . '">';
    echo '<p style="margin:0;font-size:13px;color:#040024;">'
       . esc_html__( 'Cache settings updated.', 'pressable_cache_management' ) . '</p>';
    echo '<button type="button" onclick="document.getElementById(\'' . $id . '\').remove();" style="' . $btn . '">&#x2297;</button>';
    echo '</div>';
}

// ─── "Extending Batcache" notice — shows ONCE after first enable, then never again ─
// extend_batcache.php sets 'pcm_extend_batcache_notice_pending' only when it copies
// the mu-plugin for the first time (fresh enable). We delete the flag here immediately
// after rendering, so any subsequent page load or refresh will never see it again.
add_action( 'admin_notices', 'pcm_extend_batcache_branded_notice' );
function pcm_extend_batcache_branded_notice() {
    if ( ! isset( $_GET['page'] ) || sanitize_key( $_GET['page'] ) !== 'pressable_cache_management' ) return;
    if ( ! current_user_can( 'manage_options' ) ) return;

    // Only show if freshly enabled
    if ( '1' !== get_option( 'pcm_extend_batcache_notice_pending' ) ) return;

    // Delete flag IMMEDIATELY — refresh / navigation will never trigger this again
    delete_option( 'pcm_extend_batcache_notice_pending' );

    $wrap = 'display:flex;align-items:center;justify-content:space-between;gap:12px;'
          . 'border-left:4px solid #03fcc2;background:#fff;border-radius:0 8px 8px 0;'
          . 'padding:14px 18px;box-shadow:0 2px 8px rgba(4,0,36,.07);'
          . 'margin:10px 0;font-family:sans-serif;';
    $btn  = 'background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;line-height:1;padding:0;';
    echo '<div style="max-width:1120px;margin:0 auto;padding:0 20px;box-sizing:border-box;">';
    echo '<div id="pcm-extend-batcache-notice" style="' . $wrap . '">';
    echo '<p style="margin:0;font-size:13px;color:#040024;">'
       . esc_html__( 'Extending Batcache for 24 hours — see ', 'pressable_cache_management' )
       . '<a href="https://pressable.com/knowledgebase/modifying-cache-times-batcache/" target="_blank" rel="noopener noreferrer" '
       . 'style="color:#dd3a03;font-weight:600;text-decoration:none;">'
       . esc_html__( 'Modifying Batcache Times.', 'pressable_cache_management' ) . '</a>'
       . '</p>';
    echo '<button type="button" onclick="document.getElementById(\'pcm-extend-batcache-notice\').remove();" '
       . 'style="' . $btn . '">&#x2297;</button>';
    echo '</div>';
    echo '</div>';
}

// ─── Helper: pcm_branded_notice (shared, safe) ───────────────────────────────
if ( ! function_exists( 'pcm_branded_notice' ) ) {
    function pcm_branded_notice( $message, $border_color = '#03fcc2', $is_html = false ) {
        $id   = 'pcm-notice-' . substr( md5( $message . $border_color . microtime() ), 0, 8 );
        $wrap = 'display:inline-flex;align-items:flex-start;justify-content:space-between;gap:16px;'
              . 'border-left:4px solid ' . esc_attr( $border_color ) . ';background:#fff;'
              . 'border-radius:0 8px 8px 0;padding:14px 18px;'
              . 'box-shadow:0 2px 8px rgba(4,0,36,.07);margin:10px 0 10px 8px;font-family:sans-serif;min-width:260px;max-width:480px;';
        $btn  = 'background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;'
              . 'line-height:1;padding:0;flex-shrink:0;margin-top:2px;';
        echo '<div id="' . esc_attr( $id ) . '" style="' . $wrap . '"><div style="flex:1;">';
        if ( $is_html ) {
            echo $message;
        } else {
            echo '<p style="margin:0;font-size:13px;color:#040024;">' . esc_html( $message ) . '</p>';
        }
        echo '</div><button type="button" onclick="document.getElementById(\'' . esc_js( $id ) . '\').remove();" style="' . $btn . '">&#x2297;</button></div>';
    }
}

// ─── Batcache status check ───────────────────────────────────────────────────
// HOW IT WORKS: PHP requests the homepage from the server (wp_remote_get) and reads
// the response headers. Batcache is advanced-cache.php, i.e. it runs inside PHP, so a
// loopback request is handled by it exactly like a visitor's request.
//
// Each request carries a unique fbclid[]=... query value. The Pressable edge cache
// strips a plain fbclid= but not the array form, so it misses and forwards every
// request to origin; Batcache strips any fbclid key ($ignored_query_args), so what
// gets tested is the real homepage entry and WordPress sees no other query args
// (unknown parameters make some plugins set tracking cookies). Without this the edge
// can hold a copy of a Batcache MISS (no x-nananana) for up to a day and every probe
// reads that copy — the previous browser-side fetch() reported "Batcache Broken" for
// exactly that reason.
//
// Batcache only stores a URL on its 2nd request within 120 s ($times = 2), so up to
// three requests are made, stopping at the first x-nananana (Batcache-Set or -Hit).
// Server-side is also the only place Set-Cookie response headers can be read
// (fetch() hides them): Batcache never stores a response that sets a cookie, so those
// cookie names are the "culprits" and are shown under the badge.
//
// Cost: at most three homepage requests, only when an admin opens this page or clicks
// refresh, and never more than once a minute (results are stored in a transient).

function pcm_get_batcache_status() {
    $r = get_transient( 'pcm_batcache_status' );
    if ( is_array( $r ) && isset( $r['status'] ) ) {
        return $r;
    }
    return array( 'status' => 'unknown', 'cookies' => array(), 'max_age' => 0, 'checked' => 0, 'error' => '' );
}

function pcm_batcache_status_labels() {
    return array(
        'active'     => __( 'Batcache Active',             'pressable_cache_management' ),
        'cookies'    => __( 'Batcache Blocked by Cookies', 'pressable_cache_management' ),
        'cloudflare' => __( 'Cloudflare Detected',         'pressable_cache_management' ),
        'broken'     => __( 'Batcache Broken',             'pressable_cache_management' ),
        'unknown'    => __( 'Status Unavailable',          'pressable_cache_management' ),
        'excluded'   => __( 'Homepage Excluded',           'pressable_cache_management' ),
    );
}

// Known cookie-name prefixes (matched case-insensitively): where the cookie comes
// from and what to do about it. Only cookies sent as Set-Cookie HTTP headers block
// Batcache; cookies set by JavaScript never do.
function pcm_batcache_cookie_hint( $name ) {
    static $hints = null;
    if ( null === $hints ) {
        $one_at_a_time = __( 'Deactivate plugins one at a time and click the refresh button on the badge after each one to find it.', 'pressable_cache_management' );
        $consent       = __( 'Consent cookies should be set by JavaScript. Update the plugin and look in its settings for a PHP or server-side cookie option; if the cookie is still sent as a header, deactivate the plugin and re-check.', 'pressable_cache_management' );
        $wpml          = __( 'In WPML > Languages, turn off the browser-language redirect and the language cookie used for AJAX filtering, then re-check. See the Pressable guide "Allowing Caching for WPML and WCML on Pressable".', 'pressable_cache_management' );
        $hints = array(
            'phpsessid'               => array( __( 'PHP session (session_start())', 'pressable_cache_management' ), __( 'A plugin or theme starts a PHP session on every page.', 'pressable_cache_management' ) . ' ' . $one_at_a_time . ' ' . __( 'If the plugin is needed, ask its developer to start the session only on the pages that use it.', 'pressable_cache_management' ) ),
            'wp_woocommerce_session_' => array( __( 'WooCommerce session', 'pressable_cache_management' ), __( 'WooCommerce normally sets this cookie only once a visitor has something in the cart. A plugin or theme (often geolocation, currency, wishlist or "recently viewed" features) is forcing a session for every visitor, and visitors holding this cookie bypass Batcache as well.', 'pressable_cache_management' ) . ' ' . $one_at_a_time ),
            'woocommerce_'            => array( __( 'WooCommerce', 'pressable_cache_management' ), __( 'Set when the cart or recently viewed products are touched while the page loads. A plugin or theme is doing that for every visitor.', 'pressable_cache_management' ) . ' ' . $one_at_a_time ),
            'pll_language'            => array( __( 'Polylang', 'pressable_cache_management' ), __( "Polylang sets its language cookie on every page. Add define( 'PLL_COOKIE', false ); to wp-config.php to stop it (browser language detection stops too), then re-check.", 'pressable_cache_management' ) ),
            '_icl_'                   => array( __( 'WPML', 'pressable_cache_management' ), $wpml ),
            'wp-wpml_'                => array( __( 'WPML', 'pressable_cache_management' ), $wpml ),
            'wfvt_'                   => array( __( 'Wordfence Live Traffic', 'pressable_cache_management' ), __( 'In Wordfence > All Options > Live Traffic, set logging to "Security only", then re-check.', 'pressable_cache_management' ) ),
            'yith_wcwl_'              => array( __( 'YITH WooCommerce Wishlist', 'pressable_cache_management' ), __( 'The wishlist starts a session for every visitor. Deactivate it, or ask YITH about setting the cookie only when a wishlist is used, then re-check.', 'pressable_cache_management' ) ),
            'mailchimp'               => array( __( 'Mailchimp for WooCommerce', 'pressable_cache_management' ), __( 'Older versions set a landing-site cookie for every new visitor from PHP. Update the plugin; if the cookie is still sent, deactivate it and re-check.', 'pressable_cache_management' ) ),
            'wordpress_test_cookie'   => array( __( 'WordPress login test cookie', 'pressable_cache_management' ), __( 'Normally only the login page sets this. A plugin is rendering a login form or setting it on every page.', 'pressable_cache_management' ) . ' ' . $one_at_a_time ),
            'cookielawinfo'           => array( __( 'CookieYes / GDPR Cookie Consent', 'pressable_cache_management' ), $consent ),
            'moove_gdpr'              => array( __( 'GDPR Cookie Compliance', 'pressable_cache_management' ), $consent ),
            'tk_ai'                   => array( __( 'Jetpack / WooCommerce Tracks', 'pressable_cache_management' ), __( 'Normally set by JavaScript. Sent as a header, it means a plugin sets it from PHP.', 'pressable_cache_management' ) . ' ' . $one_at_a_time ),
        );
    }
    $lower = strtolower( $name );
    foreach ( $hints as $prefix => $hint ) {
        if ( 0 === strpos( $lower, $prefix ) ) {
            return array( 'source' => $hint[0], 'fix' => $hint[1] );
        }
    }
    return array(
        'source' => '',
        'fix'    => __( 'Search your plugins and theme (wp-content) for this cookie name and for setcookie() or session_start() calls.', 'pressable_cache_management' ) . ' ' . $one_at_a_time,
    );
}

// True when "/" is in the Exclude Pages from Batcache setting: the homepage is skipped on purpose.
function pcm_batcache_home_excluded() {
    $opts = get_option( 'pressable_cache_management_options' );
    $list = is_array( $opts ) && ! empty( $opts['exempt_from_batcache'] ) ? $opts['exempt_from_batcache'] : '';
    foreach ( array_map( 'trim', explode( ',', (string) $list ) ) as $path ) {
        if ( '/' === $path ) {
            return true;
        }
    }
    return false;
}

/**
 * Request the homepage up to three times and classify what Batcache did.
 * Stores and returns array( status, cookies, max_age, checked, error, attempts ).
 */
function pcm_probe_batcache() {
    $result = array( 'status' => 'broken', 'cookies' => array(), 'max_age' => 0, 'checked' => time(), 'error' => '', 'attempts' => 0 );
    $cloudflare     = false;
    $reached_origin = false;
    $ok_responses   = 0;
    $errors         = array();
    $http_codes     = array();

    for ( $i = 1; $i <= 3; $i++ ) {
        // fbclid[] (array form): the edge does not recognise it as fbclid, so every request
        // misses the edge and reaches origin; Batcache strips any 'fbclid' key, so the real
        // homepage entry is tested. A plain fbclid=... is stripped by the edge too, so only the
        // first request would reach origin and the rest would read the edge copy of it.
        $home = home_url( '/' );
        $url  = $home . ( false === strpos( $home, '?' ) ? '?' : '&' ) . 'fbclid%5B%5D=pcm-probe-' . wp_generate_password( 12, false );
        $resp = wp_remote_get( $url, array(
            'timeout'     => 15,
            'redirection' => 3,
            'cookies'     => array(),
            'user-agent'  => 'Pressable-Cache-Management batcache-probe',
            'headers'     => array( 'Accept' => 'text/html' ),
        ) );
        $result['attempts'] = $i;

        if ( is_wp_error( $resp ) ) {
            $errors[] = $resp->get_error_message();
            continue;
        }

        $nananana = strtolower( (string) wp_remote_retrieve_header( $resp, 'x-nananana' ) );
        $x_ac     = strtolower( (string) wp_remote_retrieve_header( $resp, 'x-ac' ) );
        $server   = strtolower( (string) wp_remote_retrieve_header( $resp, 'server' ) );
        $cloudflare = $cloudflare || false !== strpos( $server, 'cloudflare' ) || '' !== (string) wp_remote_retrieve_header( $resp, 'cf-ray' );

        if ( preg_match( '/max-age=(\d+)/i', (string) wp_remote_retrieve_header( $resp, 'cache-control' ), $m ) ) {
            $result['max_age'] = (int) $m[1];
        }
        foreach ( wp_remote_retrieve_cookies( $resp ) as $cookie ) {
            if ( is_object( $cookie ) && ! empty( $cookie->name ) ) {
                $result['cookies'][] = $cookie->name;
            }
        }

        if ( false !== strpos( $nananana, 'batcache' ) ) {
            $result['status'] = 'active';
            break;
        }

        // Error pages (maintenance, coming soon, an edge challenge) are never cached and say nothing about Batcache.
        $code = (int) wp_remote_retrieve_response_code( $resp );
        if ( $code >= 400 ) {
            $http_codes[] = $code;
            continue;
        }
        $ok_responses++;
        // An edge HIT was answered without reaching origin, so it says nothing about Batcache either.
        if ( ! preg_match( '/\bhit\b/', $x_ac ) ) {
            $reached_origin = true;
        }
    }

    $result['cookies'] = array_values( array_unique( $result['cookies'] ) );

    if ( 'active' !== $result['status'] ) {
        if ( ! empty( $result['cookies'] ) ) {
            $result['status'] = 'cookies';
        } elseif ( 0 === $ok_responses && ( $http_codes || $errors ) ) {
            $result['status'] = 'unknown';
            if ( $http_codes ) {
                /* translators: %s: HTTP status code(s) */
                $result['error'] = sprintf( __( 'The homepage answered with HTTP %s instead of a page.', 'pressable_cache_management' ), implode( ' / ', array_unique( $http_codes ) ) );
                if ( 'yes' === get_option( 'edge-cache-defensive-mode-active' ) ) {
                    $result['error'] .= ' ' . __( 'Edge Cache Defensive Mode is on, so the check is being challenged like a visitor; turn it off to check Batcache.', 'pressable_cache_management' );
                }
            } else {
                $result['error'] = implode( '; ', array_unique( $errors ) );
            }
        } elseif ( pcm_batcache_home_excluded() ) {
            $result['status'] = 'excluded';
        } elseif ( $cloudflare ) {
            $result['status'] = 'cloudflare';
        } elseif ( ! $reached_origin ) {
            $result['status'] = 'unknown';
            $result['error']  = __( 'Every response came from the edge cache, so Batcache could not be observed.', 'pressable_cache_management' );
        }
    }

    set_transient( 'pcm_batcache_status', $result, DAY_IN_SECONDS );
    return $result;
}

// Last step of an alert: links to Pressable Support (%1$s) and the feedback form (%2$s). Returns escaped HTML.
function pcm_batcache_alert_help_step( $template ) {
    $link = '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>';
    return sprintf(
        esc_html( $template ),
        sprintf( $link, esc_url( pcm_support_url() ), esc_html__( 'contact Pressable Support', 'pressable_cache_management' ) ),
        sprintf( $link, esc_url( pcm_feedback_url() ), esc_html__( 'send us feedback', 'pressable_cache_management' ) )
    );
}

// The alert panel shown under the header for anything but an active Batcache.
function pcm_batcache_alert_html( $r ) {
    $tone  = 'error';
    $title = '';
    $text  = '';
    $items = array();
    $steps = array();

    switch ( $r['status'] ) {
        case 'cookies':
            $title = __( 'Cookies are blocking Batcache', 'pressable_cache_management' );
            $text  = __( 'When the homepage was requested as a first-time visitor (no cookies), the response set the cookies below. Batcache never stores a response that sets a cookie, so PHP builds these pages for every visitor.', 'pressable_cache_management' );
            foreach ( $r['cookies'] as $name ) {
                $hint    = pcm_batcache_cookie_hint( $name );
                $items[] = '<code>' . esc_html( $name ) . '</code>'
                    . ( $hint['source'] ? ' &mdash; <span class="pcm-bc-source">' . esc_html( $hint['source'] ) . '</span>' : '' )
                    . '<br>' . esc_html( $hint['fix'] );
            }
            $steps = array(
                esc_html__( 'Fix or deactivate what sets each cookie (see above), then click the ↻ button on the badge to re-check.', 'pressable_cache_management' ),
                esc_html__( 'If the plugin is needed, ask its developer to set the cookie with JavaScript or only on the pages that use it. Cookies set by JavaScript do not block Batcache; only Set-Cookie headers sent by PHP do.', 'pressable_cache_management' ),
                esc_html__( 'Logged-in users and shoppers with items in their cart are never served from Batcache; that is expected and not a problem.', 'pressable_cache_management' ),
                pcm_batcache_alert_help_step( __( 'Still stuck? %1$s and include the cookie names above, or %2$s about this check.', 'pressable_cache_management' ) ),
            );
            break;

        case 'broken':
            $title = __( 'Batcache is not caching the homepage', 'pressable_cache_management' );
            $text  = __( 'Three requests as a first-time visitor returned no Batcache header and set no cookies, so something else stops Batcache from storing the page.', 'pressable_cache_management' );
            if ( $r['max_age'] > 0 && $r['max_age'] < 60 ) {
                /* translators: %d: seconds */
                $items[] = esc_html( sprintf( __( 'The response carried Cache-Control max-age=%d, which is too short for Batcache to keep the page.', 'pressable_cache_management' ), $r['max_age'] ) );
            }
            $steps = array(
                esc_html__( 'Look for a plugin or theme that sends Cache-Control: no-cache, private or max-age=0 headers, or calls batcache_cancel(). Caching plugins such as WP Rocket, W3 Total Cache and LiteSpeed Cache should have their page caching disabled on Pressable.', 'pressable_cache_management' ),
                esc_html__( 'Deactivate plugins one at a time and click the ↻ button on the badge after each one.', 'pressable_cache_management' ),
                pcm_batcache_alert_help_step( __( 'If it stays broken, %1$s or %2$s.', 'pressable_cache_management' ) ),
            );
            break;

        case 'excluded':
            $tone  = 'warn';
            $title = __( 'The homepage is excluded from Batcache', 'pressable_cache_management' );
            $text  = __( 'Your Exclude Pages setting lists "/", so Batcache skips the homepage on purpose and the status cannot be checked there. Remove "/" from the exclusions and click the ↻ button to check again.', 'pressable_cache_management' );
            break;

        case 'cloudflare':
            $tone  = 'warn';
            $title = __( 'Cloudflare is in front of the site', 'pressable_cache_management' );
            $text  = __( 'The check sees Cloudflare\'s response, so Batcache headers cannot be observed. Batcache may still work at origin. To verify, pause Cloudflare or add a "Bypass cache" rule for HTML, then click the ↻ button.', 'pressable_cache_management' );
            break;

        case 'unknown':
            if ( empty( $r['error'] ) ) {
                return '';
            }
            $tone  = 'warn';
            $title = __( 'The Batcache status could not be checked', 'pressable_cache_management' );
            $text  = $r['error'] . ' ' . __( 'Click the ↻ button to try again. If it keeps failing, contact Pressable Support.', 'pressable_cache_management' );
            break;

        default:
            return '';
    }

    $html  = '<div class="pcm-bc-alert-title">' . esc_html( $title ) . '</div>';
    $html .= '<p>' . esc_html( $text ) . '</p>';
    if ( $items ) {
        $html .= '<ul><li>' . implode( '</li><li>', $items ) . '</li></ul>';
    }
    if ( $steps ) {
        $html .= '<p class="pcm-bc-alert-next">' . esc_html__( 'What to do next', 'pressable_cache_management' ) . '</p>';
        $html .= '<ol><li>' . implode( '</li><li>', $steps ) . '</li></ol>';
    }
    return '<div class="pcm-bc-alert pcm-bc-alert--' . esc_attr( $tone ) . '">' . $html . '</div>';
}

// What the badge shows for a stored result.
function pcm_batcache_status_payload( $r ) {
    $labels = pcm_batcache_status_labels();
    return array(
        'status'     => $r['status'],
        'label'      => $labels[ $r['status'] ],
        'alert_html' => pcm_batcache_alert_html( $r ),
        'max_age'    => (int) $r['max_age'],
        /* translators: %s: human-readable time difference */
        'checked'    => empty( $r['checked'] ) ? '' : sprintf( __( 'Checked %s ago', 'pressable_cache_management' ), human_time_diff( $r['checked'] ) ),
    );
}

// ── AJAX: run the probe (or return the stored result) ────────────────────────
function pcm_ajax_probe_batcache() {
    check_ajax_referer( 'pcm_batcache_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }

    $force  = ! empty( $_POST['force'] );
    $stored = pcm_get_batcache_status();

    // A result under a minute old is fresh enough unless the user clicked refresh;
    // the lock stops two tabs probing at the same time.
    if ( ( ! $force && $stored['checked'] > time() - MINUTE_IN_SECONDS ) || get_transient( 'pcm_batcache_probe_lock' ) ) {
        $result = $stored;
    } else {
        set_transient( 'pcm_batcache_probe_lock', 1, 45 );
        $result = pcm_probe_batcache();
        delete_transient( 'pcm_batcache_probe_lock' );
    }

    wp_send_json_success( pcm_batcache_status_payload( $result ) );
}
add_action( 'wp_ajax_pcm_probe_batcache', 'pcm_ajax_probe_batcache' );

/**
 * Clear the stored status after any cache flush so the badge re-checks on the next
 * page load (the probe also re-warms the homepage in Batcache).
 */
function pcm_clear_batcache_status_transient() {
    delete_transient( 'pcm_batcache_status' );
}
add_action( 'pcm_after_object_cache_flush', 'pcm_clear_batcache_status_transient' );
add_action( 'pcm_after_batcache_flush',     'pcm_clear_batcache_status_transient' );
add_action( 'pcm_after_edge_cache_purge',   'pcm_clear_batcache_status_transient' );

// ─── Main page ───────────────────────────────────────────────────────────────
function pressable_cache_management_display_settings_page() {
    if ( ! current_user_can('manage_options') ) return;

    $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : null;

    $branding_opts  = get_option('remove_pressable_branding_tab_options');
    $show_branding  = ! ( $branding_opts && 'disable' == $branding_opts['branding_on_off_radio_button'] );

    wp_enqueue_style( 'pressable_cache_management',
        plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/style.css', array(),
        filemtime( plugin_dir_path( dirname( __FILE__ ) ) . 'public/css/style.css' ), 'screen' );
    wp_enqueue_style( 'pcm-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', array(), null );
    ?>
    <div class="wrap pcm-wrap" style="background:#f0f2f5;padding:24px 28px 40px;min-height:calc(100vh - 32px);font-family:'Inter',sans-serif;">
    <div style="max-width:1120px;margin:0 auto;">
    <h1 style="display:none;"><?php echo esc_html( get_admin_page_title() ); ?></h1>

    <!-- ── Tabs ── -->
    <nav class="nav-tab-wrapper" style="margin-bottom:28px;">
        <a href="admin.php?page=pressable_cache_management"
           class="nav-tab <?php echo $tab === null ? 'nav-tab-active' : ''; ?>">Object Cache</a>
        <a href="admin.php?page=pressable_cache_management&tab=edge_cache_settings_tab"
           class="nav-tab <?php echo $tab === 'edge_cache_settings_tab' ? 'nav-tab-active' : ''; ?>">Edge Cache</a>
        <a href="admin.php?page=pressable_cache_management&tab=remove_pressable_branding_tab"
           class="nav-tab nav-tab-hidden <?php echo $tab === 'remove_pressable_branding_tab' ? 'nav-tab-active' : ''; ?>">Branding</a>
    </nav>

    <script>
    /* Shared by every tab: the Object Cache tab's Batcache status check and the Edge Cache
       tab's Defensive Mode status check both use this nonce. Declared outside the tab
       conditionals so it exists no matter which tab is rendered. */
    var pcmBatcacheNonce = '<?php echo esc_js( wp_create_nonce( 'pcm_batcache_nonce' ) ); ?>';
    </script>

    <?php if ( $tab === null ) :
        $options = get_option('pressable_cache_management_options');

        // Batcache status badge: render the stored result now; JS re-checks on load.
        $bc         = pcm_get_batcache_status();
        $bc_payload = pcm_batcache_status_payload( $bc );
        $bc_never   = ( 'unknown' === $bc['status'] && empty( $bc['checked'] ) );
        $bc_label   = $bc_never ? __( 'Checking…', 'pressable_cache_management' ) : $bc_payload['label'];
        $bc_class   = ( 'cookies' === $bc['status'] ) ? 'broken' : ( 'excluded' === $bc['status'] ? 'unknown' : $bc['status'] );
    ?>

    <!-- Header: logo + status badge -->
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px;">
        <div>
            <?php if ( $show_branding ) : ?>
            <p style="font-size:11px;font-weight:600;color:#94a3b8;letter-spacing:1.2px;text-transform:uppercase;margin:0 0 6px;font-family:'Inter',sans-serif;"><?php echo esc_html__( 'Cache Management by', 'pressable_cache_management' ); ?></p>
            <img class="pressablecmlogo"
                 src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/pressable-logo-primary.svg' ); ?>"
                 alt="Pressable"
                 style="width:180px;height:auto;display:block;margin-bottom:6px;">
            <?php else : ?>
            <h2 style="font-size:20px;font-weight:700;color:#040024;margin:0 0 6px;font-family:'Inter',sans-serif;"><?php echo esc_html__( 'Cache Control', 'pressable_cache_management' ); ?></h2>
            <?php endif; ?>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;max-width:460px;">
            <div style="display:flex;align-items:center;gap:6px;">
                <span class="pcm-batcache-status <?php echo esc_attr($bc_class); ?>" id="pcm-bc-badge" title="<?php echo esc_attr( $bc_payload['checked'] ); ?>">
                    <span class="pcm-dot" id="pcm-bc-dot"></span>
                    <span id="pcm-bc-label"><?php echo esc_html($bc_label); ?></span>
                    <button id="pcm-bc-refresh" title="<?php esc_attr_e('Re-check Batcache status', 'pressable_cache_management'); ?>"
                            style="background:none;border:none;cursor:pointer;padding:0 0 0 6px;line-height:1;opacity:.6;font-size:13px;vertical-align:middle;"
                            onclick="pcmRefreshBatcacheStatus()">&#x21BB;</button>
                </span>
                <span class="pcm-bc-tooltip-wrap" style="position:relative;display:inline-flex;align-items:center;">
                    <span style="width:16px;height:16px;border-radius:50%;background:#e2e8f0;color:#64748b;font-size:10px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;cursor:default;font-family:'Inter',sans-serif;line-height:1;flex-shrink:0;" aria-label="Batcache info">&#x3F;</span>
                    <span class="pcm-bc-tooltip" style="display:none;position:absolute;right:0;top:24px;width:270px;background:#1e293b;color:#f1f5f9;font-size:11.5px;line-height:1.55;padding:10px 13px;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.18);z-index:9999;font-family:'Inter',sans-serif;font-weight:400;">
                        <?php echo esc_html__( 'The status is checked from the server: the homepage is requested up to three times (Batcache stores a page on its second visit) and the response headers are read. A result is reused for one minute; the refresh button re-checks right away. If the homepage sets cookies for anonymous visitors, Batcache never caches it and the cookie names are listed under the badge.', 'pressable_cache_management' ); ?>
                        <span style="position:absolute;right:8px;top:-5px;width:0;height:0;border-left:5px solid transparent;border-right:5px solid transparent;border-bottom:5px solid #1e293b;"></span>
                    </span>
                </span>
            </div>
        </div>
        <script>
        // The probe runs in PHP (see pcm_probe_batcache); this only asks for it and
        // paints the answer. The server reuses a result under a minute old unless
        // force=1 (the refresh button), so loading this page is cheap.
        function pcmApplyStatus(res) {
            if (!res || !res.success) return null;
            var d = res.data;
            var badge = document.getElementById('pcm-bc-badge');
            var label = document.getElementById('pcm-bc-label');
            var alertBox = document.getElementById('pcm-bc-alert');
            if (!badge || !label) return null;
            label.textContent = d.label;
            badge.className = 'pcm-batcache-status ' + (d.status === 'cookies' ? 'broken' : d.status === 'excluded' ? 'unknown' : d.status);
            badge.title = d.checked || '';
            if (alertBox) {
                alertBox.innerHTML = d.alert_html || '';
                alertBox.style.display = d.alert_html ? 'block' : 'none';
            }
            var ttlEl = document.getElementById('pcm-ttl-value');
            if (ttlEl && d.max_age) ttlEl.textContent = pcmSecondsToHuman(d.max_age);
            return d.status;
        }

        var pcmProbing = false;
        function pcmProbeBatcache(force, onDone) {
            if (pcmProbing) { if (onDone) onDone(); return; }
            pcmProbing = true;
            var badge = document.getElementById('pcm-bc-badge');
            var label = document.getElementById('pcm-bc-label');
            var prev  = label.textContent;
            badge.classList.add('checking');
            label.textContent = '<?php echo esc_js( __( 'Checking…', 'pressable_cache_management' ) ); ?>';
            fetch(ajaxurl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=pcm_probe_batcache&nonce=' + encodeURIComponent(pcmBatcacheNonce) + '&force=' + (force ? '1' : '0')
            })
            .then(function(r) { return r.json(); })
            .then(function(res) { if (pcmApplyStatus(res) === null) label.textContent = prev; })
            .catch(function() { label.textContent = prev; })
            .finally(function() {
                badge.classList.remove('checking');
                pcmProbing = false;
                if (onDone) onDone();
            });
        }

        function pcmSecondsToHuman(s) {
            s = parseInt(s);
            if (s <= 0) return '0 sec';
            if (s < 60) return s + ' sec';
            if (s < 3600) {
                var m = Math.floor(s / 60), sec = s % 60;
                return sec > 0 ? m + ' min ' + sec + ' sec' : m + ' min';
            }
            if (s < 86400) {
                var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60);
                return m > 0 ? h + ' hr ' + m + ' min' : h + ' hr';
            }
            var d = Math.floor(s / 86400), h = Math.floor((s % 86400) / 3600);
            return h > 0 ? d + ' day' + (d !== 1 ? 's' : '') + ' ' + h + ' hr' : d + ' day' + (d !== 1 ? 's' : '');
        }

        // Manual refresh button: always re-probes.
        function pcmRefreshBatcacheStatus() {
            var btn = document.getElementById('pcm-bc-refresh');
            btn.style.opacity = '0.3';
            btn.disabled = true;
            pcmProbeBatcache(true, function() {
                btn.style.opacity = '0.6';
                btn.disabled = false;
            });
        }

        // On load: confirm the stored status (served from the transient when fresh).
        pcmProbeBatcache(false);

        // Tooltip show/hide
        (function() {
            var wrap = document.querySelector('.pcm-bc-tooltip-wrap');
            if (!wrap) return;
            var tip = wrap.querySelector('.pcm-bc-tooltip');
            wrap.addEventListener('mouseenter', function() { tip.style.display = 'block'; });
            wrap.addEventListener('mouseleave', function() { tip.style.display = 'none'; });
        })();
        </script>
    </div>

    <!-- Batcache alert panel (filled by PHP now, by JS after each check) -->
    <div id="pcm-bc-alert" style="<?php echo $bc_payload['alert_html'] ? '' : 'display:none;'; ?>"><?php echo $bc_payload['alert_html']; ?></div>

    <!-- ── 2-column grid ── -->
    <div class="pcm-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

        <!-- LEFT -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <!-- Global Controls -->
            <div class="pcm-card">
                <h3 class="pcm-card-title">&#8635; <?php echo esc_html__( 'Global Controls', 'pressable_cache_management' ); ?></h3>
                <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 10px;"><?php echo esc_html__( 'Flush Object Cache', 'pressable_cache_management' ); ?></p>
                <form method="post">
                    <input type="hidden" name="flush_object_cache_nonce" value="<?php echo wp_create_nonce('flush_object_cache_nonce'); ?>">
                    <input type="submit" value="<?php esc_attr_e('Flush Cache for all Pages','pressable_cache_management'); ?>"
                           class="flushcache" id="pcm-flush-btn">
                </form>
                <?php $ts = get_option('flush-obj-cache-time-stamp'); ?>
                <div style="margin-top:12px;">
                    <span class="pcm-ts-label"><?php echo esc_html__( 'LAST FLUSHED', 'pressable_cache_management' ); ?></span><br>
                    <span class="pcm-ts-value"><?php echo $ts ? esc_html($ts) : '—'; ?></span>
                </div>

                <!-- Current Cache TTL -->
                <?php $pcm_ttl = get_option( 'pcm_last_ttl', array() ); ?>
                <div style="margin-top:14px;padding-top:12px;border-top:1px solid #f1f5f9;">
                    <span class="pcm-ts-label"><?php esc_html_e( 'CURRENT CACHE MAX-AGE', 'pressable_cache_management' ); ?></span><br>
                    <span id="pcm-ttl-value" class="pcm-ts-value">—</span>
                </div>
            </div>

            <!-- Automated Rules -->
            <div class="pcm-card">
                <form action="options.php" method="post" id="pcm-main-settings-form">
                <?php settings_fields('pressable_cache_management_options'); ?>
                <h3 class="pcm-card-title"><?php echo esc_html__( 'Automated Rules', 'pressable_cache_management' ); ?></h3>

                <?php
                $rules = array(
                    'flush_cache_theme_plugin_checkbox' => array(
                        'title' => '&#x1F50C; ' . __( 'Flush Cache on Plugin/Theme Update', 'pressable_cache_management' ),
                        'desc'  => __( 'Flush cache automatically on plugin & theme update.', 'pressable_cache_management' ),
                        'ts'    => get_option('flush-cache-theme-plugin-time-stamp'),
                    ),
                    'flush_cache_page_edit_checkbox' => array(
                        'title' => '&#x1F4DD; ' . __( 'Flush Cache on Post/Page Edit', 'pressable_cache_management' ),
                        'desc'  => __( 'Flush cache automatically when page/post/post_types are updated.', 'pressable_cache_management' ),
                        'ts'    => get_option('flush-cache-page-edit-time-stamp'),
                    ),
                    'flush_cache_on_comment_delete_checkbox' => array(
                        'title' => '&#x1F4AC; ' . __( 'Flush Cache on Comment Delete', 'pressable_cache_management' ),
                        'desc'  => __( 'Flush cache automatically when comments are deleted.', 'pressable_cache_management' ),
                        'ts'    => get_option('flush-cache-on-comment-delete-time-stamp'),
                    ),
                );
                foreach ( $rules as $id => $rule ) :
                    $checked = isset($options[$id]) ? checked($options[$id], 1, false) : '';
                ?>
                <div class="pcm-toggle-row">
                    <label class="switch" style="flex-shrink:0;margin-top:2px;">
                        <input type="checkbox"
                               name="pressable_cache_management_options[<?php echo esc_attr($id); ?>]"
                               value="1" <?php echo $checked; ?>>
                        <span class="slider round"></span>
                    </label>
                    <div>
                        <div class="pcm-toggle-title"><?php echo wp_kses_post($rule['title']); ?></div>
                        <div class="pcm-toggle-desc"><?php echo wp_kses_post($rule['desc']); ?></div>
                        <span class="pcm-ts-inline"><strong><?php echo __('Last flushed at:', 'pressable_cache_management'); ?></strong> <?php echo $rule['ts'] ? wp_kses_post( str_replace( array("\n", "\r"), ' ', $rule['ts'] ) ) : '&#8212;'; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
                </form>
            </div>

        </div><!-- /LEFT -->

        <!-- RIGHT -->
        <div style="display:flex;flex-direction:column;gap:20px;">

            <!-- Batcache & Page Rules -->
            <div class="pcm-card">
                <h3 class="pcm-card-title"><?php echo esc_html__( 'Batcache & Page Rules', 'pressable_cache_management' ); ?></h3>

                <?php
                // Extend Batcache
                $eb_checked = isset($options['extend_batcache_checkbox']) ? checked($options['extend_batcache_checkbox'],1,false) : '';
                ?>
                <div class="pcm-toggle-row">
                    <label class="switch" style="flex-shrink:0;margin-top:2px;">
                        <input type="checkbox" form="pcm-main-settings-form"
                               name="pressable_cache_management_options[extend_batcache_checkbox]"
                               value="1" <?php echo $eb_checked; ?>>
                        <span class="slider round"></span>
                    </label>
                    <div>
                        <div class="pcm-toggle-title"><?php echo esc_html__( 'Extend Batcache (by 24 hrs)', 'pressable_cache_management' ); ?></div>
                        <div class="pcm-toggle-desc"><?php echo esc_html__( 'Extend Batcache storage time by 24 hours.', 'pressable_cache_management' ); ?></div>
                    </div>
                </div>

                <?php
                // Flush Batcache for Individual Pages
                $sp_checked = isset($options['flush_object_cache_for_single_page']) ? checked($options['flush_object_cache_for_single_page'],1,false) : '';
                $sp_ts      = get_option('flush-object-cache-for-single-page-time-stamp');
                $sp_url     = get_option('single-page-url-flushed');
                ?>
                <div class="pcm-toggle-row">
                    <label class="switch" style="flex-shrink:0;margin-top:2px;">
                        <input type="checkbox" form="pcm-main-settings-form"
                               name="pressable_cache_management_options[flush_object_cache_for_single_page]"
                               value="1" <?php echo $sp_checked; ?>>
                        <span class="slider round"></span>
                    </label>
                    <div>
                        <div class="pcm-toggle-title"><?php echo esc_html__( 'Flush Batcache for Individual Pages', 'pressable_cache_management' ); ?></div>
                        <div class="pcm-toggle-desc"><?php echo esc_html__( 'Flush Batcache for individual pages from page preview toolbar.', 'pressable_cache_management' ); ?></div>
                        <span class="pcm-ts-inline"><strong><?php echo __('Last flushed at:', 'pressable_cache_management'); ?></strong> <?php echo $sp_ts ? wp_kses_post( str_replace( array("\n", "\r"), ' ', $sp_ts ) ) : '&#8212;'; ?></span>
                        <span class="pcm-ts-inline"><strong><?php echo __('Page URL:', 'pressable_cache_management'); ?></strong> <?php echo $sp_url ? esc_html( $sp_url ) : '&#8212;'; ?></span>
                    </div>
                </div>

                <?php
                // Flush cache automatically when published pages/posts are deleted
                $del_checked = isset($options['flush_cache_on_page_post_delete_checkbox']) ? checked($options['flush_cache_on_page_post_delete_checkbox'],1,false) : '';
                $del_ts      = get_option('flush-cache-on-page-post-delete-time-stamp');
                ?>
                <div class="pcm-toggle-row">
                    <label class="switch" style="flex-shrink:0;margin-top:2px;">
                        <input type="checkbox" form="pcm-main-settings-form"
                               name="pressable_cache_management_options[flush_cache_on_page_post_delete_checkbox]"
                               value="1" <?php echo $del_checked; ?>>
                        <span class="slider round"></span>
                    </label>
                    <div>
                        <div class="pcm-toggle-title"><?php echo esc_html__( 'Flush cache automatically when published pages/posts are deleted.', 'pressable_cache_management' ); ?></div>
                        <div class="pcm-toggle-desc"><?php echo esc_html__( 'Flushes Batcache for the specific page when it is deleted.', 'pressable_cache_management' ); ?></div>
                        <span class="pcm-ts-inline"><strong><?php echo __('Last flushed at:', 'pressable_cache_management'); ?></strong> <?php echo $del_ts ? wp_kses_post( str_replace( array("\n", "\r"), ' ', $del_ts ) ) : '&#8212;'; ?></span>
                    </div>
                </div>

                <?php
                // Flush Batcache for WooCommerce product pages
                $woo_checked = isset($options['flush_batcache_for_woo_product_individual_page_checkbox']) ? checked($options['flush_batcache_for_woo_product_individual_page_checkbox'],1,false) : '';
                ?>
                <div class="pcm-toggle-row" style="border-bottom:none;">
                    <label class="switch" style="flex-shrink:0;margin-top:2px;">
                        <input type="checkbox" form="pcm-main-settings-form"
                               name="pressable_cache_management_options[flush_batcache_for_woo_product_individual_page_checkbox]"
                               value="1" <?php echo $woo_checked; ?>>
                        <span class="slider round"></span>
                    </label>
                    <div>
                        <div class="pcm-toggle-title"><?php echo esc_html__( 'Flush Batcache for WooCommerce product pages', 'pressable_cache_management' ); ?></div>
                        <div class="pcm-toggle-desc"><?php echo esc_html__( 'Flush Batcache for WooCommerce product pages.', 'pressable_cache_management' ); ?></div>
                    </div>
                </div>
            </div>

            <!-- Exclude Pages -->
            <div class="pcm-card">
                <h3 class="pcm-card-title"><?php echo esc_html__( 'Exclude Pages', 'pressable_cache_management' ); ?></h3>
                <p style="font-size:13px;font-weight:600;color:#374151;margin:0 0 10px;"><?php echo esc_html__( 'Cache Exclusions', 'pressable_cache_management' ); ?></p>

                <?php
                $exempt_val = isset($options['exempt_from_batcache']) ? sanitize_text_field($options['exempt_from_batcache']) : '';
                $pages = $exempt_val ? array_values( array_filter( array_map('trim', explode(',', $exempt_val)) ) ) : array();
                ?>

                <!-- Chips -->
                <div id="pcm-chips-wrap" style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px;min-height:0;">
                    <?php foreach ($pages as $page) : ?>
                    <span class="pcm-chip" data-value="<?php echo esc_attr($page); ?>">
                        <?php echo esc_html($page); ?>
                        <button type="button" class="pcm-chip-remove" title="Remove">&#xD7;</button>
                    </span>
                    <?php endforeach; ?>
                </div>

                <input type="hidden" id="pcm-exempt-hidden"
                       name="pressable_cache_management_options[exempt_from_batcache]"
                       form="pcm-main-settings-form"
                       value="<?php echo esc_attr($exempt_val); ?>">

                <input type="text" id="pcm-exempt-input" autocomplete="off"
                       placeholder="<?php echo esc_attr__( 'Enter single URL (e.g., /pagename/).', 'pressable_cache_management' ); ?>"
                       style="width:100%;height:40px;padding:0 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;background:#f8fafc;">
                <p style="font-size:11.5px;color:#94a3b8;margin:6px 0 0;">
                    <?php echo esc_html__( 'To exclude a single page use', 'pressable_cache_management' ); ?> <code>/page/</code> — <?php echo esc_html__( 'for multiple pages separate with comma, e.g.', 'pressable_cache_management' ); ?> <code>/your-site.com/, /about-us/, /info/</code>
                </p>
            </div>

        </div><!-- /RIGHT -->
    </div><!-- /grid -->

    <!-- Save button -->
    <div style="display:flex;justify-content:center;margin-top:28px;">
        <?php submit_button( __( '&#10003;&nbsp; Save Settings', 'pressable_cache_management' ), 'custom-class', 'submit', false, array('form'=>'pcm-main-settings-form') ); ?>
    </div>

    <!-- Chip JS -->
    <script>
    (function(){
        var wrap   = document.getElementById('pcm-chips-wrap');
        var input  = document.getElementById('pcm-exempt-input');
        var hidden = document.getElementById('pcm-exempt-hidden');
        if (!wrap || !input || !hidden) return;

        function getVals(){ return hidden.value ? hidden.value.split(',').map(function(s){ return s.trim(); }).filter(Boolean) : []; }
        function syncHidden(v){ hidden.value = v.join(', '); }

        // Format raw input into a clean /slug/ path
        function formatPath(val) {
            val = val.trim();
            // Strip full URLs — extract just the pathname
            try { var u = new URL(val); val = u.pathname; } catch(e) {}
            // Remove invalid characters — only allow alphanumeric, hyphen, underscore, dot, slash
            val = val.replace(/[^a-zA-Z0-9\-_\/\.]/g, '');
            // Collapse multiple slashes
            val = val.replace(/\/+/g, '/');
            // Ensure leading slash
            if (val.charAt(0) !== '/') val = '/' + val;
            // Ensure trailing slash
            if (val.charAt(val.length - 1) !== '/') val = val + '/';
            // Must be more than just a single slash
            return val.length > 1 ? val : '';
        }

        function addChip(val) {
            val = formatPath(val);
            if (!val) return;
            var vals = getVals();
            if (vals.indexOf(val) !== -1) return; // no duplicates
            vals.push(val); syncHidden(vals); renderChip(val);
        }
        function removeChip(val){ syncHidden(getVals().filter(function(v){ return v !== val; })); }

        function renderChip(val){
            var c = document.createElement('span');
            c.className = 'pcm-chip'; c.dataset.value = val;
            c.innerHTML = val + ' <button type="button" class="pcm-chip-remove" title="Remove">&#xD7;</button>';
            c.querySelector('.pcm-chip-remove').addEventListener('click', function(){ removeChip(val); c.remove(); });
            wrap.appendChild(c);
        }

        wrap.querySelectorAll('.pcm-chip-remove').forEach(function(btn){
            btn.addEventListener('click', function(){ var c = btn.closest('.pcm-chip'); removeChip(c.dataset.value); c.remove(); });
        });

        input.addEventListener('keydown', function(e){
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                var r = input.value.replace(/,/g, '').trim();
                if (r) { addChip(r); input.value = ''; }
            }
        });
        input.addEventListener('blur', function(){
            var r = input.value.replace(/,/g, '').trim();
            if (r) { addChip(r); input.value = ''; }
        });
        input.addEventListener('paste', function(e){
            e.preventDefault();
            var p = (e.clipboardData || window.clipboardData).getData('text');
            p.split(',').forEach(function(v){ var t = v.trim(); if (t) addChip(t); });
            input.value = '';
        });
    })();
    </script>

    <?php elseif ( $tab === 'edge_cache_settings_tab' ) : ?>

    <style>
    /* Edge Cache tab styles */
    .edge-cache-loader {
        display:flex;align-items:center;height:30px;
        font-style:italic;color:#94a3b8;font-family:'Inter',sans-serif;font-size:13px;
    }
    .edge-cache-loader::before {
        content:'';border:3px solid #e2e8f0;border-top:3px solid #03fcc2;
        border-radius:50%;width:14px;height:14px;
        animation:ec-spin 1s linear infinite;margin-right:10px;flex-shrink:0;
    }
    @keyframes ec-spin { 0%{transform:rotate(0deg)} 100%{transform:rotate(360deg)} }
    /* AJAX-injected enable/disable buttons */
    #edge-cache-control-wrapper input[type="submit"] {
        padding:10px 28px;border:none;border-radius:8px;
        font-size:14px;font-weight:700;cursor:pointer;
        font-family:'Inter',sans-serif;transition:background .2s;
    }
    /* Both Enable/Disable buttons use .purgecacahe → orange default, green hover */
    #edge_cache_settings_tab_options_enable,
    #edge_cache_settings_tab_options_disable {
        background:#dd3a03 !important;color:#fff !important;
    }
    #edge_cache_settings_tab_options_enable:hover,
    #edge_cache_settings_tab_options_disable:hover {
        background:#03fcc2 !important;color:#040024 !important;
        box-shadow:0 4px 14px rgba(3,252,194,0.45) !important;
    }
    /* Purge button hover (not disabled) */
    #purge-edge-cache-button-input:not([disabled]):hover {
        background:#03fcc2 !important;color:#040024 !important;
        box-shadow:0 4px 14px rgba(3,252,194,0.45) !important;
    }
    /* Defensive Mode button hovers (not disabled) */
    #pcm-dm-enable-btn:not([disabled]):hover,
    #pcm-dm-disable-btn:not([disabled]):hover {
        background:#03fcc2 !important;color:#040024 !important;
        box-shadow:0 4px 14px rgba(3,252,194,0.45) !important;
        cursor:pointer;
    }
    .ec-disabled-btn { opacity:.5;cursor:not-allowed !important;pointer-events:none; }
    </style>

    <!-- Page heading -->
    <div style="margin-bottom:20px;">
        <h2 style="font-size:20px;font-weight:700;color:#040024;margin:0 0 6px;font-family:'Inter',sans-serif;">
            <?php echo esc_html__( 'Manage Edge Cache Settings', 'pressable_cache_management' ); ?>
        </h2>
    </div>

    <!-- Card -->
    <div style="max-width:680px;">
    <div class="pcm-card" style="padding:0;">

        <!-- Row 1: Turn On/Off -->
        <div style="display:flex;align-items:center;justify-content:space-between;gap:24px;padding:24px 28px;border-bottom:1px solid #f1f5f9;">
            <div>
                <p style="font-size:15px;font-weight:700;color:#040024;margin:0 0 6px;font-family:'Inter',sans-serif;">
                    <?php echo esc_html__( 'Turn On/Off Edge Cache', 'pressable_cache_management' ); ?>
                </p>
                <p style="font-size:13px;color:#64748b;margin:0;font-family:'Inter',sans-serif;">
                    <?php echo esc_html__( 'Enable or disable the edge cache for this site.', 'pressable_cache_management' ); ?>
                </p>
            </div>
            <div id="edge-cache-control-wrapper" style="flex-shrink:0;min-width:180px;text-align:right;">
                <div class="edge-cache-loader"></div>
            </div>
        </div>

        <!-- Row 2: Purge + description + timestamps -->
        <div style="padding:24px 28px;">

            <!-- Purge title + button -->
            <div style="display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:12px;">
                <p style="font-size:15px;font-weight:700;color:#040024;margin:0;font-family:'Inter',sans-serif;">
                    <?php echo esc_html__( 'Purge Edge Cache', 'pressable_cache_management' ); ?>
                </p>
                <form method="post" id="purge_edge_cache_nonce_form_static" style="flex-shrink:0;">
                    <?php settings_fields('edge_cache_settings_tab_options'); ?>
                    <input type="hidden" name="purge_edge_cache_nonce" value="<?php echo wp_create_nonce('purge_edge_cache_nonce'); ?>">
                    <input id="purge-edge-cache-button-input"
                           name="edge_cache_settings_tab_options[purge_edge_cache_button]"
                           type="submit"
                           value="<?php echo esc_attr__( 'Purge Edge Cache', 'pressable_cache_management' ); ?>"
                           disabled
                           class="ec-disabled-btn"
                           style="padding:10px 28px;border:none;border-radius:8px;font-size:14px;font-weight:700;
                                  color:#fff;background:#dd3a03;font-family:'Inter',sans-serif;
                                  transition:background .2s,opacity .2s;">
                </form>
            </div>

            <!-- Description -->
            <p style="font-size:13px;color:#64748b;margin:0 0 20px;font-family:'Inter',sans-serif;">
                <?php echo esc_html__( 'Purging cache will temporarily slow down your site for all visitors while the cache rebuilds.', 'pressable_cache_management' ); ?>
            </p>

            <!-- Timestamps -->
            <div style="display:flex;flex-direction:column;gap:16px;">

                <div>
                    <span class="pcm-ts-label"><?php echo esc_html__( 'LAST FLUSHED', 'pressable_cache_management' ); ?></span>
                    <span class="pcm-ts-value" style="display:block;margin-top:4px;"><?php
                        $v = get_option('edge-cache-purge-time-stamp');
                        echo $v ? esc_html($v) : '&mdash;';
                    ?></span>
                </div>

                <div>
                    <span class="pcm-ts-label"><?php echo esc_html__( 'SINGLE PAGE LAST FLUSHED', 'pressable_cache_management' ); ?></span>
                    <span class="pcm-ts-value" style="display:block;margin-top:4px;"><?php
                        $v = get_option('single-page-edge-cache-purge-time-stamp');
                        echo $v ? esc_html($v) : '&mdash;';
                    ?></span>
                </div>

                <div>
                    <span class="pcm-ts-label"><?php echo esc_html__( 'SINGLE PAGE URL', 'pressable_cache_management' ); ?></span>
                    <span class="pcm-ts-value" style="display:block;margin-top:4px;word-break:break-all;"><?php
                        $v = get_option('edge-cache-single-page-url-purged');
                        echo $v ? esc_html($v) : '&mdash;';
                    ?></span>
                </div>

            </div>
        </div>

        <!-- Row 3: Defensive Mode -->
        <div style="padding:24px 28px;border-top:1px solid #f1f5f9;">

            <!-- Title + status badge -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:24px;margin-bottom:10px;">
                <div>
                    <p style="font-size:15px;font-weight:700;color:#040024;margin:0 0 6px;font-family:'Inter',sans-serif;">
                        <?php echo esc_html__( 'Edge Cache Defensive Mode', 'pressable_cache_management' ); ?>
                    </p>
                    <p style="font-size:13px;color:#64748b;margin:0 0 6px;font-family:'Inter',sans-serif;">
                        <?php echo esc_html__( 'Adds an extra layer of protection against spam bots and DDoS attacks. When enabled, visitors\' browsers must complete a small challenge before accessing the site. Legitimate users may see a brief challenge page. Edge Cache must be enabled to use this feature.', 'pressable_cache_management' ); ?>
                    </p>
                    <!-- Live status line — populated by JS -->
                    <div id="pcm-dm-status-line" style="font-size:13px;font-weight:600;color:#94a3b8;margin:6px 0 0;font-family:'Inter',sans-serif;font-style:italic;line-height:1.6;">
                        <?php echo esc_html__( 'Checking status…', 'pressable_cache_management' ); ?>
                    </div>
                </div>
            </div>

            <!-- Controls: enable form (contains dropdown) + separate disable form -->
            <div id="pcm-dm-controls" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:14px;">

                <!-- Enable form — select lives here so its value is always submitted -->
                <form method="post" id="pcm-dm-enable-form" style="margin:0;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <input type="hidden" name="enable_defensive_mode_nonce"
                           value="<?php echo wp_create_nonce( 'enable_defensive_mode_nonce' ); ?>">

                    <select id="pcm-dm-duration-select"
                            name="defensive_mode_duration"
                            disabled
                            style="padding:9px 14px;border:1px solid #e2e8f0;border-radius:8px;
                                   font-size:13px;font-family:'Inter',sans-serif;color:#040024;
                                   background:#f8fafc;cursor:not-allowed;opacity:.5;transition:opacity .2s;">
                        <?php
                        $saved_slug = get_option( 'edge-cache-defensive-mode-slug', '30-minutes' );
                        foreach ( pcm_defensive_mode_durations() as $val => $entry ) :
                        ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $val, $saved_slug ); ?>><?php echo esc_html( $entry['label'] ); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <input id="pcm-dm-enable-btn"
                           type="submit"
                           value="<?php echo esc_attr__( 'Enable Defensive Mode', 'pressable_cache_management' ); ?>"
                           disabled
                           class="ec-disabled-btn"
                           style="padding:10px 22px;border:none;border-radius:8px;font-size:13px;font-weight:700;
                                  color:#fff;background:#dd3a03;font-family:'Inter',sans-serif;
                                  cursor:not-allowed;transition:background .2s,opacity .2s;">
                </form>

                <!-- Disable form -->
                <form method="post" id="pcm-dm-disable-form" style="margin:0;">
                    <input type="hidden" name="disable_defensive_mode_nonce"
                           value="<?php echo wp_create_nonce( 'disable_defensive_mode_nonce' ); ?>">
                    <input id="pcm-dm-disable-btn"
                           type="submit"
                           value="<?php echo esc_attr__( 'Disable Defensive Mode', 'pressable_cache_management' ); ?>"
                           disabled
                           class="ec-disabled-btn"
                           style="padding:10px 22px;border:none;border-radius:8px;font-size:13px;font-weight:700;
                                  color:#fff;background:#64748b;font-family:'Inter',sans-serif;
                                  cursor:not-allowed;transition:background .2s,opacity .2s;">
                </form>

            </div><!-- /controls -->

        </div><!-- /Row 3 -->

        <!-- Row 4: Edge Cache Preload -->
        <div style="padding:24px 28px;border-top:1px solid #f1f5f9;">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:24px;margin-bottom:10px;">
                <div>
                    <p style="font-size:15px;font-weight:700;color:#040024;margin:0 0 6px;font-family:'Inter',sans-serif;">
                        <?php echo esc_html__( 'Edge Cache Preload', 'pressable_cache_management' ); ?>
                        <span style="font-size:10px;font-weight:700;letter-spacing:.6px;text-transform:uppercase;color:#9a3412;background:#fff7ed;border:1px solid #fed7aa;border-radius:999px;padding:2px 8px;margin-left:6px;vertical-align:middle;"><?php echo esc_html__( 'Beta', 'pressable_cache_management' ); ?></span>
                    </p>
                    <p style="font-size:13px;color:#64748b;margin:0 0 6px;font-family:'Inter',sans-serif;">
                        <?php echo esc_html__( 'Crawls the site from the front page and caches pages before visitors request them, then refreshes pages after purges. Helps sites with many rarely visited pages, such as large product catalogs. The crawl renders every page it finds, so it uses PHP workers while running, and a cache flush drops the warmed pages and starts a new crawl. Edge Cache must be enabled.', 'pressable_cache_management' ); ?>
                    </p>
                    <div id="pcm-preload-status-line" style="font-size:13px;font-weight:600;color:#94a3b8;margin:6px 0 0;font-family:'Inter',sans-serif;font-style:italic;line-height:1.6;">
                        <?php echo esc_html__( 'Checking status…', 'pressable_cache_management' ); ?>
                    </div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:14px;">
                <button type="button" id="pcm-preload-enable-btn" disabled class="ec-disabled-btn"
                        style="padding:10px 22px;border:none;border-radius:8px;font-size:13px;font-weight:700;color:#fff;background:#dd3a03;font-family:'Inter',sans-serif;cursor:not-allowed;transition:background .2s,opacity .2s;">
                    <?php echo esc_html__( 'Enable Preload', 'pressable_cache_management' ); ?>
                </button>
                <button type="button" id="pcm-preload-disable-btn" disabled class="ec-disabled-btn"
                        style="padding:10px 22px;border:none;border-radius:8px;font-size:13px;font-weight:700;color:#fff;background:#64748b;font-family:'Inter',sans-serif;cursor:not-allowed;transition:background .2s,opacity .2s;">
                    <?php echo esc_html__( 'Disable Preload', 'pressable_cache_management' ); ?>
                </button>
                <button type="button" id="pcm-preload-refresh-btn" title="<?php esc_attr_e( 'Refresh preload status', 'pressable_cache_management' ); ?>"
                        style="background:none;border:none;cursor:pointer;padding:0 4px;line-height:1;opacity:.6;font-size:16px;color:#040024;">&#x21BB;</button>
            </div>
        </div><!-- /Row 4 -->

    </div><!-- /card -->
    </div><!-- /max-width -->

    <script>
    jQuery(document).ready(function($){
        var wrapper  = $('#edge-cache-control-wrapper');
        var purgeBtn = $('#purge-edge-cache-button-input');

        // ── Defensive Mode elements ───────────────────────────────────────────
        var dmStatusLine = $('#pcm-dm-status-line');
        var dmSelect     = $('#pcm-dm-duration-select');
        var dmEnableBtn  = $('#pcm-dm-enable-btn');
        var dmDisableBtn = $('#pcm-dm-disable-btn');

        /**
         * Apply defensive mode UI state.
         * Called once EC status is known so edgeCacheEnabled is accurate.
         */
        function pcmSafeApplyDmState( edgeCacheEnabled ) {
            try {
                pcmApplyDmState( edgeCacheEnabled );
            } catch ( e ) {
                dmStatusLine
                    .text('<?php echo esc_js( __( 'Could not retrieve Defensive Mode status.', 'pressable_cache_management' ) ); ?>')
                    .css('color','#ef4444');
                if ( window.console ) { console.error( 'PCM Defensive Mode status check failed:', e ); }
            }
        }

        function pcmApplyDmState( edgeCacheEnabled ) {
            $.ajax({
                url: ajaxurl, type: 'POST',
                data: { action: 'pcm_check_defensive_mode_status', nonce: ( typeof pcmBatcacheNonce !== 'undefined' ) ? pcmBatcacheNonce : '' },
                success: function(r) {
                    if ( ! r.success ) {
                        dmStatusLine
                            .text('<?php echo esc_js( __( 'Unable to retrieve Defensive Mode status.', 'pressable_cache_management' ) ); ?>')
                            .css('color','#ef4444');
                        return;
                    }

                    if ( r.data.defensive_active ) {
                        var enabledLabel = '<?php echo esc_js( __( 'Enabled', 'pressable_cache_management' ) ); ?>';
                        var atLabel      = '<?php echo esc_js( __( 'at', 'pressable_cache_management' ) ); ?>';
                        var untilLabel   = '<?php echo esc_js( __( 'Enabled until', 'pressable_cache_management' ) ); ?>';

                        var shieldIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#059669" style="vertical-align:middle;margin-right:5px;position:relative;top:-1px;"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>';

                        // Line 1: 🛡 Enabled — at: 12 Mar 2026, 4:11pm UTC
                        var statusHtml = shieldIcon + '<strong style="color:#059669;">' + enabledLabel + '</strong>';
                        if ( r.data.set_at ) {
                            statusHtml += ' &mdash; ' + atLabel + ': ' + r.data.set_at;
                        }

                        // Line 2: Enabled until: 12 Mar 2026, 5:11pm UTC (indented under line 1)
                        if ( r.data.expires_at ) {
                            statusHtml += '<br><span style="font-size:13px;color:#059669;font-weight:400;padding-left:20px;display:inline-block;">'
                                + untilLabel + ': ' + r.data.expires_at + '</span>';
                        }

                        dmStatusLine
                            .html( statusHtml )
                            .css({ color:'#059669', fontStyle:'normal' });

                        // Active: lock select + enable btn; only disable btn is live
                        dmSelect.prop('disabled', true).css({ opacity:.4, cursor:'not-allowed' });
                        dmEnableBtn.prop('disabled', true).addClass('ec-disabled-btn')
                            .css({ opacity:.4, cursor:'not-allowed', pointerEvents:'none' });
                        dmDisableBtn.prop('disabled', false).removeClass('ec-disabled-btn')
                            .css({ opacity:1, cursor:'pointer', pointerEvents:'auto', background:'#dd3a03' });

                    } else {
                        if ( edgeCacheEnabled ) {
                            dmStatusLine
                                .text('<?php echo esc_js( __( 'Disabled', 'pressable_cache_management' ) ); ?>')
                                .css({ color:'#64748b', fontStyle:'normal' });
                            dmSelect.prop('disabled', false).css({ opacity:1, cursor:'pointer' });
                            dmEnableBtn.prop('disabled', false).removeClass('ec-disabled-btn')
                                .css({ opacity:1, cursor:'pointer', pointerEvents:'auto' });
                        } else {
                            // Edge Cache is off — lock DM controls with plain readable message
                            dmStatusLine
                                .text('<?php echo esc_js( __( 'Disabled — Edge Cache must be enabled first.', 'pressable_cache_management' ) ); ?>')
                                .css({ color:'#94a3b8', fontStyle:'normal' });
                            dmSelect.prop('disabled', true).css({ opacity:.4, cursor:'not-allowed' });
                            dmEnableBtn.prop('disabled', true).addClass('ec-disabled-btn')
                                .css({ opacity:.4, cursor:'not-allowed', pointerEvents:'none' });
                        }
                        // Disable btn always greyed when DM is off
                        dmDisableBtn.prop('disabled', true).addClass('ec-disabled-btn')
                            .css({ opacity:.4, cursor:'not-allowed', pointerEvents:'none' });
                    }
                },
                error: function() {
                    dmStatusLine
                        .text('<?php echo esc_js( __( 'Could not retrieve Defensive Mode status.', 'pressable_cache_management' ) ); ?>')
                        .css('color','#ef4444');
                }
            });
        }

        // ── Edge Cache status check — DM check runs after this completes ─────
        if (wrapper.length && !wrapper.data('ec-checked')) {
            wrapper.data('ec-checked', true);
            $.ajax({
                url: ajaxurl, type: 'POST',
                data: { action: 'pcm_check_edge_cache_status', nonce: '<?php echo esc_js( wp_create_nonce( 'pcm_ec_nonce' ) ); ?>' },
                success: function(r) {
                    var ecEnabled = false;
                    if (r.success && r.data.html_controls_enable_disable) {
                        wrapper.html(r.data.html_controls_enable_disable);
                        if (r.data.enabled) {
                            ecEnabled = true;
                            purgeBtn.removeClass('ec-disabled-btn')
                                    .prop('disabled', false)
                                    .css({ opacity:1, cursor:'pointer', pointerEvents:'auto' });
                        }
                    } else {
                        var msg = (r.data && r.data.message) ? r.data.message : '<?php echo esc_js( __( 'Failed to retrieve status.', 'pressable_cache_management' ) ); ?>';
                        wrapper.html('<p style="color:#ef4444;font-size:13px;margin:0;">'+msg+'</p>');
                    }
                    // Always run DM check after EC check resolves, passing accurate EC state.
                    // Isolated so a failure in the DM check can never affect the EC controls above.
                    pcmSafeApplyDmState( ecEnabled );
                    if ( window.pcmPreloadInit ) { pcmPreloadInit( ecEnabled ); }
                },
                error: function() {
                    wrapper.html('<p style="color:#ef4444;font-size:13px;margin:0;"><?php echo esc_js( __( 'Could not connect to server.', 'pressable_cache_management' ) ); ?></p>');
                    // Still run DM check even if EC check fails, treating EC as off
                    pcmSafeApplyDmState( false );
                    if ( window.pcmPreloadInit ) { pcmPreloadInit( false ); }
                }
            });
        }

    });
    </script>
    <script>
    // ── Edge Cache Preload panel ───────────────────────────────────────────────
    // pcmPreloadInit(ecEnabled) is called from the Edge Cache status check above
    // once it knows whether Edge Cache is on. Status comes from the WP Cloud API
    // (not from crawling the site), so it is polled every 10 s only while a crawl
    // is pending or running and this tab is visible.
    (function($){
        var line    = $('#pcm-preload-status-line');
        var enable  = $('#pcm-preload-enable-btn');
        var disable = $('#pcm-preload-disable-btn');
        var refresh = $('#pcm-preload-refresh-btn');
        var ecOn    = false, timer = null, busy = false;
        var T = {
            enabled:  '<?php echo esc_js( __( 'Enabled', 'pressable_cache_management' ) ); ?>',
            disabled: '<?php echo esc_js( __( 'Disabled', 'pressable_cache_management' ) ); ?>',
            needEc:   '<?php echo esc_js( __( 'Disabled — Edge Cache must be enabled first.', 'pressable_cache_management' ) ); ?>',
            pages:    '<?php echo esc_js( __( '%1$s of %2$s pages preloaded', 'pressable_cache_management' ) ); ?>',
            finished: '<?php echo esc_js( __( 'Finished', 'pressable_cache_management' ) ); ?>',
            failed:   '<?php echo esc_js( __( 'Could not retrieve Cache Preload status.', 'pressable_cache_management' ) ); ?>',
            working:  '<?php echo esc_js( __( 'Working…', 'pressable_cache_management' ) ); ?>'
        };

        function setBtn(btn, on, bg) {
            btn.prop('disabled', !on).toggleClass('ec-disabled-btn', !on)
               .css({ opacity: on ? 1 : .4, cursor: on ? 'pointer' : 'not-allowed', pointerEvents: on ? 'auto' : 'none', background: bg });
        }

        function render(d) {
            var html;
            if (d.enabled) {
                html = '<strong style="color:#059669;">' + T.enabled + '</strong>';
                if (d.state_label) html += ' &mdash; ' + $('<span>').text(d.state_label).html();
                var parts = [];
                if (d.urls_preloaded !== null && d.urls_found !== null) {
                    parts.push(T.pages.replace('%1$s', d.urls_preloaded).replace('%2$s', d.urls_found + (d.searching ? '+' : '')));
                } else if (d.urls_preloaded !== null) {
                    parts.push(d.urls_preloaded + ' ' + T.pages.split(' ').slice(-2).join(' '));
                }
                if (d.finished_time) parts.push(T.finished + ': ' + d.finished_time);
                if (d.error) parts.push('<span style="color:#dd3a03;">' + $('<span>').text(d.error).html() + '</span>');
                if (parts.length) html += '<br><span style="font-size:13px;color:#475569;font-weight:400;">' + parts.join(' &middot; ') + '</span>';
                line.html(html).css({ color:'#059669', fontStyle:'normal' });
                setBtn(enable, false, '#dd3a03');
                setBtn(disable, true, '#dd3a03');
            } else {
                line.text(ecOn ? T.disabled : T.needEc).css({ color: ecOn ? '#64748b' : '#94a3b8', fontStyle:'normal' });
                setBtn(enable, ecOn, '#dd3a03');
                setBtn(disable, false, '#64748b');
            }
            clearTimeout(timer);
            if (d.enabled && (d.state === 'pending' || d.state === 'running')) {
                timer = setTimeout(function(){ if (!document.hidden) load(); else timer = setTimeout(load, 10000); }, 10000);
            }
        }

        function fail(msg) {
            line.text(msg || T.failed).css({ color:'#ef4444', fontStyle:'normal' });
            setBtn(enable, false, '#dd3a03'); setBtn(disable, false, '#64748b');
        }

        function load() {
            $.post(ajaxurl, { action: 'pcm_cache_preload_status', nonce: pcmBatcacheNonce })
             .done(function(r){ r.success ? render(r.data) : fail(r.data && r.data.message); })
             .fail(function(){ fail(); });
        }

        function toggle(on) {
            if (busy) return;
            busy = true;
            line.text(T.working).css({ color:'#94a3b8', fontStyle:'italic' });
            setBtn(enable, false, '#dd3a03'); setBtn(disable, false, '#64748b');
            $.post(ajaxurl, { action: 'pcm_cache_preload_toggle', nonce: pcmBatcacheNonce, enable: on ? 1 : 0 })
             .done(function(r){ r.success ? render(r.data) : fail(r.data && r.data.message); })
             .fail(function(){ fail(); })
             .always(function(){ busy = false; });
        }

        enable.on('click', function(){ toggle(true); });
        disable.on('click', function(){ toggle(false); });
        refresh.on('click', function(){ clearTimeout(timer); load(); });

        window.pcmPreloadInit = function(edgeCacheEnabled) {
            ecOn = !!edgeCacheEnabled;
            load();
        };
    })(jQuery);
    </script>
    <?php elseif ( $tab === 'remove_pressable_branding_tab' ) : ?>

    <form action="options.php" method="post">
        <?php
        settings_fields('remove_pressable_branding_tab_options');
        do_settings_sections('remove_pressable_branding_tab');
        submit_button('Save Settings','custom-class');
        ?>
    </form>

    <?php endif; ?>

    </div><!-- /inner-center -->
    </div><!-- /wrap -->

    <style>
    /* ── Inline styles for component classes ── */
    .pcm-card {
        background:#fff;border-radius:12px;border:1px solid #e2e8f0;
        padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.04);
    }
    .pcm-card-title {
        font-size:15px;font-weight:700;color:#040024;margin:0 0 16px;
        font-family:'Inter',sans-serif;
    }
    .pcm-toggle-row {
        display:flex;align-items:flex-start;gap:14px;
        padding:14px 0;border-bottom:1px solid #f1f5f9;
    }
    .pcm-toggle-title {
        font-size:13.5px;font-weight:600;color:#040024;
        font-family:'Inter',sans-serif;line-height:1.3;
    }
    .pcm-toggle-desc {
        font-size:12px;color:#64748b;margin-top:2px;font-family:'Inter',sans-serif;
    }
    .pcm-ts-label {
        font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;
        font-family:'Inter',sans-serif;font-weight:600;
    }
    .pcm-ts-value {
        font-size:12.5px;color:#040024;font-family:'Inter',sans-serif;
        font-weight:500;display:block;margin-top:2px;
    }
    .pcm-ts-inline {
        font-size:11.5px;color:#475569;display:block;margin-top:4px;
        font-family:'Inter',sans-serif;font-weight:500;
    }
    /* Flush button: red, hover green */
    #pcm-flush-btn,
    input.flushcache[type="submit"] {
        background:#dd3a03 !important;
        color:#fff !important;
        transition:background .2s,box-shadow .2s,transform .1s !important;
    }
    #pcm-flush-btn:hover,
    input.flushcache[type="submit"]:hover {
        background:#03fcc2 !important;
        color:#040024 !important;
        box-shadow:0 4px 14px rgba(3,252,194,.45) !important;
        transform:translateY(-1px) !important;
    }
    .nav-tab-hidden { display:none !important; }
    code { background:#f1f5f9;padding:1px 5px;border-radius:4px;font-size:11.5px;color:#dd3a03; }
    </style>
    <?php
}

// ─── Footer ──────────────────────────────────────────────────────────────────
// Plugin version from the main file header (no constant to keep in sync).
function pcm_plugin_version() {
    static $v = null;
    if ( null === $v ) {
        $data = get_file_data( plugin_dir_path( dirname( __FILE__ ) ) . 'pressable-cache-management.php', array( 'Version' => 'Version' ) );
        $v    = ! empty( $data['Version'] ) ? $data['Version'] : '';
    }
    return $v;
}

// Where feedback goes: a prefilled GitHub issue on the plugin repo (public, issues on).
function pcm_feedback_url() {
    global $wp_version;
    $title = 'Feedback: Pressable Cache Management ' . pcm_plugin_version();
    $body  = "Plugin version: " . pcm_plugin_version() . "\nWordPress: " . $wp_version . "\n\nWhat happened, or what would you like changed?\n";
    return 'https://github.com/pressable/pressable-cache-management/issues/new?title=' . rawurlencode( $title ) . '&body=' . rawurlencode( $body );
}

function pcm_support_url() {
    return 'https://my.pressable.com/help';
}

// Right side of the admin footer: the plugin version instead of the WordPress version.
function pcm_footer_version( $text ) {
    if ( isset( $_GET['page'] ) && 'pressable_cache_management' === sanitize_key( $_GET['page'] ) ) {
        return esc_html( 'Pressable Cache Management v' . pcm_plugin_version() );
    }
    return $text;
}
add_filter( 'update_footer', 'pcm_footer_version', 11 );

function pcm_footer_msg() {
    if ( 'not-exists' === get_option('remove_pressable_branding_tab_options','not-exists') ) {
        add_option('remove_pressable_branding_tab_options','');
        update_option('remove_pressable_branding_tab_options', array('branding_on_off_radio_button'=>'enable'));
    }
    add_filter('admin_footer_text','pcm_replace_default_footer');
}

function pcm_replace_default_footer($footer_text) {
    if ( is_admin() && isset($_GET['page']) && sanitize_key( $_GET['page'] ) === 'pressable_cache_management' ) {
        $opts              = get_option('remove_pressable_branding_tab_options');
        $branding_disabled = $opts && 'disable' === $opts['branding_on_off_radio_button'];

        if ( $branding_disabled ) {
            // Branding hidden: "Built with ♥" — heart links to branding settings page
            return 'Built with <a href="admin.php?page=pressable_cache_management&tab=remove_pressable_branding_tab" title="Show or Hide Plugin Branding" style="text-decoration:none;"><span style="color:#03fcc2;font-size:18px;transition:opacity .2s;" onmouseover="this.style.opacity=\'0.7\'" onmouseout="this.style.opacity=\'1\'">&#x2665;</span></a>' . ' <span style="color:#cbd5e1;">&middot;</span> <a href="' . esc_url( pcm_feedback_url() ) . '" target="_blank" rel="noopener noreferrer" style="color:#dd3a03;text-decoration:none;font-weight:600;">' . esc_html__( 'Send feedback', 'pressable_cache_management' ) . '</a> <span style="color:#cbd5e1;">&middot;</span> <a href="' . esc_url( pcm_support_url() ) . '" target="_blank" rel="noopener noreferrer" style="color:#dd3a03;text-decoration:none;font-weight:600;">' . esc_html__( 'Pressable Support', 'pressable_cache_management' ) . '</a>';
        } else {
            // Branding shown: full credit — heart links to branding settings page
            return 'Built with <a href="admin.php?page=pressable_cache_management&tab=remove_pressable_branding_tab" title="Show or Hide Plugin Branding" style="text-decoration:none;"><span style="color:#dd3a03;font-size:20px;transition:opacity .2s;" onmouseover="this.style.opacity=\'0.7\'" onmouseout="this.style.opacity=\'1\'">&#x2665;</span></a> by The Pressable CS Team.' . ' <span style="color:#cbd5e1;">&middot;</span> <a href="' . esc_url( pcm_feedback_url() ) . '" target="_blank" rel="noopener noreferrer" style="color:#dd3a03;text-decoration:none;font-weight:600;">' . esc_html__( 'Send feedback', 'pressable_cache_management' ) . '</a> <span style="color:#cbd5e1;">&middot;</span> <a href="' . esc_url( pcm_support_url() ) . '" target="_blank" rel="noopener noreferrer" style="color:#dd3a03;text-decoration:none;font-weight:600;">' . esc_html__( 'Pressable Support', 'pressable_cache_management' ) . '</a>';
        }
    }
    return $footer_text;
}
add_action('admin_init','pcm_footer_msg');
