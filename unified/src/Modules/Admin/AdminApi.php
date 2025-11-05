<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin;

use Kova\Kams\Unified\Modules\Admin\Handlers\CronServiceHandler;
use Kova\Kams\Unified\Modules\Admin\Handlers\SettingsHandler;
use Kova\Kams\Unified\Modules\Admin\Handlers\AuthHandler;
use Kova\Kams\Unified\Modules\Admin\Http\CorsHandler;
use Kova\Kams\Common\Logger;

/**
 * AdminApi
 * 
 * Main API class for admin functionality.
 * Handles settings management and cron service management.
 */
class AdminApi
{
    private SettingsHandler $settingsHandler;
    private AuthHandler $authHandler;
    private CronServiceHandler $cronServiceHandler;
    private CorsHandler $corsHandler;
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger('admin.log');
        $this->corsHandler = new CorsHandler();
        $this->authHandler = new AuthHandler(null, $this->logger);
        $this->settingsHandler = new SettingsHandler(null, null, null, $this->logger);
        $this->cronServiceHandler = new CronServiceHandler(null, $this->logger);
    }

    /**
     * Handle incoming request
     */
    public function handleRequest(): void
    {
        try {
            // Handle CORS
            $this->corsHandler->handle();

            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $action = $_GET['action'] ?? null;
            $module = $_GET['module'] ?? null;

            // Handle OPTIONS
            if ($method === 'OPTIONS') {
                http_response_code(204);
                exit;
            }

            // Route to appropriate handler
            if (str_starts_with($action ?? '', 'cron')) {
                // Cron service management
                $this->handleCronService($method, $action, $module, $input);
            } elseif (in_array($action, ['login', 'logout', 'check'])) {
                // Authentication
                $this->authHandler->handleRequest($method, $action, $input);
            } else {
                // Settings management (default)
                $this->handleSettings($method, $input);
            }
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage() ?: 'Unknown error';
            $this->logger->error('Admin API error', [
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'class' => get_class($e)
            ]);

            http_response_code(500);
            header('Content-Type: application/json');
            
            // Ensure we output valid JSON even if there's an encoding issue
            $errorResponse = [
                'error' => 'Server error',
                'message' => $errorMessage,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'class' => get_class($e)
            ];
            
            // Try to include trace in development
            if (defined('KOVA_ENV') && KOVA_ENV === 'development') {
                $errorResponse['trace'] = $e->getTraceAsString();
            }
            
            echo json_encode($errorResponse, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
    }

    /**
     * Handle cron service management requests
     */
    private function handleCronService(string $method, ?string $action, ?string $module, array $input): void
    {
        header('Content-Type: application/json');

        // Check authentication for service management
        if (!$this->authHandler->check()) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }

        if ($method === 'GET') {
            $result = $this->cronServiceHandler->handleGet($module);
            echo json_encode($result);
        } elseif ($method === 'POST') {
            $result = $this->cronServiceHandler->handlePost($input);
            echo json_encode($result);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
    }

    /**
     * Handle settings management requests
     */
    private function handleSettings(string $method, array $input): void
    {
        header('Content-Type: application/json');

        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

        if ($method === 'GET') {
            $result = $this->settingsHandler->handleGet();
            // Return data array directly for frontend compatibility
            if (isset($result['data']) && $result['success']) {
                echo json_encode($result['data']);
            } else {
                echo json_encode($result);
            }
        } elseif ($method === 'PUT') {
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing id']);
                exit;
            }
            $result = $this->settingsHandler->handlePut($id, $input);
            if ($result['success']) {
                http_response_code(204);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
        }
    }
}

