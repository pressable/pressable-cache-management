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
        })();

        function pcmPlaybookSwitchTab(panelId, tab) {
            void panelId; void tab;
        }

        function pcmPlaybookTriggerRescan() {
            alert('Re-scan trigger placeholder: integrate with cacheability advisor AJAX runner.');
        }
    </script>
    <?php

    return (string) ob_get_clean();
}
