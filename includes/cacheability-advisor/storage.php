<?php
/**
 * Cacheability Advisor storage + repository scaffolding.
 *
 * @package PressableCacheManagement
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'PCM_CACHEABILITY_ADVISOR_DB_VERSION' ) ) {
    define( 'PCM_CACHEABILITY_ADVISOR_DB_VERSION', '1.0.0' );
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

    $runs_table     = $wpdb->prefix . 'pcm_scan_runs';
    $urls_table     = $wpdb->prefix . 'pcm_scan_urls';
    $findings_table = $wpdb->prefix . 'pcm_findings';

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

    dbDelta( $sql_runs );
    dbDelta( $sql_urls );
    dbDelta( $sql_findings );
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
}

/**
 * Service wrapper for run status lifecycle.
 */
class PCM_Cacheability_Advisor_Run_Service {
    /**
     * @var PCM_Cacheability_Advisor_Repository
     */
    protected $repository;

    /**
     * @param PCM_Cacheability_Advisor_Repository|null $repository Repository dependency.
     */
    public function __construct( $repository = null ) {
        $this->repository = $repository ? $repository : new PCM_Cacheability_Advisor_Repository();
    }

    /**
     * Start new run.
     *
     * @param int $sample_count URL sample count.
     *
     * @return int|false
     */
    public function start_run( $sample_count = 0 ) {
        $initiated_by = get_current_user_id();

        return $this->repository->create_run( $initiated_by, $sample_count );
    }

    /**
     * Mark run as completed.
     *
     * @param int $run_id Run ID.
     *
     * @return bool
     */
    public function mark_completed( $run_id ) {
        return $this->repository->complete_run( $run_id, 'completed' );
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
