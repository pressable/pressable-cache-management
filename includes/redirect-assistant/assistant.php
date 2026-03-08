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
