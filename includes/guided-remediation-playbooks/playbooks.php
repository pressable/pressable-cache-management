<?php
/**
 * Guided Remediation Playbooks (Pillar 8).
 *
 * @package PressableCacheManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Feature gate for guided remediation playbooks.
 *
 * @return bool
 */
function pcm_guided_playbooks_is_enabled() {
    $enabled = false;

    return (bool) apply_filters( 'pcm_enable_guided_playbooks', $enabled );
}

/**
 * Playbook repository backed by bundled markdown files.
 */
class PCM_Playbook_Repository {
    /**
     * @return string
     */
    protected function get_playbooks_dir() {
        return plugin_dir_path( __FILE__ ) . 'playbooks/';
    }

    /**
     * @return array
     */
    public function list_playbooks() {
        $paths = glob( $this->get_playbooks_dir() . '*.md' );

        if ( ! is_array( $paths ) ) {
            return array();
        }

        $rows = array();

        foreach ( $paths as $path ) {
            $playbook = $this->read_playbook_file( $path );
            if ( ! empty( $playbook ) ) {
                $rows[] = $playbook;
            }
        }

        return $rows;
    }

    /**
     * @param string $playbook_id Playbook ID.
     *
     * @return array|null
     */
    public function get_by_id( $playbook_id ) {
        $playbook_id = sanitize_key( $playbook_id );

        foreach ( $this->list_playbooks() as $playbook ) {
            if ( isset( $playbook['meta']['playbook_id'] ) && $playbook['meta']['playbook_id'] === $playbook_id ) {
                return $playbook;
            }
        }

        return null;
    }

    /**
     * @param string $rule_id Rule ID.
     *
     * @return array|null
     */
    public function get_by_rule_id( $rule_id ) {
        $rule_id = sanitize_key( $rule_id );

        foreach ( $this->list_playbooks() as $playbook ) {
            $mapped = isset( $playbook['meta']['rule_ids'] ) && is_array( $playbook['meta']['rule_ids'] ) ? $playbook['meta']['rule_ids'] : array();
            if ( in_array( $rule_id, $mapped, true ) ) {
                return $playbook;
            }
        }

        return null;
    }

    /**
     * @param string $path Path.
     *
     * @return array
     */
    protected function read_playbook_file( $path ) {
        $content = file_get_contents( $path );

        if ( ! is_string( $content ) || '' === trim( $content ) ) {
            return array();
        }

        $meta = $this->parse_meta_json( $content );

        if ( empty( $meta['playbook_id'] ) ) {
            return array();
        }

        $body = preg_replace( '/\A\/\*PCM_PLAYBOOK_META\n.*?\nPCM_PLAYBOOK_META\*\/\n/s', '', $content );

        return array(
            'meta' => $meta,
            'body' => is_string( $body ) ? trim( $body ) : '',
        );
    }

    /**
     * @param string $content File content.
     *
     * @return array
     */
    protected function parse_meta_json( $content ) {
        if ( ! preg_match( '/\A\/\*PCM_PLAYBOOK_META\n(.*?)\nPCM_PLAYBOOK_META\*\//s', $content, $matches ) ) {
            return array();
        }

        $json = json_decode( trim( $matches[1] ), true );

        if ( ! is_array( $json ) ) {
            return array();
        }

        $json['playbook_id'] = isset( $json['playbook_id'] ) ? sanitize_key( $json['playbook_id'] ) : '';
        $json['version']     = isset( $json['version'] ) ? sanitize_text_field( $json['version'] ) : '1.0.0';
        $json['severity']    = isset( $json['severity'] ) ? sanitize_key( $json['severity'] ) : 'warning';
        $json['title']       = isset( $json['title'] ) ? sanitize_text_field( $json['title'] ) : '';

        $json['rule_ids'] = isset( $json['rule_ids'] ) && is_array( $json['rule_ids'] )
            ? array_values( array_filter( array_map( 'sanitize_key', $json['rule_ids'] ) ) )
            : array();

        $json['audiences'] = isset( $json['audiences'] ) && is_array( $json['audiences'] )
            ? array_values( array_filter( array_map( 'sanitize_key', $json['audiences'] ) ) )
            : array();

        return $json;
    }
}


/**
 * Capability guard for playbook AJAX handlers.
 *
 * @return bool
 */
function pcm_playbooks_ajax_can_manage() {
    if ( function_exists( 'pcm_current_user_can' ) ) {
        return (bool) pcm_current_user_can( 'pcm_view_diagnostics' );
    }

    return current_user_can( 'manage_options' );
}

/**
 * Persisted progress storage for playbook checklist + verification.
 */
class PCM_Playbook_Progress_Store {
    const OPTION_KEY = 'pcm_playbook_progress_v1';

    /**
     * @return array
     */
    protected function all() {
        $raw = get_option( self::OPTION_KEY, array() );

        return is_array( $raw ) ? $raw : array();
    }

    /**
     * @param array $rows Rows.
     *
     * @return void
     */
    protected function save_all( $rows ) {
        update_option( self::OPTION_KEY, is_array( $rows ) ? $rows : array(), false );
    }

    /**
     * @param string $playbook_id Playbook identifier.
     *
     * @return array
     */
    public function get_state( $playbook_id ) {
        $playbook_id = sanitize_key( $playbook_id );
        $rows        = $this->all();

        if ( empty( $rows[ $playbook_id ] ) || ! is_array( $rows[ $playbook_id ] ) ) {
            return array(
                'checklist'    => array(),
                'verification' => array(),
            );
        }

        return $rows[ $playbook_id ];
    }

    /**
     * @param string $playbook_id Playbook identifier.
     * @param array  $checklist   Checklist values.
     *
     * @return array
     */
    public function save_checklist( $playbook_id, $checklist ) {
        $playbook_id = sanitize_key( $playbook_id );
        $rows        = $this->all();
        $state       = isset( $rows[ $playbook_id ] ) && is_array( $rows[ $playbook_id ] ) ? $rows[ $playbook_id ] : array();
        $clean       = array();

        if ( is_array( $checklist ) ) {
            foreach ( $checklist as $step => $complete ) {
                $step          = sanitize_key( $step );
                $clean[ $step ] = (bool) $complete;
            }
        }

        $state['checklist']    = $clean;
        $state['updated_at']   = gmdate( 'c' );
        $rows[ $playbook_id ]  = $state;

        $this->save_all( $rows );

        return $state;
    }

    /**
     * @param string $playbook_id Playbook identifier.
     * @param array  $verification Verification payload.
     *
     * @return array
     */
    public function save_verification( $playbook_id, $verification ) {
        $playbook_id = sanitize_key( $playbook_id );
        $rows        = $this->all();
        $state       = isset( $rows[ $playbook_id ] ) && is_array( $rows[ $playbook_id ] ) ? $rows[ $playbook_id ] : array();

        $state['verification'] = array(
            'status'      => ! empty( $verification['status'] ) ? sanitize_key( $verification['status'] ) : 'unknown',
            'run_id'      => isset( $verification['run_id'] ) ? absint( $verification['run_id'] ) : 0,
            'rule_id'     => isset( $verification['rule_id'] ) ? sanitize_key( $verification['rule_id'] ) : '',
            'checked_at'  => gmdate( 'c' ),
            'message'     => isset( $verification['message'] ) ? sanitize_text_field( $verification['message'] ) : '',
        );
        $state['updated_at']   = gmdate( 'c' );
        $rows[ $playbook_id ]  = $state;

        $this->save_all( $rows );

        return $state;
    }
}

/**
 * Rule-to-playbook lookup service.
 */
class PCM_Playbook_Lookup_Service {
    /** @var PCM_Playbook_Repository */
    protected $repository;

    public function __construct( $repository = null ) {
        $this->repository = $repository ? $repository : new PCM_Playbook_Repository();
    }

    /**
     * @param string $rule_id Rule ID.
     *
     * @return array
     */
    public function lookup_for_finding( $rule_id ) {
        if ( ! pcm_guided_playbooks_is_enabled() ) {
            return array(
                'available' => false,
                'reason'    => 'feature_disabled',
            );
        }

        $playbook = $this->repository->get_by_rule_id( $rule_id );

        if ( empty( $playbook ) ) {
            return array(
                'available' => false,
                'reason'    => 'no_playbook',
            );
        }

        return array(
            'available' => true,
            'playbook'  => $playbook,
        );
    }
}

/**
 * Safe markdown renderer for admin panel.
 */
class PCM_Playbook_Renderer {
    /**
     * @param string $markdown Markdown.
     *
     * @return string
     */
    public function render( $markdown ) {
        $markdown = (string) $markdown;
        $escaped  = esc_html( $markdown );

        $escaped = preg_replace( '/^###\s+(.+)$/m', '<h4>$1</h4>', $escaped );
        $escaped = preg_replace( '/^##\s+(.+)$/m', '<h3>$1</h3>', $escaped );
        $escaped = preg_replace( '/^#\s+(.+)$/m', '<h2>$1</h2>', $escaped );
        $escaped = preg_replace( '/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped );
        $escaped = preg_replace( '/`([^`]+)`/', '<code>$1</code>', $escaped );

        $escaped = preg_replace_callback(
            '/(?:^|\n)-\s+(.+?)(?=\n[^-]|\n\z)/s',
            static function ( $matches ) {
                $items = preg_split( '/\n-\s+/', trim( $matches[0] ) );
                $items = array_filter( array_map( 'trim', $items ) );
                if ( empty( $items ) ) {
                    return $matches[0];
                }

                $out = '<ul>';
                foreach ( $items as $item ) {
                    $item = preg_replace( '/^-\s+/', '', $item );
                    $out .= '<li>' . $item . '</li>';
                }
                $out .= '</ul>';

                return "\n" . $out . "\n";
            },
            $escaped
        );

        $escaped = nl2br( $escaped );

        return wp_kses(
            $escaped,
            array(
                'h2'     => array(),
                'h3'     => array(),
                'h4'     => array(),
                'strong' => array(),
                'code'   => array(),
                'ul'     => array(),
                'li'     => array(),
                'br'     => array(),
            )
        );
    }
}

/**
 * Render helper with tabs and localStorage checklist wiring.
 *
 * @param array $playbook Playbook.
 *
 * @return string
 */
function pcm_render_playbook_panel( $playbook ) {
    if ( empty( $playbook['meta']['playbook_id'] ) ) {
        return '';
    }

    $renderer    = new PCM_Playbook_Renderer();
    $playbook_id = $playbook['meta']['playbook_id'];
    $title       = isset( $playbook['meta']['title'] ) ? $playbook['meta']['title'] : $playbook_id;
    $content     = $renderer->render( isset( $playbook['body'] ) ? $playbook['body'] : '' );

    $panel_id = 'pcm-playbook-' . $playbook_id;
    $ajax_url = admin_url( 'admin-ajax.php' );
    $nonce    = wp_create_nonce( 'pcm_cacheability_scan' );

    ob_start();
    ?>
    <div id="<?php echo esc_attr( $panel_id ); ?>" class="pcm-playbook-panel">
        <h3><?php echo esc_html( $title ); ?></h3>
        <p>
            <strong><?php esc_html_e( 'Severity:', 'pressable_cache_management' ); ?></strong>
            <?php echo esc_html( isset( $playbook['meta']['severity'] ) ? $playbook['meta']['severity'] : 'warning' ); ?>
            &nbsp;|&nbsp;
            <strong><?php esc_html_e( 'Version:', 'pressable_cache_management' ); ?></strong>
            <?php echo esc_html( isset( $playbook['meta']['version'] ) ? $playbook['meta']['version'] : '1.0.0' ); ?>
        </p>
        <div class="pcm-playbook-tabs" style="margin:12px 0;">
            <button type="button" class="button" onclick="pcmPlaybookSwitchTab('<?php echo esc_js( $panel_id ); ?>','quick')"><?php esc_html_e( 'Quick Fix', 'pressable_cache_management' ); ?></button>
            <button type="button" class="button" onclick="pcmPlaybookSwitchTab('<?php echo esc_js( $panel_id ); ?>','technical')"><?php esc_html_e( 'Technical', 'pressable_cache_management' ); ?></button>
            <button type="button" class="button" onclick="pcmPlaybookSwitchTab('<?php echo esc_js( $panel_id ); ?>','verify')"><?php esc_html_e( 'Verify', 'pressable_cache_management' ); ?></button>
        </div>
        <div class="pcm-playbook-content"><?php echo wp_kses_post( $content ); ?></div>
        <hr />
        <div class="pcm-playbook-checklist">
            <label><input type="checkbox" data-pcm-check="1" /> <?php esc_html_e( 'Step 1 complete', 'pressable_cache_management' ); ?></label><br />
            <label><input type="checkbox" data-pcm-check="2" /> <?php esc_html_e( 'Step 2 complete', 'pressable_cache_management' ); ?></label><br />
            <label><input type="checkbox" data-pcm-check="3" /> <?php esc_html_e( 'Verification complete', 'pressable_cache_management' ); ?></label>
        </div>
        <p style="margin-top:10px;">
            <button type="button" class="button button-secondary" onclick="pcmPlaybookTriggerRescan()"><?php esc_html_e( 'Re-scan now', 'pressable_cache_management' ); ?></button>
        </p>
    </div>
    <script>
        (function(){
            var panelId = <?php echo wp_json_encode( $panel_id ); ?>;
            var ajaxUrl = <?php echo wp_json_encode( $ajax_url ); ?>;
            var nonce = <?php echo wp_json_encode( $nonce ); ?>;
            var key = 'pcm_playbook_checklist_' + panelId;
            var panel = document.getElementById(panelId);
            if (!panel) return;
            var checks = panel.querySelectorAll('input[data-pcm-check]');
            var saved = {};
            try { saved = JSON.parse(localStorage.getItem(key) || '{}'); } catch(e) { saved = {}; }
            checks.forEach(function(el){
                var id = el.getAttribute('data-pcm-check');
                if (saved[id]) el.checked = true;
                el.addEventListener('change', function(){
                    saved[id] = !!el.checked;
                    localStorage.setItem(key, JSON.stringify(saved));
                });
            });

            window.pcmPlaybookAjax = {
                url: ajaxUrl,
                nonce: nonce
            };
        })();

        function pcmPlaybookSwitchTab(panelId, tab) {
            void panelId; void tab;
        }

        function pcmPlaybookTriggerRescan() {
            var cfg = window.pcmPlaybookAjax || {};
            if (!cfg.url || !cfg.nonce) {
                alert('Unable to start scan: missing AJAX configuration.');
                return;
            }

            var params = new URLSearchParams();
            params.append('action', 'pcm_cacheability_scan_start');
            params.append('nonce', cfg.nonce);

            fetch(cfg.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: params.toString()
            })
            .then(function(response){ return response.json(); })
            .then(function(payload){
                if (!payload || !payload.success || !payload.data || !payload.data.run_id) {
                    throw new Error('Scan start failed.');
                }

                var runId = payload.data.run_id;
                var statusParams = new URLSearchParams();
                statusParams.append('action', 'pcm_cacheability_scan_status');
                statusParams.append('nonce', cfg.nonce);
                statusParams.append('run_id', String(runId));

                return fetch(cfg.url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: statusParams.toString()
                });
            })
            .then(function(response){ return response.json(); })
            .then(function(payload){
                var status = payload && payload.data && payload.data.run && payload.data.run.status ? payload.data.run.status : 'unknown';
                alert('Cacheability scan triggered. Current status: ' + status + '.');
            })
            .catch(function(){
                alert('Unable to trigger cacheability scan. Check permissions and feature flags.');
            });
        }
    </script>
    <?php

    return (string) ob_get_clean();
}


/**
 * Build a serialized playbook payload for UI use.
 *
 * @param array $playbook Playbook.
 *
 * @return array
 */
function pcm_playbook_build_payload( $playbook ) {
    $renderer = new PCM_Playbook_Renderer();

    return array(
        'meta'      => isset( $playbook['meta'] ) && is_array( $playbook['meta'] ) ? $playbook['meta'] : array(),
        'body'      => isset( $playbook['body'] ) ? (string) $playbook['body'] : '',
        'html_body' => $renderer->render( isset( $playbook['body'] ) ? $playbook['body'] : '' ),
    );
}

/**
 * AJAX: Lookup playbook for a finding rule.
 *
 * @return void
 */
function pcm_ajax_playbook_lookup() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_view_diagnostics' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( ! pcm_playbooks_ajax_can_manage() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $rule_id = isset( $_REQUEST['rule_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['rule_id'] ) ) : '';
    if ( '' === $rule_id ) {
        wp_send_json_error( array( 'message' => 'Missing rule_id.' ), 400 );
    }

    $lookup = new PCM_Playbook_Lookup_Service();
    $result = $lookup->lookup_for_finding( $rule_id );

    if ( empty( $result['available'] ) || empty( $result['playbook'] ) ) {
        wp_send_json_success( array( 'available' => false, 'reason' => isset( $result['reason'] ) ? $result['reason'] : 'no_playbook' ) );
    }

    $playbook = $result['playbook'];
    $store    = new PCM_Playbook_Progress_Store();
    $state    = $store->get_state( isset( $playbook['meta']['playbook_id'] ) ? $playbook['meta']['playbook_id'] : '' );

    if ( function_exists( 'pcm_audit_log' ) ) {
        pcm_audit_log( 'playbook_lookup', 'guided_playbooks', array( 'rule_id' => $rule_id ) );
    }

    wp_send_json_success(
        array(
            'available' => true,
            'playbook'  => pcm_playbook_build_payload( $playbook ),
            'progress'  => $state,
        )
    );
}
add_action( 'wp_ajax_pcm_playbook_lookup', 'pcm_ajax_playbook_lookup' );

/**
 * AJAX: Save playbook checklist progress.
 *
 * @return void
 */
function pcm_ajax_playbook_progress_save() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_view_diagnostics' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( ! pcm_playbooks_ajax_can_manage() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $playbook_id = isset( $_REQUEST['playbook_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['playbook_id'] ) ) : '';
    if ( '' === $playbook_id ) {
        wp_send_json_error( array( 'message' => 'Missing playbook_id.' ), 400 );
    }

    $checklist_raw = isset( $_REQUEST['checklist'] ) ? wp_unslash( $_REQUEST['checklist'] ) : '';
    $checklist     = is_string( $checklist_raw ) ? json_decode( $checklist_raw, true ) : array();

    if ( ! is_array( $checklist ) ) {
        $checklist = array();
    }

    $store = new PCM_Playbook_Progress_Store();
    $state = $store->save_checklist( $playbook_id, $checklist );

    if ( function_exists( 'pcm_audit_log' ) ) {
        pcm_audit_log( 'playbook_progress_saved', 'guided_playbooks', array( 'playbook_id' => $playbook_id ) );
    }

    wp_send_json_success( array( 'playbook_id' => $playbook_id, 'progress' => $state ) );
}
add_action( 'wp_ajax_pcm_playbook_progress_save', 'pcm_ajax_playbook_progress_save' );

/**
 * AJAX: Run post-fix verification for a playbook.
 *
 * @return void
 */
function pcm_ajax_playbook_verify() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_run_scans' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( ! pcm_playbooks_ajax_can_manage() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    if ( ! function_exists( 'pcm_cacheability_advisor_is_enabled' ) || ! pcm_cacheability_advisor_is_enabled() ) {
        wp_send_json_error( array( 'message' => 'Cacheability Advisor is disabled.' ), 400 );
    }

    $playbook_id = isset( $_REQUEST['playbook_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['playbook_id'] ) ) : '';
    $rule_id     = isset( $_REQUEST['rule_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['rule_id'] ) ) : '';
    if ( '' === $playbook_id || '' === $rule_id ) {
        wp_send_json_error( array( 'message' => 'Missing playbook_id or rule_id.' ), 400 );
    }

    $service    = new PCM_Cacheability_Advisor_Run_Service();
    $repository = new PCM_Cacheability_Advisor_Repository();
    $run_id     = $service->start_and_execute_scan();

    if ( ! $run_id ) {
        wp_send_json_error( array( 'message' => 'Unable to execute verification scan.' ), 500 );
    }

    $findings      = $repository->list_findings( $run_id );
    $rule_still_on = false;

    foreach ( $findings as $finding ) {
        if ( isset( $finding['rule_id'] ) && sanitize_key( $finding['rule_id'] ) === $rule_id ) {
            $rule_still_on = true;
            break;
        }
    }

    $status  = $rule_still_on ? 'failing' : 'passed';
    $message = $rule_still_on
        ? 'Rule is still present after verification scan.'
        : 'Rule did not appear in verification scan.';

    $store = new PCM_Playbook_Progress_Store();
    $state = $store->save_verification(
        $playbook_id,
        array(
            'status'  => $status,
            'run_id'  => $run_id,
            'rule_id' => $rule_id,
            'message' => $message,
        )
    );

    if ( function_exists( 'pcm_audit_log' ) ) {
        pcm_audit_log( 'playbook_verification_run', 'guided_playbooks', array( 'playbook_id' => $playbook_id, 'run_id' => (int) $run_id, 'status' => $status ) );
    }

    wp_send_json_success(
        array(
            'playbook_id' => $playbook_id,
            'run_id'      => (int) $run_id,
            'status'      => $status,
            'message'     => $message,
            'progress'    => $state,
        )
    );
}
add_action( 'wp_ajax_pcm_playbook_verify', 'pcm_ajax_playbook_verify' );
