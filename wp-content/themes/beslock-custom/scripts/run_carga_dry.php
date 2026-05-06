<?php
// Runner: execute carga_portfolio_data dry-run and print JSON result.
// Place in theme scripts and run via PHP-CLI from repo root.
chdir(__DIR__ . '/../../../..'); // move to repository root where wp-load.php lives
define('WP_USE_THEMES', false);
require_once __DIR__ . '/../../../../wp-load.php';
// Load the importer script
require_once get_stylesheet_directory() . '/scripts/carga_portfolio_data.php';

try {
    $result = beslock_carga_portfolio_process(true);
    // If WP_Error, convert to array
    if (is_wp_error($result)) {
        $out = [
            'success' => false,
            'error' => $result->get_error_message(),
            'data' => $result->get_error_data(),
        ];
    } else {
        $out = $result;
    }
    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (Throwable $e) {
    $err = [
        'success' => false,
        'exception' => get_class($e),
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ];
    echo json_encode($err, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
