<?php
/**
 * Redirect Assistant (Pillar 5).
 *
 * Export-only workflow for custom-redirects.php generation.
 *
 * @package PressableCacheManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Feature flag for redirect assistant.
 *
 * @return bool
 */
function pcm_redirect_assistant_is_enabled() {
    $enabled = false;

    return (bool) apply_filters( 'pcm_enable_redirect_assistant', $enabled );
}

/**
 * Redirect rules repository.
 */
class PCM_Redirect_Assistant_Repository {
    /**
     * @var string
     */
    protected $option_key = 'pcm_redirect_assistant_rules_v1';

    /**
     * List all rules ordered by priority ASC, id ASC.
     *
     * @return array
     */
    public function list_rules() {
        $rules = get_option( $this->option_key, array() );

        if ( ! is_array( $rules ) ) {
            return array();
        }

        usort(
            $rules,
            static function ( $a, $b ) {
                $priority_a = isset( $a['priority'] ) ? absint( $a['priority'] ) : 999;
                $priority_b = isset( $b['priority'] ) ? absint( $b['priority'] ) : 999;

                if ( $priority_a === $priority_b ) {
                    return strcmp( (string) $a['id'], (string) $b['id'] );
                }

                return ( $priority_a < $priority_b ) ? -1 : 1;
            }
        );

        return $rules;
    }

    /**
     * Upsert one rule.
     *
     * @param array $rule Rule payload.
     *
     * @return string Rule ID.
     */
    public function upsert_rule( $rule ) {
        $sanitized = pcm_redirect_assistant_sanitize_rule( $rule );
        $rules     = $this->list_rules();

        $found = false;
        foreach ( $rules as $index => $existing ) {
            if ( isset( $existing['id'] ) && $existing['id'] === $sanitized['id'] ) {
                $rules[ $index ] = $sanitized;
                $found           = true;
                break;
            }
        }

        if ( ! $found ) {
            $rules[] = $sanitized;
        }

        update_option( $this->option_key, array_values( $rules ), false );

        return $sanitized['id'];
    }

    /**
     * Delete rule by ID.
     *
     * @param string $rule_id Rule ID.
     *
     * @return bool
     */
    public function delete_rule( $rule_id ) {
        $rule_id = sanitize_key( $rule_id );
        $rules   = $this->list_rules();

        $filtered = array_values(
            array_filter(
                $rules,
                static function ( $rule ) use ( $rule_id ) {
                    return ! isset( $rule['id'] ) || $rule['id'] !== $rule_id;
                }
            )
        );

        update_option( $this->option_key, $filtered, false );

        return count( $filtered ) < count( $rules );
    }
}

/**
 * Candidate discovery service.
 */
class PCM_Redirect_Assistant_Candidate_Discovery {
    /**
     * Build candidates from observed URL list.
     *
     * @param array $observed_urls Full URLs.
     *
     * @return array
     */
    public function discover( $observed_urls ) {
        $candidates = array();

        if ( ! is_array( $observed_urls ) || empty( $observed_urls ) ) {
            return $candidates;
        }

        foreach ( $observed_urls as $url ) {
            $url = esc_url_raw( $url );
            if ( '' === $url ) {
                continue;
            }

            $parts = wp_parse_url( $url );
            if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
                continue;
            }

            $path  = isset( $parts['path'] ) ? $parts['path'] : '/';
            $query = isset( $parts['query'] ) ? $parts['query'] : '';

            if ( '' !== $query ) {
                parse_str( $query, $params );
                $noise_params = array_intersect( array_keys( $params ), array( 'gclid', 'fbclid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content' ) );

                if ( ! empty( $noise_params ) ) {
                    $canonical = trailingslashit( $parts['scheme'] . '://' . $parts['host'] . ltrim( $path, '/' ) );

                    $candidates[] = array(
                        'id'              => 'cand_' . md5( 'query_' . $path ),
                        'enabled'         => false,
                        'priority'        => 100,
                        'match_type'      => 'prefix',
                        'source_pattern'  => $path,
                        'target_pattern'  => $canonical,
                        'status_code'     => 301,
                        'notes'           => 'Query parameter normalization for tracking keys: ' . implode( ', ', $noise_params ),
                        'expected_impact' => 'high',
                        'confidence'      => 'high',
                    );
                }
            }

            if ( $path !== strtolower( $path ) ) {
                $target = trailingslashit( $parts['scheme'] . '://' . $parts['host'] . strtolower( ltrim( $path, '/' ) ) );
                $candidates[] = array(
                    'id'              => 'cand_' . md5( 'case_' . $path ),
                    'enabled'         => false,
                    'priority'        => 200,
                    'match_type'      => 'exact',
                    'source_pattern'  => $path,
                    'target_pattern'  => $target,
                    'status_code'     => 301,
                    'notes'           => 'Lowercase canonicalization pattern.',
                    'expected_impact' => 'medium',
                    'confidence'      => 'medium',
                );
            }

            if ( '/' !== $path && '/' === substr( $path, -1 ) ) {
                $trimmed = untrailingslashit( $path );
                $target  = $parts['scheme'] . '://' . $parts['host'] . $trimmed;
                $candidates[] = array(
                    'id'              => 'cand_' . md5( 'slash_' . $path ),
                    'enabled'         => false,
                    'priority'        => 300,
                    'match_type'      => 'exact',
                    'source_pattern'  => $path,
                    'target_pattern'  => $target,
                    'status_code'     => 301,
                    'notes'           => 'Trailing slash normalization.',
                    'expected_impact' => 'low',
                    'confidence'      => 'medium',
                );
            }
        }

        return array_values(
            array_map(
                'pcm_redirect_assistant_sanitize_rule',
                pcm_redirect_assistant_unique_by_key( $candidates, 'id' )
            )
        );
    }
}

/**
 * Simulation and validation engine.
 */
class PCM_Redirect_Assistant_Simulation_Engine {
    /**
     * Simulate one URL.
     *
     * @param string $input_url Input URL.
     * @param array  $rules Rules list.
     * @param int    $hop_cap Max redirect hops.
     *
     * @return array
     */
    public function simulate_url( $input_url, $rules, $hop_cap = 10 ) {
        $current  = esc_url_raw( $input_url );
        $visited  = array();
        $warnings = array();

        for ( $hop = 0; $hop < absint( $hop_cap ); $hop++ ) {
            if ( in_array( $current, $visited, true ) ) {
                $warnings[] = 'redirect_loop_detected';
                return array(
                    'input_url'      => esc_url_raw( $input_url ),
                    'result_status'  => 'loop',
                    'result_url'     => $current,
                    'warnings'       => $warnings,
                );
            }

            $visited[] = $current;
            $match     = $this->match_first_rule( $current, $rules );

            if ( empty( $match ) ) {
                return array(
                    'input_url'      => esc_url_raw( $input_url ),
                    'result_status'  => 'ok',
                    'result_url'     => $current,
                    'warnings'       => $warnings,
                );
            }

            $current = esc_url_raw( $match['target_pattern'] );
        }

        $warnings[] = 'hop_cap_reached';

        return array(
            'input_url'      => esc_url_raw( $input_url ),
            'result_status'  => 'warning',
            'result_url'     => $current,
            'warnings'       => $warnings,
        );
    }

    /**
     * Batch simulate URLs.
     *
     * @param array $input_urls Input URLs.
     * @param array $rules Rules list.
     *
     * @return array
     */
    public function simulate_batch( $input_urls, $rules ) {
        $results = array();

        foreach ( (array) $input_urls as $url ) {
            $results[] = $this->simulate_url( $url, $rules );
        }

        return $results;
    }

    /**
     * Detect conflicting/overlapping rules (v1 heuristic).
     *
     * @param array $rules Rules list.
     *
     * @return array
     */
    public function detect_conflicts( $rules ) {
        $warnings = array();

        foreach ( $rules as $i => $left ) {
            foreach ( $rules as $j => $right ) {
                if ( $i >= $j ) {
                    continue;
                }

                if ( empty( $left['enabled'] ) || empty( $right['enabled'] ) ) {
                    continue;
                }

                if ( $left['source_pattern'] === $right['source_pattern'] && $left['target_pattern'] !== $right['target_pattern'] ) {
                    $warnings[] = array(
                        'type'        => 'source_conflict',
                        'left_rule'   => $left['id'],
                        'right_rule'  => $right['id'],
                        'description' => 'Same source pattern redirects to different targets.',
                    );
                }

                if ( 'prefix' === $left['match_type'] && 0 === strpos( $right['source_pattern'], $left['source_pattern'] ) ) {
                    $warnings[] = array(
                        'type'        => 'overlap_prefix',
                        'left_rule'   => $left['id'],
                        'right_rule'  => $right['id'],
                        'description' => 'Prefix pattern overlaps a more specific rule.',
                    );
                }
            }
        }

        return $warnings;
    }

    /**
     * @param string $url URL.
     * @param array  $rules Rules list.
     *
     * @return array
     */
    protected function match_first_rule( $url, $rules ) {
        $path = wp_parse_url( $url, PHP_URL_PATH );
        $path = is_string( $path ) ? $path : '/';

        foreach ( $rules as $rule ) {
            if ( empty( $rule['enabled'] ) ) {
                continue;
            }

            $source = isset( $rule['source_pattern'] ) ? (string) $rule['source_pattern'] : '';
            $type   = isset( $rule['match_type'] ) ? (string) $rule['match_type'] : 'exact';

            if ( 'exact' === $type && $source === $path ) {
                return $rule;
            }

            if ( 'prefix' === $type && '' !== $source && 0 === strpos( $path, $source ) ) {
                return $rule;
            }

            if ( 'regex' === $type && @preg_match( $source, $path ) ) {
                return $rule;
            }
        }

        return array();
    }
}

/**
 * Exporter for custom-redirects.php content.
 */
class PCM_Redirect_Assistant_Exporter {
    /**
     * @param array $rules Rule list.
     *
     * @return array
     */
    public function build_export( $rules ) {
        $rules    = array_values( array_map( 'pcm_redirect_assistant_sanitize_rule', (array) $rules ) );
        $checksum = hash( 'sha256', wp_json_encode( $rules ) );
        $created  = gmdate( 'Y-m-d H:i:s' ) . ' UTC';

        $payload = array(
            'schema_version' => 1,
            'generated_at'   => $created,
            'checksum'       => $checksum,
            'rules'          => $rules,
        );

        $php = "<?php\n";
        $php .= "/**\n";
        $php .= " * custom-redirects.php generated by Pressable Cache Management\n";
        $php .= " * Schema: 1\n";
        $php .= " * Generated: {$created}\n";
        $php .= " * Checksum: {$checksum}\n";
        $php .= " */\n\n";
        $php .= 'return ' . var_export( $payload, true ) . ';' . "\n";

        return array(
            'content' => $php,
            'meta'    => $payload,
            'syntax'  => $this->validate_syntax( $php ),
        );
    }

    /**
     * @param string $php_content PHP source.
     *
     * @return array
     */
    protected function validate_syntax( $php_content ) {
        $result = array(
            'valid'   => false,
            'message' => 'Syntax validation unavailable.',
        );

        if ( ! function_exists( 'sys_get_temp_dir' ) || ! defined( 'PHP_BINARY' ) ) {
            return $result;
        }

        $temp_file = trailingslashit( sys_get_temp_dir() ) . 'pcm-custom-redirects-' . wp_generate_uuid4() . '.php';
        $written   = file_put_contents( $temp_file, $php_content );

        if ( false === $written ) {
            $result['message'] = 'Unable to write temporary file for syntax validation.';
            return $result;
        }

        $output = array();
        $code   = 1;

        @exec( escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $temp_file ), $output, $code );
        @unlink( $temp_file );

        $result['valid']   = ( 0 === $code );
        $result['message'] = implode( "\n", $output );

        return $result;
    }
}

/**
 * @param array  $rows Rows.
 * @param string $key  Unique key field.
 *
 * @return array
 */
function pcm_redirect_assistant_unique_by_key( $rows, $key ) {
    $unique = array();

    foreach ( (array) $rows as $row ) {
        if ( ! is_array( $row ) || empty( $row[ $key ] ) ) {
            continue;
        }

        $unique[ $row[ $key ] ] = $row;
    }

    return array_values( $unique );
}

/**
 * @param array $rule Rule payload.
 *
 * @return array
 */
function pcm_redirect_assistant_sanitize_rule( $rule ) {
    $rule = is_array( $rule ) ? $rule : array();

    $id = isset( $rule['id'] ) ? sanitize_key( $rule['id'] ) : 'rule_' . wp_generate_uuid4();

    return array(
        'id'             => $id,
        'enabled'        => ! empty( $rule['enabled'] ),
        'priority'       => isset( $rule['priority'] ) ? absint( $rule['priority'] ) : 999,
        'match_type'     => isset( $rule['match_type'] ) && in_array( $rule['match_type'], array( 'exact', 'prefix', 'regex' ), true ) ? $rule['match_type'] : 'exact',
        'source_pattern' => isset( $rule['source_pattern'] ) ? sanitize_text_field( $rule['source_pattern'] ) : '/',
        'target_pattern' => isset( $rule['target_pattern'] ) ? esc_url_raw( $rule['target_pattern'] ) : home_url( '/' ),
        'status_code'    => isset( $rule['status_code'] ) ? absint( $rule['status_code'] ) : 301,
        'notes'          => isset( $rule['notes'] ) ? sanitize_text_field( $rule['notes'] ) : '',
        'created_by'     => isset( $rule['created_by'] ) ? absint( $rule['created_by'] ) : get_current_user_id(),
        'updated_at'     => current_time( 'mysql', true ),
    );
}

/**
 * Capability guard for redirect assistant management.
 *
 * @return bool
 */
function pcm_redirect_assistant_can_manage() {
    if ( function_exists( 'pcm_current_user_can' ) ) {
        return pcm_current_user_can( 'pcm_manage_redirect_rules' );
    }

    return current_user_can( 'manage_options' );
}

/**
 * Validate rules for regex safety and wildcard confirmation requirements.
 *
 * @param array $rules Rules.
 * @param bool  $wildcard_confirmed Wildcard confirmation flag.
 *
 * @return array
 */
function pcm_redirect_assistant_validate_rules( $rules, $wildcard_confirmed = false ) {
    $errors   = array();
    $warnings = array();

    foreach ( (array) $rules as $index => $rule ) {
        $rule   = pcm_redirect_assistant_sanitize_rule( $rule );
        $id     = isset( $rule['id'] ) ? $rule['id'] : 'rule_' . $index;
        $source = isset( $rule['source_pattern'] ) ? (string) $rule['source_pattern'] : '';
        $target = isset( $rule['target_pattern'] ) ? (string) $rule['target_pattern'] : '';
        $type   = isset( $rule['match_type'] ) ? (string) $rule['match_type'] : 'exact';

        if ( '' === $target ) {
            $errors[] = array(
                'rule_id' => $id,
                'type'    => 'empty_target',
                'message' => 'Target URL is required.',
            );
        }

        if ( 'regex' === $type ) {
            $regex_valid = @preg_match( $source, '/redirect-assistant-test/' );
            if ( false === $regex_valid ) {
                $errors[] = array(
                    'rule_id' => $id,
                    'type'    => 'invalid_regex',
                    'message' => 'Regex pattern failed validation.',
                );
            }

            $looks_wild = false !== strpos( $source, '.*' ) || false !== strpos( $source, '.+' ) || false !== strpos( $source, '(.+)' );
            if ( $looks_wild ) {
                $warnings[] = array(
                    'rule_id' => $id,
                    'type'    => 'wildcard_regex',
                    'message' => 'Regex appears broad and may match many URLs.',
                );
            }
        }

        if ( 'prefix' === $type && '/' === trim( $source ) ) {
            $warnings[] = array(
                'rule_id' => $id,
                'type'    => 'root_prefix',
                'message' => 'Root prefix rule can affect nearly all requests.',
            );
        }
    }

    if ( ! empty( $warnings ) && ! $wildcard_confirmed ) {
        $errors[] = array(
            'rule_id' => 'global',
            'type'    => 'wildcard_confirmation_required',
            'message' => 'Wildcard/regex-like rules require explicit confirmation.',
        );
    }

    return array(
        'is_valid' => empty( $errors ),
        'errors'   => $errors,
        'warnings' => $warnings,
    );
}

/**
 * AJAX: list saved redirect rules.
 *
 * @return void
 */
function pcm_ajax_redirect_assistant_list_rules() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_manage_redirect_rules' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( ! pcm_redirect_assistant_can_manage() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $repo = new PCM_Redirect_Assistant_Repository();

    wp_send_json_success( array( 'rules' => $repo->list_rules() ) );
}
add_action( 'wp_ajax_pcm_redirect_assistant_list_rules', 'pcm_ajax_redirect_assistant_list_rules' );

/**
 * AJAX: discover rule candidates from URL input and latest advisor URLs.
 *
 * @return void
 */
function pcm_ajax_redirect_assistant_discover_candidates() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_manage_redirect_rules' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( ! pcm_redirect_assistant_can_manage() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $input_urls = isset( $_POST['urls'] ) ? (string) wp_unslash( $_POST['urls'] ) : '';
    $urls = array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $input_urls ) ) ) );

    if ( empty( $urls ) ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pcm_scan_urls';
        $rows = $wpdb->get_col( "SELECT url FROM {$table} ORDER BY id DESC LIMIT 50" );
        $urls = is_array( $rows ) ? $rows : array();
    }

    $discovery  = new PCM_Redirect_Assistant_Candidate_Discovery();
    $candidates = $discovery->discover( $urls );

    wp_send_json_success(
        array(
            'count'      => count( $candidates ),
            'candidates' => $candidates,
        )
    );
}
add_action( 'wp_ajax_pcm_redirect_assistant_discover_candidates', 'pcm_ajax_redirect_assistant_discover_candidates' );

/**
 * AJAX: save rules payload.
 *
 * @return void
 */
function pcm_ajax_redirect_assistant_save_rules() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_manage_redirect_rules' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( ! pcm_redirect_assistant_can_manage() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $raw_rules = isset( $_POST['rules'] ) ? (string) wp_unslash( $_POST['rules'] ) : '[]';
    $decoded   = json_decode( $raw_rules, true );
    $rules     = is_array( $decoded ) ? $decoded : array();

    $confirmed = isset( $_POST['confirm_wildcards'] ) && '1' === (string) wp_unslash( $_POST['confirm_wildcards'] );
    $validation = pcm_redirect_assistant_validate_rules( $rules, $confirmed );

    if ( ! $validation['is_valid'] ) {
        wp_send_json_error(
            array(
                'message'    => 'Rule validation failed.',
                'validation' => $validation,
            ),
            400
        );
    }

    $repo   = new PCM_Redirect_Assistant_Repository();
    $saved  = array();

    foreach ( $rules as $rule ) {
        $saved[] = $repo->upsert_rule( $rule );
    }

    if ( function_exists( 'pcm_audit_log' ) ) {
        pcm_audit_log( 'redirect_rules_saved', 'redirect_assistant', array( 'rule_count' => count( $saved ) ) );
    }

    wp_send_json_success(
        array(
            'saved_rule_ids' => $saved,
            'validation'     => $validation,
        )
    );
}
add_action( 'wp_ajax_pcm_redirect_assistant_save_rules', 'pcm_ajax_redirect_assistant_save_rules' );

/**
 * AJAX: simulate URLs against current or provided rules.
 *
 * @return void
 */
function pcm_ajax_redirect_assistant_simulate() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_manage_redirect_rules' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( ! pcm_redirect_assistant_can_manage() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $raw_urls = isset( $_POST['urls'] ) ? (string) wp_unslash( $_POST['urls'] ) : '';
    $urls     = array_values( array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', $raw_urls ) ) ) );

    $repo     = new PCM_Redirect_Assistant_Repository();
    $rules    = $repo->list_rules();

    $raw_rules = isset( $_POST['rules'] ) ? (string) wp_unslash( $_POST['rules'] ) : '';
    if ( '' !== trim( $raw_rules ) ) {
        $decoded = json_decode( $raw_rules, true );
        if ( is_array( $decoded ) ) {
            $rules = array_values( array_map( 'pcm_redirect_assistant_sanitize_rule', $decoded ) );
        }
    }

    $sim       = new PCM_Redirect_Assistant_Simulation_Engine();
    $results   = $sim->simulate_batch( $urls, $rules );
    $conflicts = $sim->detect_conflicts( $rules );

    if ( function_exists( 'pcm_audit_log' ) ) {
        pcm_audit_log( 'redirect_rules_simulated', 'redirect_assistant', array( 'url_count' => count( $urls ) ) );
    }

    wp_send_json_success(
        array(
            'results'   => $results,
            'conflicts' => $conflicts,
        )
    );
}
add_action( 'wp_ajax_pcm_redirect_assistant_simulate', 'pcm_ajax_redirect_assistant_simulate' );

/**
 * AJAX: export rules with syntax + conflict checks.
 *
 * @return void
 */
function pcm_ajax_redirect_assistant_export() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_manage_redirect_rules' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( ! pcm_redirect_assistant_can_manage() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $repo      = new PCM_Redirect_Assistant_Repository();
    $rules     = $repo->list_rules();
    $confirmed = isset( $_POST['confirm_wildcards'] ) && '1' === (string) wp_unslash( $_POST['confirm_wildcards'] );

    $validation = pcm_redirect_assistant_validate_rules( $rules, $confirmed );
    if ( ! $validation['is_valid'] ) {
        wp_send_json_error(
            array(
                'message'    => 'Export blocked by validation guardrails.',
                'validation' => $validation,
            ),
            400
        );
    }

    $sim       = new PCM_Redirect_Assistant_Simulation_Engine();
    $conflicts = $sim->detect_conflicts( $rules );

    $exporter = new PCM_Redirect_Assistant_Exporter();
    $export   = $exporter->build_export( $rules );

    if ( function_exists( 'pcm_audit_log' ) ) {
        pcm_audit_log( 'redirect_rules_exported', 'redirect_assistant', array( 'rule_count' => count( $rules ) ) );
    }

    wp_send_json_success(
        array(
            'export' => $export,
            'meta_json' => wp_json_encode( isset( $export['meta'] ) ? $export['meta'] : array() ),
            'conflicts'  => $conflicts,
            'validation' => $validation,
        )
    );
}
add_action( 'wp_ajax_pcm_redirect_assistant_export', 'pcm_ajax_redirect_assistant_export' );

/**
 * AJAX: import rules from prior export payload JSON.
 *
 * @return void
 */
function pcm_ajax_redirect_assistant_import() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_manage_redirect_rules' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( ! pcm_redirect_assistant_can_manage() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $raw_payload = isset( $_POST['payload'] ) ? (string) wp_unslash( $_POST['payload'] ) : '';
    $decoded     = json_decode( $raw_payload, true );

    if ( ! is_array( $decoded ) ) {
        wp_send_json_error( array( 'message' => 'Payload must be valid JSON export metadata.' ), 400 );
    }

    $rules = isset( $decoded['rules'] ) && is_array( $decoded['rules'] ) ? $decoded['rules'] : array();
    if ( empty( $rules ) ) {
        wp_send_json_error( array( 'message' => 'No rules found in import payload.' ), 400 );
    }

    $repo = new PCM_Redirect_Assistant_Repository();

    $saved = array();
    foreach ( $rules as $rule ) {
        $saved[] = $repo->upsert_rule( $rule );
    }

    if ( function_exists( 'pcm_audit_log' ) ) {
        pcm_audit_log( 'redirect_rules_imported', 'redirect_assistant', array( 'rule_count' => count( $saved ) ) );
    }

    wp_send_json_success(
        array(
            'imported_rule_ids' => $saved,
            'rule_count'        => count( $saved ),
        )
    );
}
add_action( 'wp_ajax_pcm_redirect_assistant_import', 'pcm_ajax_redirect_assistant_import' );
