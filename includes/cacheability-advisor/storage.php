<?php
/**
 * Cacheability Advisor storage + services.
 *
 * @package PressableCacheManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'PCM_CACHEABILITY_ADVISOR_DB_VERSION' ) ) {
    define( 'PCM_CACHEABILITY_ADVISOR_DB_VERSION', '1.1.0' );
}

/**
 * Feature flag for cacheability advisor.
 *
 * Default is disabled to keep rollout safe in WPCloud production sites.
 * Enable through:
 * add_filter( 'pcm_enable_cacheability_advisor', '__return_true' );
 *
 * @return bool
 */
function pcm_cacheability_advisor_is_enabled() {
    $enabled = false;

    return (bool) apply_filters( 'pcm_enable_cacheability_advisor', $enabled );
}

/**
 * Ensure schema is up to date when feature is enabled.
 *
 * @return void
 */
function pcm_cacheability_advisor_maybe_migrate() {
    if ( ! pcm_cacheability_advisor_is_enabled() ) {
        return;
    }

    if ( ! is_admin() ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $current_version = get_option( 'pcm_cacheability_advisor_db_version', '' );

    if ( PCM_CACHEABILITY_ADVISOR_DB_VERSION === $current_version ) {
        return;
    }

    pcm_cacheability_advisor_install_tables();
    update_option( 'pcm_cacheability_advisor_db_version', PCM_CACHEABILITY_ADVISOR_DB_VERSION, false );
}
add_action( 'admin_init', 'pcm_cacheability_advisor_maybe_migrate' );

/**
 * Install or update plugin tables.
 *
 * @return void
 */
function pcm_cacheability_advisor_install_tables() {
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();

    $runs_table            = $wpdb->prefix . 'pcm_scan_runs';
    $urls_table            = $wpdb->prefix . 'pcm_scan_urls';
    $findings_table        = $wpdb->prefix . 'pcm_findings';
    $template_scores_table = $wpdb->prefix . 'pcm_template_scores';

    $sql_runs = "CREATE TABLE {$runs_table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        started_at datetime DEFAULT NULL,
        finished_at datetime DEFAULT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        sample_count int(11) unsigned NOT NULL DEFAULT 0,
        initiated_by bigint(20) unsigned DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY status (status),
        KEY started_at (started_at)
    ) {$charset_collate};";

    $sql_urls = "CREATE TABLE {$urls_table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        run_id bigint(20) unsigned NOT NULL,
        url text NOT NULL,
        template_type varchar(50) NOT NULL,
        status_code smallint(5) unsigned DEFAULT NULL,
        score int(3) unsigned DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY run_id (run_id),
        KEY template_type (template_type),
        KEY score (score)
    ) {$charset_collate};";

    $sql_findings = "CREATE TABLE {$findings_table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        run_id bigint(20) unsigned NOT NULL,
        url text NOT NULL,
        rule_id varchar(100) NOT NULL,
        severity varchar(20) NOT NULL,
        evidence_json longtext DEFAULT NULL,
        recommendation_id varchar(100) DEFAULT NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY run_id (run_id),
        KEY rule_id (rule_id),
        KEY severity (severity)
    ) {$charset_collate};";

    $sql_template_scores = "CREATE TABLE {$template_scores_table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        run_id bigint(20) unsigned NOT NULL,
        template_type varchar(50) NOT NULL,
        score int(3) unsigned NOT NULL DEFAULT 0,
        url_count int(11) unsigned NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY run_id (run_id),
        KEY template_type (template_type),
        KEY created_at (created_at)
    ) {$charset_collate};";

    dbDelta( $sql_runs );
    dbDelta( $sql_urls );
    dbDelta( $sql_findings );
    dbDelta( $sql_template_scores );
}

/**
 * Repository for Cacheability Advisor run lifecycle and findings.
 */
class PCM_Cacheability_Advisor_Repository {
    /**
     * Create a scan run.
     *
     * @param int $initiated_by User ID who started the run.
     * @param int $sample_count Number of URLs sampled.
     *
     * @return int|false Run ID or false on failure.
     */
    public function create_run( $initiated_by = 0, $sample_count = 0 ) {
        global $wpdb;

        $table = $wpdb->prefix . 'pcm_scan_runs';
        $now   = current_time( 'mysql', true );

        $inserted = $wpdb->insert(
            $table,
            array(
                'started_at'   => $now,
                'status'       => 'running',
                'sample_count' => absint( $sample_count ),
                'initiated_by' => absint( $initiated_by ),
                'created_at'   => $now,
            ),
            array( '%s', '%s', '%d', '%d', '%s' )
        );

        if ( false === $inserted ) {
            return false;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Update run completion state.
     *
     * @param int    $run_id Run ID.
     * @param string $status pending|running|completed|failed
     *
     * @return bool
     */
    public function complete_run( $run_id, $status = 'completed' ) {
        global $wpdb;

        $table = $wpdb->prefix . 'pcm_scan_runs';
        $now   = current_time( 'mysql', true );

        $updated = $wpdb->update(
            $table,
            array(
                'status'      => sanitize_key( $status ),
                'finished_at' => $now,
            ),
            array( 'id' => absint( $run_id ) ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        return false !== $updated;
    }

    /**
     * Add URL score row.
     *
     * @param int    $run_id Run ID.
     * @param string $url URL probed.
     * @param string $template_type Template grouping.
     * @param int    $status_code HTTP status code.
     * @param int    $score URL score.
     *
     * @return int|false
     */
    public function add_url_result( $run_id, $url, $template_type, $status_code = 0, $score = 0 ) {
        global $wpdb;

        $table = $wpdb->prefix . 'pcm_scan_urls';
        $now   = current_time( 'mysql', true );

        $inserted = $wpdb->insert(
            $table,
            array(
                'run_id'        => absint( $run_id ),
                'url'           => esc_url_raw( $url ),
                'template_type' => sanitize_key( $template_type ),
                'status_code'   => absint( $status_code ),
                'score'         => max( 0, min( 100, absint( $score ) ) ),
                'created_at'    => $now,
            ),
            array( '%d', '%s', '%s', '%d', '%d', '%s' )
        );

        if ( false === $inserted ) {
            return false;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Add finding row.
     *
     * @param int    $run_id Run ID.
     * @param string $url URL where finding was detected.
     * @param string $rule_id Rule identifier.
     * @param string $severity critical|warning|opportunity.
     * @param array  $evidence Associative evidence payload.
     * @param string $recommendation_id Recommendation key.
     *
     * @return int|false
     */
    public function add_finding( $run_id, $url, $rule_id, $severity, $evidence = array(), $recommendation_id = '' ) {
        global $wpdb;

        $table = $wpdb->prefix . 'pcm_findings';
        $now   = current_time( 'mysql', true );

        $inserted = $wpdb->insert(
            $table,
            array(
                'run_id'            => absint( $run_id ),
                'url'               => esc_url_raw( $url ),
                'rule_id'           => sanitize_key( $rule_id ),
                'severity'          => sanitize_key( $severity ),
                'evidence_json'     => wp_json_encode( $evidence ),
                'recommendation_id' => sanitize_key( $recommendation_id ),
                'created_at'        => $now,
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            return false;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Persist template score for trend history.
     *
     * @param int    $run_id Run ID.
     * @param string $template_type Template type.
     * @param int    $score Averaged score.
     * @param int    $url_count URL count used.
     *
     * @return int|false
     */
    public function add_template_score( $run_id, $template_type, $score, $url_count ) {
        global $wpdb;

        $table = $wpdb->prefix . 'pcm_template_scores';
        $now   = current_time( 'mysql', true );

        $inserted = $wpdb->insert(
            $table,
            array(
                'run_id'         => absint( $run_id ),
                'template_type'  => sanitize_key( $template_type ),
                'score'          => max( 0, min( 100, absint( $score ) ) ),
                'url_count'      => max( 0, absint( $url_count ) ),
                'created_at'     => $now,
            ),
            array( '%d', '%s', '%d', '%d', '%s' )
        );

        if ( false === $inserted ) {
            return false;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Fetch a run by ID.
     *
     * @param int $run_id Run ID.
     *
     * @return array|null
     */
    public function get_run( $run_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'pcm_scan_runs';

        $query = $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $run_id ) );

        return $wpdb->get_row( $query, ARRAY_A );
    }

    /**
     * List recent runs.
     *
     * @param int $limit Result size.
     *
     * @return array
     */
    public function list_runs( $limit = 10 ) {
        global $wpdb;

        $table = $wpdb->prefix . 'pcm_scan_runs';
        $limit = max( 1, min( 100, absint( $limit ) ) );

        $query = $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit );

        return $wpdb->get_results( $query, ARRAY_A );
    }

    /**
     * List URL results for a run.
     *
     * @param int $run_id Run ID.
     *
     * @return array
     */
    public function list_url_results( $run_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'pcm_scan_urls';

        $query = $wpdb->prepare( "SELECT * FROM {$table} WHERE run_id = %d ORDER BY id ASC", absint( $run_id ) );

        return $wpdb->get_results( $query, ARRAY_A );
    }

    /**
     * List findings for a run.
     *
     * @param int $run_id Run ID.
     *
     * @return array
     */
    public function list_findings( $run_id ) {
        global $wpdb;

        $table = $wpdb->prefix . 'pcm_findings';

        $query = $wpdb->prepare( "SELECT * FROM {$table} WHERE run_id = %d ORDER BY id ASC", absint( $run_id ) );
        $rows  = $wpdb->get_results( $query, ARRAY_A );

        foreach ( $rows as $index => $row ) {
            $decoded = ! empty( $row['evidence_json'] ) ? json_decode( $row['evidence_json'], true ) : array();
            $rows[ $index ]['evidence'] = is_array( $decoded ) ? $decoded : array();
            unset( $rows[ $index ]['evidence_json'] );
        }

        return $rows;
    }

    /**
     * List template trends.
     *
     * @param string $range 24h|7d|30d.
     *
     * @return array
     */
    public function list_template_trends( $range = '7d' ) {
        global $wpdb;

        $table = $wpdb->prefix . 'pcm_template_scores';

        $days_by_range = array(
            '24h' => 1,
            '7d'  => 7,
            '30d' => 30,
        );

        $days = isset( $days_by_range[ $range ] ) ? $days_by_range[ $range ] : 7;
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - ( DAY_IN_SECONDS * $days ) );

        $query = $wpdb->prepare(
            "SELECT run_id, template_type, score, url_count, created_at
             FROM {$table}
             WHERE created_at >= %s
             ORDER BY created_at ASC, id ASC",
            $cutoff
        );

        return $wpdb->get_results( $query, ARRAY_A );
    }
}

/**
 * Probe client (A1.2).
 */
class PCM_Cacheability_Probe_Client {
    /**
     * Probe URL and return normalized metadata.
     *
     * @param string $url URL.
     *
     * @return array
     */
    public function probe( $url ) {
        $url = esc_url_raw( $url );

        $args = array(
            'timeout'     => 8,
            'redirection' => 3,
            'headers'     => array(
                'Cache-Control' => 'no-cache',
                'Pragma'        => 'no-cache',
                'User-Agent'    => 'Pressable-Cache-Advisor/1.0',
            ),
            'cookies'     => array(),
            'sslverify'   => apply_filters( 'https_local_ssl_verify', false ),
        );

        $attempts = 2;
        $response = null;
        for ( $i = 0; $i < $attempts; $i++ ) {
            $response = wp_remote_get( $url, $args );
            if ( ! is_wp_error( $response ) ) {
                break;
            }
        }

        if ( is_wp_error( $response ) ) {
            return array(
                'url'              => $url,
                'effective_url'    => $url,
                'status_code'      => 0,
                'headers'          => array(),
                'error_code'       => $response->get_error_code(),
                'error_message'    => $response->get_error_message(),
                'is_error'         => true,
            );
        }

        $headers = wp_remote_retrieve_headers( $response );
        $normalized_headers = $this->normalize_headers( $headers );

        return array(
            'url'              => $url,
            'effective_url'    => esc_url_raw( wp_remote_retrieve_header( $response, 'x-redirect-by' ) ? $url : $url ),
            'status_code'      => absint( wp_remote_retrieve_response_code( $response ) ),
            'headers'          => $normalized_headers,
            'error_code'       => '',
            'error_message'    => '',
            'is_error'         => false,
        );
    }

    /**
     * @param array|object $headers Headers.
     *
     * @return array
     */
    protected function normalize_headers( $headers ) {
        $output = array();

        if ( is_object( $headers ) && method_exists( $headers, 'getAll' ) ) {
            $headers = $headers->getAll();
        }

        foreach ( (array) $headers as $name => $value ) {
            $key = strtolower( sanitize_text_field( (string) $name ) );
            if ( is_array( $value ) ) {
                $output[ $key ] = array_map( 'sanitize_text_field', $value );
            } else {
                $output[ $key ] = sanitize_text_field( (string) $value );
            }
        }

        return $output;
    }
}

/**
 * Rule evaluator + score calculator.
 */
class PCM_Cacheability_Rule_Engine {
    /**
     * Evaluate probe response and return score/findings.
     *
     * @param array $response Response context.
     *
     * @return array{score:int,findings:array}
     */
    public function evaluate( $response ) {
        $score    = 100;
        $findings = array();

        $headers = isset( $response['headers'] ) && is_array( $response['headers'] ) ? $response['headers'] : array();

        $set_cookie = isset( $headers['set-cookie'] ) ? $headers['set-cookie'] : '';
        if ( ! empty( $set_cookie ) ) {
            $score -= 40;
            $findings[] = array(
                'rule_id'           => 'anonymous_set_cookie',
                'severity'          => 'critical',
                'recommendation_id' => 'remove_anonymous_cookie',
                'evidence'          => array( 'headers' => array( 'set-cookie' => $set_cookie ) ),
            );
        }

        $cache_control = isset( $headers['cache-control'] ) ? strtolower( (string) $headers['cache-control'] ) : '';
        if ( '' !== $cache_control && ( false !== strpos( $cache_control, 'no-store' ) || false !== strpos( $cache_control, 'private' ) || false !== strpos( $cache_control, 'max-age=0' ) ) ) {
            $score -= 30;
            $findings[] = array(
                'rule_id'           => 'cache_control_not_public',
                'severity'          => 'warning',
                'recommendation_id' => 'adjust_cache_control',
                'evidence'          => array( 'headers' => array( 'cache-control' => $cache_control ) ),
            );
        }

        $vary = isset( $headers['vary'] ) ? strtolower( (string) $headers['vary'] ) : '';
        if ( '' !== $vary && ( false !== strpos( $vary, 'cookie' ) || false !== strpos( $vary, 'user-agent' ) ) ) {
            $score -= 20;
            $findings[] = array(
                'rule_id'           => 'volatile_vary',
                'severity'          => 'warning',
                'recommendation_id' => 'narrow_vary_headers',
                'evidence'          => array( 'headers' => array( 'vary' => $vary ) ),
            );
        }

        if ( ! empty( $response['is_error'] ) ) {
            $score -= 20;
            $findings[] = array(
                'rule_id'           => 'probe_error',
                'severity'          => 'warning',
                'recommendation_id' => 'retry_probe_and_check_origin',
                'evidence'          => array(
                    'error_code'    => isset( $response['error_code'] ) ? $response['error_code'] : '',
                    'error_message' => isset( $response['error_message'] ) ? $response['error_message'] : '',
                ),
            );
        }

        return array(
            'score'    => max( 0, min( 100, (int) $score ) ),
            'findings' => $findings,
        );
    }
}

/**
 * URL sampler.
 */
class PCM_Cacheability_URL_Sampler {
    /**
     * @return array[]
     */
    public function sample() {
        $samples = array();

        $samples[] = array(
            'url'           => home_url( '/' ),
            'template_type' => 'homepage',
        );

        $samples[] = array(
            'url'           => home_url( '/?s=cache' ),
            'template_type' => 'search',
        );

        $posts = get_posts(
            array(
                'post_type'      => array( 'post', 'page' ),
                'posts_per_page' => 6,
                'post_status'    => 'publish',
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );

        foreach ( (array) $posts as $post ) {
            $url = get_permalink( $post );
            if ( ! $url ) {
                continue;
            }

            $samples[] = array(
                'url'           => $url,
                'template_type' => ( 'page' === $post->post_type ) ? 'page' : 'post',
            );
        }

        if ( class_exists( 'WooCommerce' ) ) {
            foreach ( array( '/cart/', '/checkout/', '/my-account/' ) as $path ) {
                $samples[] = array(
                    'url'           => home_url( $path ),
                    'template_type' => 'commerce',
                );
            }
        }

        return $this->unique_samples( $samples );
    }

    /**
     * @param array $samples Samples.
     *
     * @return array
     */
    protected function unique_samples( $samples ) {
        $seen   = array();
        $output = array();

        foreach ( (array) $samples as $sample ) {
            $url = isset( $sample['url'] ) ? esc_url_raw( $sample['url'] ) : '';
            if ( '' === $url || isset( $seen[ $url ] ) ) {
                continue;
            }

            $seen[ $url ] = true;
            $output[] = array(
                'url'           => $url,
                'template_type' => isset( $sample['template_type'] ) ? sanitize_key( $sample['template_type'] ) : 'unknown',
            );
        }

        return $output;
    }
}

/**
 * Service wrapper for run status lifecycle.
 */
class PCM_Cacheability_Advisor_Run_Service {
    /** @var PCM_Cacheability_Advisor_Repository */
    protected $repository;

    /** @var PCM_Cacheability_Probe_Client */
    protected $probe_client;

    /** @var PCM_Cacheability_Rule_Engine */
    protected $rule_engine;

    /** @var PCM_Cacheability_URL_Sampler */
    protected $sampler;

    /**
     * @param PCM_Cacheability_Advisor_Repository|null $repository Repository dependency.
     * @param PCM_Cacheability_Probe_Client|null       $probe_client Probe client.
     * @param PCM_Cacheability_Rule_Engine|null        $rule_engine Rule engine.
     * @param PCM_Cacheability_URL_Sampler|null        $sampler Sampler.
     */
    public function __construct( $repository = null, $probe_client = null, $rule_engine = null, $sampler = null ) {
        $this->repository   = $repository ? $repository : new PCM_Cacheability_Advisor_Repository();
        $this->probe_client = $probe_client ? $probe_client : new PCM_Cacheability_Probe_Client();
        $this->rule_engine  = $rule_engine ? $rule_engine : new PCM_Cacheability_Rule_Engine();
        $this->sampler      = $sampler ? $sampler : new PCM_Cacheability_URL_Sampler();
    }

    /**
     * Start a run + execute orchestrator.
     *
     * @return int|false
     */
    public function start_and_execute_scan() {
        $samples    = $this->sampler->sample();
        $sample_cnt = count( $samples );
        $run_id     = $this->repository->create_run( get_current_user_id(), $sample_cnt );

        if ( ! $run_id ) {
            return false;
        }

        $template_aggregates = array();

        foreach ( $samples as $sample ) {
            $url           = isset( $sample['url'] ) ? $sample['url'] : '';
            $template_type = isset( $sample['template_type'] ) ? $sample['template_type'] : 'unknown';
            $probe         = $this->probe_client->probe( $url );
            $evaluation    = $this->rule_engine->evaluate( $probe );
            $score         = isset( $evaluation['score'] ) ? absint( $evaluation['score'] ) : 0;

            $this->repository->add_url_result(
                $run_id,
                $url,
                $template_type,
                isset( $probe['status_code'] ) ? absint( $probe['status_code'] ) : 0,
                $score
            );

            foreach ( (array) $evaluation['findings'] as $finding ) {
                $this->repository->add_finding(
                    $run_id,
                    $url,
                    isset( $finding['rule_id'] ) ? $finding['rule_id'] : 'unknown_rule',
                    isset( $finding['severity'] ) ? $finding['severity'] : 'warning',
                    array(
                        'headers'       => isset( $probe['headers'] ) ? $probe['headers'] : array(),
                        'probe_context' => isset( $finding['evidence'] ) ? $finding['evidence'] : array(),
                    ),
                    isset( $finding['recommendation_id'] ) ? $finding['recommendation_id'] : ''
                );
            }

            if ( ! isset( $template_aggregates[ $template_type ] ) ) {
                $template_aggregates[ $template_type ] = array(
                    'score_total' => 0,
                    'count'       => 0,
                );
            }

            $template_aggregates[ $template_type ]['score_total'] += $score;
            $template_aggregates[ $template_type ]['count']++;
        }

        foreach ( $template_aggregates as $template_type => $aggregate ) {
            $count = max( 1, absint( $aggregate['count'] ) );
            $avg   = (int) round( absint( $aggregate['score_total'] ) / $count );
            $this->repository->add_template_score( $run_id, $template_type, $avg, $count );
        }

        $this->repository->complete_run( $run_id, 'completed' );

        return $run_id;
    }

    /**
     * Mark run as failed.
     *
     * @param int $run_id Run ID.
     *
     * @return bool
     */
    public function mark_failed( $run_id ) {
        return $this->repository->complete_run( $run_id, 'failed' );
    }
}

/**
 * Shared permission guard for Cacheability Advisor AJAX endpoints.
 *
 * @return bool
 */
function pcm_cacheability_advisor_ajax_can_manage() {
    if ( function_exists( 'pcm_current_user_can' ) ) {
        return pcm_current_user_can( 'pcm_run_scans' );
    }

    return current_user_can( 'manage_options' );
}

/**
 * AJAX: Start an advisor scan run.
 *
 * @return void
 */
function pcm_ajax_cacheability_scan_start() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_run_scans' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( ! pcm_cacheability_advisor_ajax_can_manage() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    if ( ! pcm_cacheability_advisor_is_enabled() ) {
        wp_send_json_error( array( 'message' => 'Cacheability Advisor is disabled.' ), 400 );
    }

    if ( function_exists( 'pcm_get_privacy_settings' ) ) {
        $privacy_settings = pcm_get_privacy_settings();
        if ( empty( $privacy_settings['advanced_scan_opt_in'] ) ) {
            wp_send_json_error( array( 'message' => 'Advanced scans require privacy opt-in.' ), 400 );
        }
    }

    $service = new PCM_Cacheability_Advisor_Run_Service();
    $run_id  = $service->start_and_execute_scan();

    if ( ! $run_id ) {
        wp_send_json_error( array( 'message' => 'Unable to create scan run.' ), 500 );
    }

    if ( function_exists( 'pcm_audit_log' ) ) {
        pcm_audit_log( 'cacheability_scan_started', 'cacheability_advisor', array( 'run_id' => (int) $run_id ) );
    }

    wp_send_json_success(
        array(
            'run_id' => (int) $run_id,
            'status' => 'completed',
        )
    );
}
add_action( 'wp_ajax_pcm_cacheability_scan_start', 'pcm_ajax_cacheability_scan_start' );

/**
 * AJAX: Get scan run status.
 *
 * @return void
 */
function pcm_ajax_cacheability_scan_status() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_view_diagnostics' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( ! pcm_cacheability_advisor_ajax_can_manage() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $run_id = isset( $_REQUEST['run_id'] ) ? absint( wp_unslash( $_REQUEST['run_id'] ) ) : 0;

    $repository = new PCM_Cacheability_Advisor_Repository();
    $run        = $run_id ? $repository->get_run( $run_id ) : null;

    if ( ! $run ) {
        $runs = $repository->list_runs( 1 );
        $run  = ! empty( $runs ) ? $runs[0] : null;
    }

    if ( ! $run ) {
        wp_send_json_success( array( 'run' => null ) );
    }

    wp_send_json_success( array( 'run' => $run ) );
}
add_action( 'wp_ajax_pcm_cacheability_scan_status', 'pcm_ajax_cacheability_scan_status' );

/**
 * AJAX: Get findings for a run.
 *
 * @return void
 */
function pcm_ajax_cacheability_scan_findings() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_view_diagnostics' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( ! pcm_cacheability_advisor_ajax_can_manage() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $run_id = isset( $_REQUEST['run_id'] ) ? absint( wp_unslash( $_REQUEST['run_id'] ) ) : 0;
    if ( $run_id <= 0 ) {
        wp_send_json_error( array( 'message' => 'Missing run_id.' ), 400 );
    }

    $repository = new PCM_Cacheability_Advisor_Repository();
    $findings   = $repository->list_findings( $run_id );

    if ( class_exists( 'PCM_Playbook_Lookup_Service' ) ) {
        $lookup = new PCM_Playbook_Lookup_Service();

        foreach ( $findings as $index => $finding ) {
            $rule_id = isset( $finding['rule_id'] ) ? sanitize_key( $finding['rule_id'] ) : '';
            if ( '' === $rule_id ) {
                continue;
            }

            $playbook_lookup = $lookup->lookup_for_finding( $rule_id );
            if ( ! empty( $playbook_lookup['available'] ) && ! empty( $playbook_lookup['playbook']['meta'] ) ) {
                $meta = $playbook_lookup['playbook']['meta'];
                $findings[ $index ]['playbook_lookup'] = array(
                    'available'   => true,
                    'playbook_id' => isset( $meta['playbook_id'] ) ? $meta['playbook_id'] : '',
                    'title'       => isset( $meta['title'] ) ? $meta['title'] : '',
                    'severity'    => isset( $meta['severity'] ) ? $meta['severity'] : '',
                );
            } else {
                $findings[ $index ]['playbook_lookup'] = array(
                    'available' => false,
                    'reason'    => isset( $playbook_lookup['reason'] ) ? $playbook_lookup['reason'] : 'no_playbook',
                );
            }
        }
    }

    wp_send_json_success(
        array(
            'run_id'   => $run_id,
            'findings' => $findings,
        )
    );
}
add_action( 'wp_ajax_pcm_cacheability_scan_findings', 'pcm_ajax_cacheability_scan_findings' );

/**
 * AJAX: Get URL results for a run.
 *
 * @return void
 */
function pcm_ajax_cacheability_scan_results() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_view_diagnostics' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( ! pcm_cacheability_advisor_ajax_can_manage() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $run_id = isset( $_REQUEST['run_id'] ) ? absint( wp_unslash( $_REQUEST['run_id'] ) ) : 0;
    if ( $run_id <= 0 ) {
        wp_send_json_error( array( 'message' => 'Missing run_id.' ), 400 );
    }

    $repository = new PCM_Cacheability_Advisor_Repository();
    $results    = $repository->list_url_results( $run_id );

    wp_send_json_success(
        array(
            'run_id'  => $run_id,
            'results' => $results,
        )
    );
}
add_action( 'wp_ajax_pcm_cacheability_scan_results', 'pcm_ajax_cacheability_scan_results' );

/**
 * AJAX: Get template trends.
 *
 * @return void
 */
function pcm_ajax_cacheability_template_trends() {
    if ( function_exists( 'pcm_ajax_enforce_permissions' ) ) {
        pcm_ajax_enforce_permissions( 'pcm_cacheability_scan', 'pcm_view_diagnostics' );
    } else {
        check_ajax_referer( 'pcm_cacheability_scan', 'nonce' );

        if ( ! pcm_cacheability_advisor_ajax_can_manage() ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }
    }

    $range      = isset( $_REQUEST['range'] ) ? sanitize_key( wp_unslash( $_REQUEST['range'] ) ) : '7d';
    $repository = new PCM_Cacheability_Advisor_Repository();

    wp_send_json_success(
        array(
            'range'  => $range,
            'trends' => $repository->list_template_trends( $range ),
        )
    );
}
add_action( 'wp_ajax_pcm_cacheability_template_trends', 'pcm_ajax_cacheability_template_trends' );
