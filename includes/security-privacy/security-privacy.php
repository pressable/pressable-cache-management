<?php
/**
 * Permissions, Safety, and Privacy baseline (Pillar 9).
 *
 * @package PressableCacheManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Baseline is enabled by default so all modules can rely on it.
 *
 * @return bool
 */
function pcm_security_privacy_is_enabled() {
    return (bool) apply_filters( 'pcm_enable_security_privacy', true );
}

/**
 * Capability map for advanced diagnostics and actions.
 *
 * @return array
 */
function pcm_get_capability_matrix() {
    return array(
        'pcm_view_diagnostics'       => __( 'View diagnostics and reports', 'pressable_cache_management' ),
        'pcm_run_scans'              => __( 'Run cacheability scans', 'pressable_cache_management' ),
        'pcm_manage_redirect_rules'  => __( 'Manage redirect assistant rules', 'pressable_cache_management' ),
        'pcm_flush_cache_global'     => __( 'Execute global cache flushes', 'pressable_cache_management' ),
        'pcm_export_reports'         => __( 'Export diagnostic reports', 'pressable_cache_management' ),
        'pcm_manage_privacy_settings'=> __( 'Manage privacy and retention settings', 'pressable_cache_management' ),
    );
}

/**
 * Ensure admin role has PCM capabilities.
 *
 * @return void
 */
function pcm_register_default_capabilities() {
    if ( ! pcm_security_privacy_is_enabled() || ! is_admin() ) {
        return;
    }

    $version = (string) get_option( 'pcm_security_privacy_caps_version', '' );
    if ( '1.0.0' === $version ) {
        return;
    }

    $role = get_role( 'administrator' );
    if ( ! $role ) {
        return;
    }

    foreach ( array_keys( pcm_get_capability_matrix() ) as $cap ) {
        if ( ! $role->has_cap( $cap ) ) {
            $role->add_cap( $cap );
        }
    }

    update_option( 'pcm_security_privacy_caps_version', '1.0.0', false );
}
add_action( 'admin_init', 'pcm_register_default_capabilities' );

/**
 * Unified capability guard with admin fallback for compatibility.
 *
 * @param string $capability Capability.
 *
 * @return bool
 */
function pcm_current_user_can( $capability ) {
    if ( current_user_can( $capability ) ) {
        return true;
    }

    // Fallback keeps existing installs functional while capabilities roll out.
    return current_user_can( 'manage_options' );
}

/**
 * Privacy settings defaults.
 *
 * @return array
 */
function pcm_get_privacy_settings() {
    $defaults = array(
        'retention_days'       => 90,
        'redaction_level'      => 'standard',
        'export_restrictions'  => 'admin_only',
        'advanced_scan_opt_in' => false,
        'audit_log_enabled'    => true,
        'sensitive_keys'       => array( 'email', 'token', 'auth', 'authorization', 'password', 'pass', 'nonce', 'key', 'secret' ),
    );

    $stored = get_option( 'pcm_privacy_settings_v1', array() );
    if ( ! is_array( $stored ) ) {
        return $defaults;
    }

    $settings = wp_parse_args( $stored, $defaults );
    $settings['retention_days'] = max( 7, min( 365, absint( $settings['retention_days'] ) ) );
    $settings['redaction_level'] = in_array( $settings['redaction_level'], array( 'minimal', 'standard', 'strict' ), true )
        ? $settings['redaction_level']
        : 'standard';

    $settings['export_restrictions'] = in_array( $settings['export_restrictions'], array( 'admin_only', 'diagnostics_viewers' ), true )
        ? $settings['export_restrictions']
        : 'admin_only';
    $settings['advanced_scan_opt_in'] = ! empty( $settings['advanced_scan_opt_in'] );
    $settings['audit_log_enabled']    = ! empty( $settings['audit_log_enabled'] );
    $settings['sensitive_keys']       = array_values( array_filter( array_map( 'sanitize_key', (array) $settings['sensitive_keys'] ) ) );

    return $settings;
}

/**
 * Recursive redaction middleware for telemetry and exports.
 *
 * @param mixed $value Value to redact.
 * @param array $settings Settings.
 *
 * @return mixed
 */
function pcm_privacy_redact_value( $value, $settings = array() ) {
    $settings = wp_parse_args( $settings, pcm_get_privacy_settings() );

    if ( is_array( $value ) ) {
        $output = array();
        foreach ( $value as $key => $child ) {
            $normalized_key = sanitize_key( is_string( $key ) ? $key : (string) $key );
            if ( in_array( $normalized_key, $settings['sensitive_keys'], true ) ) {
                $output[ $key ] = pcm_privacy_mask_scalar( $child, $settings['redaction_level'] );
                continue;
            }

            if ( 'set-cookie' === $normalized_key || 'cookie' === $normalized_key ) {
                $output[ $key ] = pcm_privacy_mask_cookie_values( $child );
                continue;
            }

            $output[ $key ] = pcm_privacy_redact_value( $child, $settings );
        }

        return $output;
    }

    return $value;
}

/**
 * @param mixed  $value Value.
 * @param string $redaction_level Level.
 *
 * @return string
 */
function pcm_privacy_mask_scalar( $value, $redaction_level = 'standard' ) {
    $string = is_scalar( $value ) ? (string) $value : wp_json_encode( $value );
    $string = is_string( $string ) ? $string : '';

    if ( 'strict' === $redaction_level ) {
        return '[redacted]';
    }

    if ( '' === $string ) {
        return '[redacted]';
    }

    $hash = hash( 'sha256', $string );

    if ( 'minimal' === $redaction_level ) {
        return '[masked:' . substr( $hash, 0, 6 ) . ']';
    }

    return '[redacted:' . substr( $hash, 0, 12 ) . ']';
}

/**
 * Mask cookie values but keep cookie names.
 *
 * @param mixed $cookie_value Raw cookie header value(s).
 *
 * @return mixed
 */
function pcm_privacy_mask_cookie_values( $cookie_value ) {
    if ( is_array( $cookie_value ) ) {
        return array_map( 'pcm_privacy_mask_cookie_values', $cookie_value );
    }

    $line = (string) $cookie_value;
    $first_part = strtok( $line, ';' );
    $pair = explode( '=', (string) $first_part, 2 );

    $name = isset( $pair[0] ) ? sanitize_key( trim( $pair[0] ) ) : 'cookie';

    return $name . '=[redacted]';
}

/**
 * Audit writer with tamper-evident sequence/hash chain.
 */
class PCM_Audit_Log_Service {
    /** @var string */
    protected $key = 'pcm_audit_log_v1';

    /** @var int */
    protected $max_rows = 2000;

    /**
     * @param string $action Action.
     * @param string $target Target.
     * @param array  $context Context.
     *
     * @return array
     */
    public function log( $action, $target = '', $context = array() ) {
        $rows = $this->all();
        $last = end( $rows );

        $sequence  = isset( $last['sequence_id'] ) ? absint( $last['sequence_id'] ) + 1 : 1;
        $prev_hash = isset( $last['entry_hash'] ) ? (string) $last['entry_hash'] : '';

        $entry = array(
            'sequence_id' => $sequence,
            'actor_id'    => get_current_user_id(),
            'action'      => sanitize_key( $action ),
            'target'      => sanitize_text_field( $target ),
            'context_json'=> pcm_privacy_redact_value( (array) $context ),
            'created_at'  => current_time( 'mysql', true ),
            'prev_hash'   => $prev_hash,
        );

        $entry['entry_hash'] = hash( 'sha256', wp_json_encode( $entry ) );

        $rows[] = $entry;
        update_option( $this->key, array_slice( $rows, -1 * $this->max_rows ), false );

        return $entry;
    }

    /**
     * @return array
     */
    public function all() {
        $rows = get_option( $this->key, array() );

        return is_array( $rows ) ? $rows : array();
    }

    /**
     * @return bool
     */
    public function verify_chain() {
        $rows      = $this->all();
        $prev_hash = '';

        foreach ( $rows as $row ) {
            if ( ! is_array( $row ) ) {
                return false;
            }

            $entry_hash = isset( $row['entry_hash'] ) ? (string) $row['entry_hash'] : '';
            $computed   = $row;
            unset( $computed['entry_hash'] );

            if ( isset( $row['prev_hash'] ) && (string) $row['prev_hash'] !== $prev_hash ) {
                return false;
            }

            if ( '' === $entry_hash || ! hash_equals( $entry_hash, hash( 'sha256', wp_json_encode( $computed ) ) ) ) {
                return false;
            }

            $prev_hash = $entry_hash;
        }

        return true;
    }
}

/**
 * Explicit confirmation token helper for risky actions.
 */
class PCM_Risky_Action_Confirmation_Service {
    /**
     * @param string $action Action key.
     *
     * @return string
     */
    public function issue_token( $action ) {
        $action = sanitize_key( $action );
        $token  = wp_generate_password( 32, false, false );
        set_transient( 'pcm_confirm_' . $action . '_' . get_current_user_id(), $token, MINUTE_IN_SECONDS * 10 );

        return $token;
    }

    /**
     * @param string $action Action key.
     * @param string $token  Token.
     *
     * @return bool
     */
    public function verify_token( $action, $token ) {
        $action = sanitize_key( $action );
        $token  = sanitize_text_field( $token );

        $key      = 'pcm_confirm_' . $action . '_' . get_current_user_id();
        $expected = get_transient( $key );

        if ( ! is_string( $expected ) || '' === $expected ) {
            return false;
        }

        $valid = hash_equals( $expected, $token );

        if ( $valid ) {
            delete_transient( $key );
        }

        return $valid;
    }
}

/**
 * Retention manager for existing telemetry stores.
 */
class PCM_Telemetry_Retention_Manager {
    /**
     * @return void
     */
    public function cleanup() {
        $settings = pcm_get_privacy_settings();
        $days     = $settings['retention_days'];

        $this->prune_option_rows( 'pcm_metric_rollups_v1', 'bucket_start', $days );
        $this->prune_option_rows( 'pcm_smart_purge_events_v1', 'timestamp', $days );
        $this->prune_option_rows( 'pcm_smart_purge_outcomes_v1', 'timestamp', $days );
        $this->prune_option_rows( 'pcm_audit_log_v1', 'created_at', $days );
    }

    /**
     * @param string $option_key Option key.
     * @param string $date_key Date field key.
     * @param int    $days Retention days.
     *
     * @return void
     */
    protected function prune_option_rows( $option_key, $date_key, $days ) {
        $rows = get_option( $option_key, array() );

        if ( ! is_array( $rows ) || empty( $rows ) ) {
            return;
        }

        $cutoff = time() - ( DAY_IN_SECONDS * max( 7, min( 365, absint( $days ) ) ) );

        $rows = array_values(
            array_filter(
                $rows,
                static function ( $row ) use ( $date_key, $cutoff ) {
                    $ts = isset( $row[ $date_key ] ) ? strtotime( (string) $row[ $date_key ] ) : 0;
                    return $ts >= $cutoff;
                }
            )
        );

        update_option( $option_key, $rows, false );
    }
}

/**
 * Register daily retention cleanup schedule.
 *
 * @return void
 */
function pcm_security_privacy_maybe_schedule_cleanup() {
    if ( ! pcm_security_privacy_is_enabled() ) {
        return;
    }

    if ( ! wp_next_scheduled( 'pcm_security_privacy_cleanup' ) ) {
        wp_schedule_event( time() + 180, 'daily', 'pcm_security_privacy_cleanup' );
    }
}
add_action( 'init', 'pcm_security_privacy_maybe_schedule_cleanup' );

/**
 * Execute retention cleanup.
 *
 * @return void
 */
function pcm_security_privacy_cleanup() {
    if ( ! pcm_security_privacy_is_enabled() ) {
        return;
    }

    $manager = new PCM_Telemetry_Retention_Manager();
    $manager->cleanup();
}
add_action( 'pcm_security_privacy_cleanup', 'pcm_security_privacy_cleanup' );

/**
 * Centralized permission + nonce guard for AJAX surfaces.
 *
 * @param string $nonce_action Nonce action.
 * @param string $capability   Required capability.
 *
 * @return void
 */
function pcm_ajax_enforce_permissions( $nonce_action, $capability ) {
    check_ajax_referer( $nonce_action, 'nonce' );

    if ( ! pcm_current_user_can( $capability ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
    }
}

/**
 * Save privacy settings.
 *
 * @param array $raw Incoming settings.
 *
 * @return array
 */
function pcm_save_privacy_settings( $raw ) {
    $raw = is_array( $raw ) ? $raw : array();

    $settings = array(
        'retention_days'       => isset( $raw['retention_days'] ) ? absint( $raw['retention_days'] ) : 90,
        'redaction_level'      => isset( $raw['redaction_level'] ) ? sanitize_key( $raw['redaction_level'] ) : 'standard',
        'export_restrictions'  => isset( $raw['export_restrictions'] ) ? sanitize_key( $raw['export_restrictions'] ) : 'admin_only',
        'advanced_scan_opt_in' => ! empty( $raw['advanced_scan_opt_in'] ),
        'audit_log_enabled'    => ! empty( $raw['audit_log_enabled'] ),
        'sensitive_keys'       => isset( $raw['sensitive_keys'] ) ? array_filter( array_map( 'sanitize_key', (array) $raw['sensitive_keys'] ) ) : array(),
    );

    update_option( 'pcm_privacy_settings_v1', $settings, false );

    return pcm_get_privacy_settings();
}

/**
 * Write audit event if enabled.
 *
 * @param string $action Action key.
 * @param string $target Target.
 * @param array  $context Context.
 *
 * @return array|null
 */
function pcm_audit_log( $action, $target = '', $context = array() ) {
    $settings = pcm_get_privacy_settings();
    if ( empty( $settings['audit_log_enabled'] ) ) {
        return null;
    }

    $service = new PCM_Audit_Log_Service();

    return $service->log( $action, $target, $context );
}

/**
 * AJAX: Get privacy settings.
 *
 * @return void
 */
function pcm_ajax_privacy_settings_get() {
    pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_manage_privacy_settings' );

    wp_send_json_success( array( 'settings' => pcm_get_privacy_settings() ) );
}
add_action( 'wp_ajax_pcm_privacy_settings_get', 'pcm_ajax_privacy_settings_get' );

/**
 * AJAX: Save privacy settings.
 *
 * @return void
 */
function pcm_ajax_privacy_settings_save() {
    pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_manage_privacy_settings' );

    $payload = isset( $_REQUEST['settings'] ) ? json_decode( wp_unslash( $_REQUEST['settings'] ), true ) : array();
    if ( ! is_array( $payload ) ) {
        wp_send_json_error( array( 'message' => 'Invalid settings payload.' ), 400 );
    }

    $settings = pcm_save_privacy_settings( $payload );

    pcm_audit_log( 'privacy_settings_updated', 'privacy_settings', array( 'keys' => array_keys( $payload ) ) );

    wp_send_json_success( array( 'settings' => $settings ) );
}
add_action( 'wp_ajax_pcm_privacy_settings_save', 'pcm_ajax_privacy_settings_save' );

/**
 * AJAX: View audit logs.
 *
 * @return void
 */
function pcm_ajax_audit_log_list() {
    pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_manage_privacy_settings' );

    $limit   = isset( $_REQUEST['limit'] ) ? max( 1, min( 200, absint( $_REQUEST['limit'] ) ) ) : 50;
    $service = new PCM_Audit_Log_Service();
    $rows    = array_slice( array_reverse( $service->all() ), 0, $limit );

    wp_send_json_success(
        array(
            'rows'            => $rows,
            'chain_integrity' => $service->verify_chain(),
        )
    );
}
add_action( 'wp_ajax_pcm_audit_log_list', 'pcm_ajax_audit_log_list' );
