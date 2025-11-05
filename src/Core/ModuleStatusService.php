<?php

declare(strict_types=1);

namespace Kova\Kams\Core;

use Kova\Kams\Common\Database;
use Kova\Kams\Common\Logger;

/**
 * ModuleStatusService
 * 
 * Provides unified status for cron modules combining:
 * - Database enablement (MotorolaPage, SerialPage, UdpPage, etc.)
 * - Systemd service running state (active/inactive)
 * - Systemd boot enablement (enabled/disabled)
 * 
 * This service is used throughout the application for:
 * - Checking if modules should be running
 * - Alarm detection (enabled modules that have failed)
 * - Status reporting
 */
class ModuleStatusService
{
    private Database $db;
    private Logger $logger;
    private array $modulePageSettings = [
        'moto' => 'MotorolaPage',
        'serial' => 'SerialPage',
        'udp' => 'UdpPage',
        'zabbix' => 'ZabbixPage'
    ];

    public function __construct(?Database $db = null, ?Logger $logger = null)
    {
        $this->db = $db ?? new Database('kams');
        $this->logger = $logger ?? new Logger('module-status.log');
    }

    /**
     * Get comprehensive status for a single module
     * 
     * @return array {
     *   database_enabled: bool,
     *   service_running: bool,
     *   boot_enabled: bool,
     *   operational_status: 'operational'|'enabled_but_stopped'|'disabled'|'failed'|'unknown',
     *   service_name: string,
     *   processes: array
     * }
     */
    public function getModuleStatus(string $module): array
    {
        $databaseEnabled = $this->isDatabaseEnabled($module);
        $serviceStatus = $this->getSystemdServiceStatus($module);
        
        return [
            'module' => $module,
            'database_enabled' => $databaseEnabled,
            'service_running' => $serviceStatus['active'] ?? false,
            'boot_enabled' => $serviceStatus['enabled'] ?? false,
            'operational_status' => $this->calculateOperationalStatus(
                $databaseEnabled,
                $serviceStatus['active'] ?? false,
                $serviceStatus['status'] ?? 'unknown'
            ),
            'service_name' => $serviceStatus['service'] ?? "kcm-cron@{$module}",
            'service_status' => $serviceStatus['status'] ?? 'unknown',
            'processes' => $serviceStatus['processes'] ?? ['count' => 0],
            'last_check' => time()
        ];
    }

    /**
     * Get status for all modules
     */
    public function getAllModuleStatus(): array
    {
        $statuses = [];
        foreach (array_keys($this->modulePageSettings) as $module) {
            $statuses[$module] = $this->getModuleStatus($module);
        }
        return $statuses;
    }

    /**
     * Check if modules need alarms raised
     * Returns modules that are enabled but not running (failed/stopped)
     */
    public function getModulesNeedingAlarms(): array
    {
        $alarms = [];
        $allStatus = $this->getAllModuleStatus();
        
        foreach ($allStatus as $module => $status) {
            if ($status['database_enabled'] && !$status['service_running']) {
                // Module is enabled in database but service is not running
                $alarms[$module] = [
                    'module' => $module,
                    'reason' => 'enabled_but_stopped',
                    'message' => "Module {$module} is enabled but service is not running",
                    'status' => $status
                ];
            } elseif ($status['database_enabled'] && $status['operational_status'] === 'failed') {
                // Module is enabled but in failed state
                $alarms[$module] = [
                    'module' => $module,
                    'reason' => 'failed',
                    'message' => "Module {$module} is enabled but has failed",
                    'status' => $status
                ];
            }
        }
        
        return $alarms;
    }

    /**
     * Check if a module is enabled in the database
     */
    public function isDatabaseEnabled(string $module): bool
    {
        $pageSetting = $this->modulePageSettings[$module] ?? null;
        if (!$pageSetting) {
            return false;
        }

        try {
            $qb = $this->db->createQueryBuilder();
            $qb->select('value')
               ->from('settings')
               ->where('name = :name')
               ->setParameter('name', $pageSetting);
            
            $result = $this->db->executeQueryBuilder($qb);
            
            // Handle old schema (setname/setvalue) vs new schema (name/value)
            if (empty($result)) {
                // Try old schema
                $qb = $this->db->createQueryBuilder();
                $qb->select('setvalue')
                   ->from('settings')
                   ->where('setname = :name')
                   ->setParameter('name', $pageSetting);
                $result = $this->db->executeQueryBuilder($qb);
            }
            
            if (empty($result)) {
                return false;
            }
            
            $value = strtolower(trim($result[0]['value'] ?? $result[0]['setvalue'] ?? ''));
            return in_array($value, ['yes', '1', 'true', 'on'], true);
        } catch (\Exception $e) {
            $this->logger->error("Error checking database enablement for {$module}", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get systemd service status
     */
    private function getSystemdServiceStatus(string $module): array
    {
        $serviceName = "kcm-cron@{$module}";
        
        // Check if service exists
        $exists = $this->checkServiceExists($serviceName);
        if (!$exists) {
            return [
                'service' => $serviceName,
                'status' => 'not-installed',
                'active' => false,
                'enabled' => false,
                'processes' => ['count' => 0]
            ];
        }

        // Get service status
        $isActive = $this->isServiceActive($serviceName);
        $isEnabled = $this->isServiceEnabled($serviceName);
        $processes = $this->getProcessInfo($module);
        
        return [
            'service' => $serviceName,
            'status' => $isActive ? 'active' : 'inactive',
            'active' => $isActive,
            'enabled' => $isEnabled,
            'processes' => $processes
        ];
    }

    /**
     * Calculate operational status based on all states
     */
    private function calculateOperationalStatus(
        bool $databaseEnabled,
        bool $serviceRunning,
        string $serviceStatus
    ): string {
        if (!$databaseEnabled) {
            return 'disabled';
        }
        
        if ($serviceStatus === 'not-installed') {
            return 'failed';
        }
        
        if ($databaseEnabled && $serviceRunning) {
            return 'operational';
        }
        
        if ($databaseEnabled && !$serviceRunning) {
            return 'enabled_but_stopped';
        }
        
        return 'unknown';
    }

    /**
     * Check if service unit file exists
     */
    private function checkServiceExists(string $serviceName): bool
    {
        $result = $this->executeSystemctl(['list-unit-files', $serviceName]);
        return strpos($result['output'] ?? '', $serviceName) !== false;
    }

    /**
     * Check if service is active (running)
     */
    private function isServiceActive(string $serviceName): bool
    {
        $result = $this->executeSystemctl(['is-active', $serviceName]);
        return trim($result['output'] ?? '') === 'active';
    }

    /**
     * Check if service is enabled for boot
     */
    private function isServiceEnabled(string $serviceName): bool
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
        $tcpdumpCmd = "pgrep -af 'tcpdump.*Parser.*{$module}' 2>/dev/null | head -5";
        $catCmd = "pgrep -af 'cat.*dev.*Parser.*{$module}' 2>/dev/null | head -5";
        
        exec($tcpdumpCmd, $tcpdumpOutput);
        exec($catCmd, $catOutput);
        
        if (!empty($tcpdumpOutput)) {
            $processes['tcpdump'] = array_slice($tcpdumpOutput, 0, 5);
        }
        
        if (!empty($catOutput)) {
            $processes['cat'] = array_slice($catOutput, 0, 5);
        }
        
        // Check for parser processes
        $parserCmd = "pgrep -af 'Parser\.php.*{$module}' 2>/dev/null | head -5";
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
        $sanitized = [];
        foreach ($args as $arg) {
            $sanitized[] = escapeshellarg($arg);
        }
        
        $command = 'systemctl ' . implode(' ', $sanitized) . ' 2>&1';
        
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);
        
        return [
            'exitCode' => $exitCode,
            'output' => implode("\n", $output),
            'error' => $exitCode !== 0 ? implode("\n", $output) : null
        ];
    }

    /**
     * Get available modules
     */
    public function getAvailableModules(): array
    {
        return array_keys($this->modulePageSettings);
    }
}

