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
// WHY BROWSER-SIDE: wp_remote_get() is a server-side loopback request. Pressable's
// infrastructure routes loopback requests directly to PHP, bypassing the Batcache/CDN
// layer entirely — so x-nananana is never present regardless of real cache state.
// The browser is the only client that sees the actual CDN response headers.
// SOLUTION: JS fetches the homepage with cache:reload (forces a fresh CDN response)
// and reads x-nananana directly, then reports the result back to PHP via AJAX.
// PHP only stores/returns the transient — it never probes the URL itself.

function pcm_get_batcache_status() {
    $cached = get_transient( 'pcm_batcache_status' );
    return ( $cached !== false ) ? $cached : 'unknown';
}

// ── AJAX: browser reports the header value it observed ───────────────────────
function pcm_ajax_report_batcache_header() {
    check_ajax_referer( 'pcm_batcache_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }

    $raw = isset( $_POST['x_nananana'] ) ? sanitize_text_field( wp_unslash( $_POST['x_nananana'] ) ) : '';
    $val = strtolower( trim( $raw ) );

    if ( strpos( $val, 'batcache' ) !== false ) {
        $status = 'active';
    } elseif ( isset( $_POST['is_cloudflare'] ) && $_POST['is_cloudflare'] === '1' ) {
        $status = 'cloudflare';
    } else {
        $status = 'broken';
    }

    // Active: cache 5 min (stable). Broken: cache 2 min to limit probe frequency.
    $ttl = ( $status === 'active' ) ? 300 : 120;
    set_transient( 'pcm_batcache_status', $status, $ttl );

    $labels = array(
        'active'     => __( 'Batcache Active',     'pressable_cache_management' ),
        'cloudflare' => __( 'Cloudflare Detected', 'pressable_cache_management' ),
        'broken'     => __( 'Batcache Broken',     'pressable_cache_management' ),
    );

    wp_send_json_success( array(
        'status' => $status,
        'label'  => $labels[ $status ],
    ) );
}
add_action( 'wp_ajax_pcm_report_batcache_header', 'pcm_ajax_report_batcache_header' );

// ── AJAX: return current stored status (for badge refresh without re-fetching) ─
function pcm_ajax_get_batcache_status() {
    check_ajax_referer( 'pcm_batcache_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }

    $status = pcm_get_batcache_status();
    $labels = array(
        'active'     => __( 'Batcache Active',     'pressable_cache_management' ),
        'cloudflare' => __( 'Cloudflare Detected', 'pressable_cache_management' ),
        'broken'     => __( 'Batcache Broken',     'pressable_cache_management' ),
        'unknown'    => __( 'Batcache Broken',     'pressable_cache_management' ),
    );

    wp_send_json_success( array(
        'status' => $status,
        'label'  => isset( $labels[ $status ] ) ? $labels[ $status ] : $labels['broken'],
    ) );
}
add_action( 'wp_ajax_pcm_get_batcache_status', 'pcm_ajax_get_batcache_status' );

// Keep the old action name as an alias so any cached JS still works
add_action( 'wp_ajax_pcm_refresh_batcache_status', 'pcm_ajax_get_batcache_status' );

/**
 * Clear the cached status immediately after any cache flush
 * so the badge re-checks on next page load.
 */
function pcm_clear_batcache_status_transient() {
    delete_transient( 'pcm_batcache_status' );
}
add_action( 'pcm_after_object_cache_flush', 'pcm_clear_batcache_status_transient' );
add_action( 'pcm_after_batcache_flush',     'pcm_clear_batcache_status_transient' );
// Also clear when edge cache is fully purged — Batcache is implicitly invalidated too,
// so the next probe will correctly detect the transitional 'broken' state.
add_action( 'pcm_after_edge_cache_purge',   'pcm_clear_batcache_status_transient' );

// ─── Main page ───────────────────────────────────────────────────────────────
function pressable_cache_management_display_settings_page() {
    if ( ! current_user_can('manage_options') ) return;

    $tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : null;

    $branding_opts  = get_option('remove_pressable_branding_tab_options');
    $show_branding  = ! ( $branding_opts && 'disable' == $branding_opts['branding_on_off_radio_button'] );

    wp_enqueue_style( 'pressable_cache_management',
        plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/style.css', array(), '3.0.0', 'screen' );
    wp_enqueue_style( 'pcm-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap', array(), null );
    ?>
    <div class="wrap" style="background:#f0f2f5;margin-left:-20px;margin-right:-20px;padding:24px 28px 40px;min-height:calc(100vh - 32px);font-family:'Inter',sans-serif;">
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

    <?php if ( $tab === null ) :
        $options = get_option('pressable_cache_management_options');

        // Batcache status badge
        $bc_status = pcm_get_batcache_status();
        $bc_label  = $bc_status === 'active' ? __( 'Batcache Active', 'pressable_cache_management' ) : ( $bc_status === 'cloudflare' ? __( 'Cloudflare Detected', 'pressable_cache_management' ) : __( 'Batcache Broken', 'pressable_cache_management' ) );
        $bc_class  = $bc_status === 'active' ? 'active' : 'broken';
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
            <?php endif; ?>
        </div>
        <div style="display:flex;align-items:center;gap:6px;">
            <span class="pcm-batcache-status <?php echo esc_attr($bc_class); ?>" id="pcm-bc-badge">
                <span class="pcm-dot" id="pcm-bc-dot"></span>
                <span id="pcm-bc-label"><?php echo esc_html($bc_label); ?></span>
                <button id="pcm-bc-refresh" title="<?php esc_attr_e('Re-check Batcache status', 'pressable_cache_management'); ?>"
                        style="background:none;border:none;cursor:pointer;padding:0 0 0 6px;line-height:1;opacity:.6;font-size:13px;vertical-align:middle;"
                        onclick="pcmRefreshBatcacheStatus()">&#x21BB;</button>
            </span>
            <span class="pcm-bc-tooltip-wrap" style="position:relative;display:inline-flex;align-items:center;">
                <span style="width:16px;height:16px;border-radius:50%;background:#e2e8f0;color:#64748b;font-size:10px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;cursor:default;font-family:'Inter',sans-serif;line-height:1;flex-shrink:0;" aria-label="Batcache info">&#x3F;</span>
                <span class="pcm-bc-tooltip" style="display:none;position:absolute;right:0;top:24px;width:270px;background:#1e293b;color:#f1f5f9;font-size:11.5px;line-height:1.55;padding:10px 13px;border-radius:8px;box-shadow:0 4px 16px rgba(0,0,0,.18);z-index:9999;font-family:'Inter',sans-serif;font-weight:400;">
                    <?php echo esc_html__( 'Use the refresh button to manually check your cache status. If the cache status remains broken for more than 4 minutes after two visits are recorded on your site, it is likely that caching is failing due to cookie interference from your plugin, theme or custom code.', 'pressable_cache_management' ); ?>
                    <span style="position:absolute;right:8px;top:-5px;width:0;height:0;border-left:5px solid transparent;border-right:5px solid transparent;border-bottom:5px solid #1e293b;"></span>
                </span>
            </span>
        </div>
        <script>
        var pcmBatcacheNonce = '<?php echo esc_js( wp_create_nonce('pcm_batcache_nonce') ); ?>';
        var pcmSiteUrl       = '<?php echo esc_js( trailingslashit( get_site_url() ) ); ?>';

        // WHY BROWSER-SIDE FETCH:
        // wp_remote_get() is a server-side loopback. Pressable routes loopbacks
        // directly to PHP, bypassing the Batcache/CDN layer, so x-nananana is
        // never returned regardless of real cache state.
        // The browser is the only client that sees the actual CDN response headers.
        // We fetch the homepage from JS, read x-nananana directly, then POST the
        // result to PHP which stores it in the transient.

        // Apply AJAX response to the badge DOM
        function pcmApplyStatus(res) {
            if (!res || !res.success) return null;
            var badge = document.getElementById('pcm-bc-badge');
            var label = document.getElementById('pcm-bc-label');
            if (!badge || !label) return null;
            label.textContent = res.data.label;
            ['active','broken','cloudflare'].forEach(function(cls) {
                badge.classList.remove(cls);
            });
            badge.classList.add(res.data.status === 'active' ? 'active' : 'broken');
            return res.data.status;
        }

        // Core: browser fetches homepage, reads header, reports to PHP.
        // cache:'reload' bypasses browser cache for a fresh CDN response.
        // Pragma: no-cache forces Pressable's Atomic Edge Cache to BYPASS (x-ac: BYPASS).
        function pcmProbeAndReport(onDone) {
            fetch(pcmSiteUrl, {
                method: 'GET',
                cache: 'reload',
                credentials: 'omit',
                redirect: 'follow',
                headers: { 'Pragma': 'no-cache' },
            })
            .then(function(resp) {
                var xNananana    = resp.headers.get('x-nananana') || '';
                var serverHdr    = resp.headers.get('server') || '';
                var isCloudflare = serverHdr.toLowerCase().indexOf('cloudflare') !== -1 ? '1' : '0';
                var body = 'action=pcm_report_batcache_header'
                         + '&nonce='         + encodeURIComponent(pcmBatcacheNonce)
                         + '&x_nananana='    + encodeURIComponent(xNananana)
                         + '&is_cloudflare=' + isCloudflare;
                return fetch(ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: body,
                });
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                var status = pcmApplyStatus(res);
                if (typeof onDone === 'function') onDone(status);
            })
            .catch(function() {
                if (typeof onDone === 'function') onDone(null);
            });
        }

        // Manual refresh button
        function pcmRefreshBatcacheStatus() {
            var btn   = document.getElementById('pcm-bc-refresh');
            var label = document.getElementById('pcm-bc-label');
            btn.style.opacity = '0.3';
            btn.disabled = true;
            label.textContent = '<?php echo esc_js(__('Checking…', 'pressable_cache_management')); ?>';
            pcmProbeAndReport(function() {
                btn.style.opacity = '0.6';
                btn.disabled = false;
            });
        }

        // Auto-poll: re-probe every 60s while status is broken (up to 5 min, 5 attempts max)
        // Reduced from 15s/12 retries to avoid excessive admin-ajax.php load.
        var pcmPollTimer = null, pcmPollCount = 0, pcmPollMax = 5;
        function pcmStartRecoveryPoll() {
            clearInterval(pcmPollTimer);
            pcmPollCount = 0;
            pcmPollTimer = setInterval(function() {
                pcmPollCount++;
                if (pcmPollCount > pcmPollMax) { clearInterval(pcmPollTimer); return; }
                pcmProbeAndReport(function(status) {
                    if (status === 'active') {
                        clearInterval(pcmPollTimer);
                    }
                });
            }, 60000);
        }

        // Start polling immediately if badge is broken on page load.
        <?php if ( $bc_status !== 'active' ) : ?>
        pcmStartRecoveryPoll();
        <?php endif; ?>
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

    <!-- ── 2-column grid ── -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">

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
            </div>

            <!-- Automated Rules -->
            <div class="pcm-card">
                <form action="options.php" method="post" id="pcm-main-settings-form">
                <?php settings_fields('pressable_cache_management_options'); ?>
                <h3 class="pcm-card-title"><?php echo esc_html__( 'Automated Rules', 'pressable_cache_management' ); ?></h3>

                <?php
                $rules = array(
                    'flush_cache_theme_plugin_checkbox' => array(
                        'title' => __( 'Flush Cache on Plugin/Theme Update', 'pressable_cache_management' ) . ' &#x1F50C;',
                        'desc'  => __( 'Flush cache automatically on plugin & theme update.', 'pressable_cache_management' ),
                        'ts'    => get_option('flush-cache-theme-plugin-time-stamp'),
                    ),
                    'flush_cache_page_edit_checkbox' => array(
                        'title' => __( 'Flush Cache on Post/Page Edit', 'pressable_cache_management' ) . ' &#x1F4DD;',
                        'desc'  => __( 'Flush cache automatically when page/post/post_types are updated.', 'pressable_cache_management' ),
                        'ts'    => get_option('flush-cache-page-edit-time-stamp'),
                    ),
                    'flush_cache_on_comment_delete_checkbox' => array(
                        'title' => __( 'Flush Cache on Comment Delete', 'pressable_cache_management' ) . ' &#x1F4AC;',
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

        function getVals(){ return hidden.value ? hidden.value.split(',').map(s=>s.trim()).filter(Boolean) : []; }
        function syncHidden(v){ hidden.value = v.join(', '); }

        function addChip(val){
            val = val.trim(); if (!val) return;
            var vals = getVals();
            if (vals.indexOf(val) !== -1) return;
            vals.push(val); syncHidden(vals); renderChip(val);
        }
        function removeChip(val){ syncHidden(getVals().filter(v=>v!==val)); }

        function renderChip(val){
            var c = document.createElement('span');
            c.className = 'pcm-chip'; c.dataset.value = val;
            c.innerHTML = val + ' <button type="button" class="pcm-chip-remove" title="Remove">&#xD7;</button>';
            c.querySelector('.pcm-chip-remove').addEventListener('click',function(){ removeChip(val); c.remove(); });
            wrap.appendChild(c);
        }

        wrap.querySelectorAll('.pcm-chip-remove').forEach(function(btn){
            btn.addEventListener('click',function(){ var c=btn.closest('.pcm-chip'); removeChip(c.dataset.value); c.remove(); });
        });

        input.addEventListener('keydown',function(e){
            if (e.key==='Enter'||e.key===','){
                e.preventDefault(); var r=input.value.replace(/,/g,'').trim(); if(r){addChip(r);input.value='';}
            }
        });
        input.addEventListener('blur',function(){ var r=input.value.replace(/,/g,'').trim(); if(r){addChip(r);input.value='';} });
        input.addEventListener('paste',function(e){
            e.preventDefault();
            var p=(e.clipboardData||window.clipboardData).getData('text');
            p.split(',').forEach(function(v){ var t=v.trim(); if(t) addChip(t); });
            input.value='';
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

    </div><!-- /card -->
    </div><!-- /max-width -->

    <script>
    jQuery(document).ready(function($){
        var wrapper  = $('#edge-cache-control-wrapper');
        var purgeBtn = $('#purge-edge-cache-button-input');
        if (wrapper.length && !wrapper.data('ec-checked')) {
            wrapper.data('ec-checked', true);
            $.ajax({
                url: ajaxurl, type: 'POST',
                data: { action: 'pcm_check_edge_cache_status' },
                success: function(r) {
                    if (r.success && r.data.html_controls_enable_disable) {
                        wrapper.html(r.data.html_controls_enable_disable);
                        if (r.data.enabled) {
                            purgeBtn.removeClass('ec-disabled-btn')
                                    .prop('disabled', false)
                                    .css({ opacity:1, cursor:'pointer', pointerEvents:'auto' });
                        }
                    } else {
                        var msg = (r.data && r.data.message) ? r.data.message : '<?php echo esc_js( __( 'Failed to retrieve status.', 'pressable_cache_management' ) ); ?>';
                        wrapper.html('<p style="color:#ef4444;font-size:13px;margin:0;">'+msg+'</p>');
                    }
                },
                error: function() {
                    wrapper.html('<p style="color:#ef4444;font-size:13px;margin:0;"><?php echo esc_js( __( 'Could not connect to server.', 'pressable_cache_management' ) ); ?></p>');
                }
            });
        }
    });
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
            return 'Built with <a href="admin.php?page=pressable_cache_management&tab=remove_pressable_branding_tab" title="Show or Hide Plugin Branding" style="text-decoration:none;"><span style="color:#03fcc2;font-size:18px;transition:opacity .2s;" onmouseover="this.style.opacity=\'0.7\'" onmouseout="this.style.opacity=\'1\'">&#x2665;</span></a>';
        } else {
            // Branding shown: full credit — heart links to branding settings page
            return 'Built with <a href="admin.php?page=pressable_cache_management&tab=remove_pressable_branding_tab" title="Show or Hide Plugin Branding" style="text-decoration:none;"><span style="color:#dd3a03;font-size:20px;transition:opacity .2s;" onmouseover="this.style.opacity=\'0.7\'" onmouseout="this.style.opacity=\'1\'">&#x2665;</span></a> by The Pressable CS Team.';
        }
    }
    return $footer_text;
}
add_action('admin_init','pcm_footer_msg');
