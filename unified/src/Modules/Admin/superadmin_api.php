<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../app/bootstrap.php');

use Kova\Kams\Unified\Modules\Admin\SuperadminApi;

try {
    $api = new SuperadminApi();
    $api->handleRequest();
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    
    $errorResponse = [
        'error' => 'Server error',
        'message' => $e->getMessage() ?: 'Unknown error',
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'class' => get_class($e)
    ];
    
    if (defined('KOVA_ENV') && KOVA_ENV === 'development') {
        $errorResponse['trace'] = $e->getTraceAsString();
    }
    
    echo json_encode($errorResponse, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
}

