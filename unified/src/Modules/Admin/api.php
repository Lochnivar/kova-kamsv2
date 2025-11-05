<?php

declare(strict_types=1);

// Load bootstrap first to ensure kova_path() and KOVA_ROOT are available
require_once(__DIR__ . '/../../../../app/bootstrap.php');

session_start();

use Kova\Kams\Unified\Modules\Admin\AdminApi;

try {
    $api = new AdminApi();
    $api->handleRequest();
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    
    $errorResponse = [
        'error' => 'Fatal error during API initialization',
        'message' => $e->getMessage() ?: 'Unknown error',
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'class' => get_class($e)
    ];
    
    // Include trace in development
    if (defined('KOVA_ENV') && KOVA_ENV === 'development') {
        $errorResponse['trace'] = $e->getTraceAsString();
    }
    
    echo json_encode($errorResponse, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    exit(1);
}

