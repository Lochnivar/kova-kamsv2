<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Services;

use Kova\Kams\Common\Logger;
use Kova\Kams\Core\ModuleStatusService;

/**
 * SystemdServiceManager
 * 
 * Manages systemd services for cron modules.
 * Provides safe interface to systemctl commands.
 */
class SystemdServiceManager
{
    private Logger $logger;
    private ModuleStatusService $statusService;
    private string $servicePrefix = 'kcm-cron@';
    private array $availableModules = ['moto', 'serial', 'udp', 'zabbix'];

    public function __construct(?Logger $logger = null, ?ModuleStatusService $statusService = null)
    {
        $this->logger = $logger ?? new Logger('admin.log');
        $this->statusService = $statusService ?? new ModuleStatusService();
    }

    /**
     * Get status of a specific module service
     * Includes database enablement status
     */
    public function getServiceStatus(string $module): array
    {
        if (!in_array($module, $this->availableModules)) {
            return [
                'error' => "Invalid module: {$module}",
                'status' => 'unknown'
            ];
        }

        // Use ModuleStatusService for unified status
        $unifiedStatus = $this->statusService->getModuleStatus($module);
        
        return [
            'module' => $module,
            'service' => $unifiedStatus['service_name'],
            'status' => $unifiedStatus['service_status'],
            'enabled' => $unifiedStatus['boot_enabled'],
            'active' => $unifiedStatus['service_running'],
            'database_enabled' => $unifiedStatus['database_enabled'],
            'operational_status' => $unifiedStatus['operational_status'],
            'processes' => $unifiedStatus['processes'],
            'message' => $unifiedStatus['service_status'] === 'not-installed' ? 'Service unit file not found' : null
        ];
    }

    /**
     * Get status of all module services
     */
    public function getAllServiceStatus(): array
    {
        $statuses = [];
        
        foreach ($this->availableModules as $module) {
            $statuses[$module] = $this->getServiceStatus($module);
        }
        
        return $statuses;
    }

    /**
     * Start a module service (resume if paused)
     * This starts the service if it's currently stopped
     */
    public function startService(string $module): array
    {
        if (!in_array($module, $this->availableModules)) {
            return ['error' => "Invalid module: {$module}"];
        }

        $serviceName = $this->servicePrefix . $module;
        
        if (!$this->serviceExists($serviceName)) {
            return [
                'error' => "Service {$serviceName} is not installed. Please install systemd template units first."
            ];
        }

        $result = $this->executeSystemctl(['start', $serviceName]);
        
        if ($result['exitCode'] === 0) {
            $this->logger->info("Started service: {$serviceName}");
            return [
                'success' => true,
                'message' => "Service {$serviceName} started successfully",
                'module' => $module
            ];
        } else {
            $this->logger->error("Failed to start service: {$serviceName}", [
                'error' => $result['error']
            ]);
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to start service'
            ];
        }
    }

    /**
     * Stop a module service (pause without disabling)
     * This stops the service but keeps it enabled for boot
     */
    public function stopService(string $module): array
    {
        if (!in_array($module, $this->availableModules)) {
            return ['error' => "Invalid module: {$module}"];
        }

        $serviceName = $this->servicePrefix . $module;
        
        $result = $this->executeSystemctl(['stop', $serviceName]);
        
        if ($result['exitCode'] === 0) {
            $this->logger->info("Stopped service: {$serviceName}");
            return [
                'success' => true,
                'message' => "Service {$serviceName} stopped successfully",
                'module' => $module
            ];
        } else {
            $this->logger->error("Failed to stop service: {$serviceName}", [
                'error' => $result['error']
            ]);
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to stop service'
            ];
        }
    }

    /**
     * Restart a module service
     */
    public function restartService(string $module): array
    {
        if (!in_array($module, $this->availableModules)) {
            return ['error' => "Invalid module: {$module}"];
        }

        $serviceName = $this->servicePrefix . $module;
        
        $result = $this->executeSystemctl(['restart', $serviceName]);
        
        if ($result['exitCode'] === 0) {
            $this->logger->info("Restarted service: {$serviceName}");
            return [
                'success' => true,
                'message' => "Service {$serviceName} restarted successfully",
                'module' => $module
            ];
        } else {
            $this->logger->error("Failed to restart service: {$serviceName}", [
                'error' => $result['error']
            ]);
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to restart service'
            ];
        }
    }

    /**
     * Enable a module service (start on boot)
     */
    public function enableService(string $module): array
    {
        if (!in_array($module, $this->availableModules)) {
            return ['error' => "Invalid module: {$module}"];
        }

        $serviceName = $this->servicePrefix . $module;
        
        $result = $this->executeSystemctl(['enable', $serviceName]);
        
        if ($result['exitCode'] === 0) {
            $this->logger->info("Enabled service: {$serviceName}");
            return [
                'success' => true,
                'message' => "Service {$serviceName} enabled for boot",
                'module' => $module
            ];
        } else {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to enable service'
            ];
        }
    }

    /**
     * Disable a module service (don't start on boot)
     */
    public function disableService(string $module): array
    {
        if (!in_array($module, $this->availableModules)) {
            return ['error' => "Invalid module: {$module}"];
        }

        $serviceName = $this->servicePrefix . $module;
        
        $result = $this->executeSystemctl(['disable', $serviceName]);
        
        if ($result['exitCode'] === 0) {
            $this->logger->info("Disabled service: {$serviceName}");
            return [
                'success' => true,
                'message' => "Service {$serviceName} disabled from boot",
                'module' => $module
            ];
        } else {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to disable service'
            ];
        }
    }

    /**
     * Get service logs
     */
    public function getServiceLogs(string $module, int $lines = 50): array
    {
        if (!in_array($module, $this->availableModules)) {
            return ['error' => "Invalid module: {$module}"];
        }

        $serviceName = $this->servicePrefix . $module;
        
        $result = $this->executeSystemctl(['--no-pager', '-n', (string)$lines, 'journalctl', '-u', $serviceName]);
        
        return [
            'module' => $module,
            'service' => $serviceName,
            'logs' => $result['output'] ?? '',
            'error' => $result['error'] ?? null
        ];
    }

    /**
     * Check if service unit file exists
     */
    private function serviceExists(string $serviceName): bool
    {
        $result = $this->executeSystemctl(['list-unit-files', $serviceName]);
        return strpos($result['output'] ?? '', $serviceName) !== false;
    }

    /**
     * Check if service is enabled
     */
    private function isEnabled(string $serviceName): bool
    {
        $result = $this->executeSystemctl(['is-enabled', $serviceName]);
        return trim($result['output'] ?? '') === 'enabled';
    }

    /**
     * Get process information for a module
     */
    private function getProcessInfo(string $module): array
    {
        $processes = [];
        
        // Check for tcpdump/cat processes
        $tcpdumpCmd = "pgrep -af 'tcpdump.*Parser.*{$module}' | head -5";
        $catCmd = "pgrep -af 'cat.*dev.*Parser.*{$module}' | head -5";
        
        exec($tcpdumpCmd, $tcpdumpOutput);
        exec($catCmd, $catOutput);
        
        if (!empty($tcpdumpOutput)) {
            $processes['tcpdump'] = array_slice($tcpdumpOutput, 0, 5);
        }
        
        if (!empty($catOutput)) {
            $processes['cat'] = array_slice($catOutput, 0, 5);
        }
        
        // Check for parser processes
        $parserCmd = "pgrep -af 'Parser\.php.*{$module}' | head -5";
        exec($parserCmd, $parserOutput);
        
        if (!empty($parserOutput)) {
            $processes['parser'] = array_slice($parserOutput, 0, 5);
        }
        
        return [
            'count' => count($processes),
            'details' => $processes
        ];
    }

    /**
     * Execute systemctl command safely
     */
    private function executeSystemctl(array $args): array
    {
        // Sanitize arguments
        $sanitized = [];
        foreach ($args as $arg) {
            $sanitized[] = escapeshellarg($arg);
        }
        
        $command = 'systemctl ' . implode(' ', $sanitized) . ' 2>&1';
        
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        
        $outputStr = implode("\n", $output);
        
        return [
            'exitCode' => $exitCode,
            'output' => $outputStr,
            'error' => $exitCode !== 0 ? $outputStr : null
        ];
    }

    /**
     * Get available modules
     */
    public function getAvailableModules(): array
    {
        return $this->availableModules;
    }
}

