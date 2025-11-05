<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Handlers;

use Kova\Kams\Unified\Modules\Admin\Services\SystemdServiceManager;
use Kova\Kams\Common\Logger;

/**
 * CronServiceHandler
 * 
 * Handles cron service management API requests.
 */
class CronServiceHandler
{
    private SystemdServiceManager $serviceManager;
    private Logger $logger;

    public function __construct(?SystemdServiceManager $serviceManager = null, ?Logger $logger = null)
    {
        $this->serviceManager = $serviceManager ?? new SystemdServiceManager($logger);
        $this->logger = $logger ?? new Logger('admin.log');
    }

    /**
     * Handle GET request - get service status
     */
    public function handleGet(?string $module = null): array
    {
        try {
            if ($module) {
                return [
                    'success' => true,
                    'data' => $this->serviceManager->getServiceStatus($module)
                ];
            } else {
                return [
                    'success' => true,
                    'data' => $this->serviceManager->getAllServiceStatus()
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Error getting service status', [
                'module' => $module,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Handle POST request - start/stop/restart/enable/disable service
     */
    public function handlePost(array $input): array
    {
        $action = $input['action'] ?? null;
        $module = $input['module'] ?? null;

        if (empty($action) || empty($module)) {
            return [
                'success' => false,
                'error' => 'Missing required parameters: action and module'
            ];
        }

        try {
            switch ($action) {
                case 'start':
                    return $this->serviceManager->startService($module);
                    
                case 'stop':
                    return $this->serviceManager->stopService($module);
                    
                case 'restart':
                    return $this->serviceManager->restartService($module);
                    
                case 'enable':
                    return $this->serviceManager->enableService($module);
                    
                case 'disable':
                    return $this->serviceManager->disableService($module);
                    
                case 'logs':
                    $lines = isset($input['lines']) ? (int)$input['lines'] : 50;
                    return [
                        'success' => true,
                        'data' => $this->serviceManager->getServiceLogs($module, $lines)
                    ];
                    
                default:
                    return [
                        'success' => false,
                        'error' => "Unknown action: {$action}"
                    ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Error executing service action', [
                'action' => $action,
                'module' => $module,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

