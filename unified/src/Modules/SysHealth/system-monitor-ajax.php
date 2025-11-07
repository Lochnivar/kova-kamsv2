<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../app/bootstrap.php');

use Kova\Kams\Unified\Modules\SysHealth\SystemMonitor;

try {
    $systemMonitor = new SystemMonitor();
    $data = $systemMonitor->getMonitorData();

    // Output JSON for JavaScript to consume
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    $errorResponse = [
        'error' => 'Server error in System Monitor API',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'class' => get_class($e)
    ];
    if (defined('KOVA_ENV') && KOVA_ENV === 'development') {
        $errorResponse['trace'] = $e->getTraceAsString();
    }
    echo json_encode($errorResponse, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
}

