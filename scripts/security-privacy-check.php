<?php
/**
 * Lightweight static checks for A9 permission + redaction coverage.
 */

$root = dirname(__DIR__);
$targets = array(
    'includes/cacheability-advisor/storage.php',
    'includes/cache-busters/detector-framework.php',
    'includes/object-cache-intelligence/intelligence.php',
    'includes/php-opcache-awareness/opcache-awareness.php',
    'includes/redirect-assistant/assistant.php',
    'includes/guided-remediation-playbooks/playbooks.php',
    'includes/observability-reporting/reporting.php',
    'includes/security-privacy/security-privacy.php',
);

$errors = array();

foreach ($targets as $rel) {
    $path = $root . '/' . $rel;
    $code = file_get_contents($path);
    if (!is_string($code) || $code === '') {
        $errors[] = "Unable to read {$rel}";
        continue;
    }

    if (strpos($code, "add_action( 'wp_ajax_") !== false && strpos($code, 'pcm_ajax_enforce_permissions') === false) {
        $errors[] = "Expected centralized AJAX guard in {$rel}";
    }
}

$reporting = file_get_contents($root . '/includes/observability-reporting/reporting.php');
if (strpos((string) $reporting, 'pcm_privacy_redact_value') === false) {
    $errors[] = 'Reporting export path is missing privacy redaction usage.';
}

if ($errors) {
    fwrite(STDERR, "Security/privacy checks failed:\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

echo "Security/privacy checks passed.\n";
