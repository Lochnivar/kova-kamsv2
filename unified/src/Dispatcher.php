<?php

declare(strict_types=1);

namespace Kova\Kams\Unified;

// Load bootstrap before any namespace operations
require_once(__DIR__ . '/../../app/bootstrap.php');

use Kova\Kams\Unified\Modules\Common\Config;
use Kova\Kams\Unified\Modules\Home\Home;
use Kova\Kams\Unified\Modules\Common\Common;
use Kova\Kams\Unified\Modules\UDP\Workers\Dataworker as udpDataworker;
use Kova\Kams\Unified\Modules\Serial\Workers\Dataworker as serialDataworker;
use Kova\Kams\Unified\Modules\Motorola\Workers\Dataworker as motoDataworker;
use Kova\Kams\Unified\Modules\SysHealth\SysHealth;
use Kova\Kams\Unified\Modules\Admin\AdminController;

// Set up error handling
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'error' => 'Fatal error',
            'message' => $error['message'],
            'file' => $error['file'],
            'line' => $error['line']
        ]);
        exit;
    }
});

set_exception_handler(function(\Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Uncaught exception',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    exit;
});

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

try {
    // Get action from POST
    $action = $_POST['action'] ?? $_GET['action'] ?? null;
    
    if (empty($action)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'No action specified']);
        exit;
    }

    $config = new Config();
    $configs = $config->config;

    switch ($action) {
        case "getHome":
            try {
                $cargo = new Home($configs);
                echo $cargo->buildHome();
            } catch (\Exception $e) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode([
                    'error' => 'Error in getHome',
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
            break;

        case "getNavBar":
            $cargo = new Common($configs);
            echo $cargo->getNavBar();
            break;

        case "getSiteName":
            $cargo = new Common($configs);
            echo $cargo->getSiteName();
            break;

        case "getIfaces":
            $mod = $_POST['mod'] ?? $_GET['mod'] ?? null;
            if (empty($mod)) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Module name required']);
                exit;
            }
            $cargo = new Common($configs);
            echo json_encode($cargo->getIfaces($mod));
            break;

        case "getAdmin":
            $adminController = new AdminController();
            echo $adminController->renderAdminPage();
            break;

        case "getCronServices":
            $adminController = new AdminController();
            echo $adminController->renderCronServicesPage();
            break;

        case "getUDPAvgs":
            $cargo = new udpDataworker($configs);
            header('Content-Type: application/json');
            echo json_encode($cargo->getUDPAvgs());
            break;

        case "getSerialAvgs":
            $cargo = new serialDataworker($configs);
            header('Content-Type: application/json');
            echo json_encode($cargo->getSerialAvgs());
            break;

        case "getSysHealth":
            $cargo = new SysHealth($configs);
            echo $cargo->getSystHealthSum();
            break;

        case "getMotoData":
            $cargo = new motoDataworker($configs);
            header('Content-Type: application/json');
            echo json_encode($cargo->getMotoData());
            break;

        default:
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => "Unknown action: {$action}"]);
            break;
    }
} catch (\Exception $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'Dispatcher error',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}

