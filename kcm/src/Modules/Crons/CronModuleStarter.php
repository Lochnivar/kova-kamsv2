#!/usr/bin/env php
<?php
/**
 * CronModuleStarter.php
 * 
 * Starts a specific cron module using systemd template units.
 * This is called by systemd service: kcm-cron@<module>.service
 * 
 * Usage: php CronModuleStarter.php <module>
 */

declare(strict_types=1);

require_once(__DIR__ . '/../../../vendor/autoload.php');

use Kova\Kams\Kcm\Modules\Crons\CronController;
use Kova\Kams\Kcm\Modules\Common\Config;

// Get module from command line
$module = $argv[1] ?? null;
if (empty($module)) {
    fwrite(STDERR, "Error: Module name required\n");
    fwrite(STDERR, "Usage: php CronModuleStarter.php <module>\n");
    exit(1);
}

// Validate module
$validModules = ['moto', 'serial', 'udp', 'zabbix'];
if (!in_array($module, $validModules)) {
    fwrite(STDERR, "Error: Invalid module. Must be one of: " . implode(', ', $validModules) . "\n");
    exit(1);
}

try {
    $config = new Config();
    $controller = new CronController();
    
    // Check if module is enabled
    $cronClass = match($module) {
        'moto' => new \Kova\Kams\Kcm\Modules\Motorola\MotoCron($config),
        'serial' => new \Kova\Kams\Kcm\Modules\Serial\SerialCron($config),
        'udp' => new \Kova\Kams\Kcm\Modules\Udp\UdpCron($config),
        'zabbix' => new \Kova\Kams\Kcm\Modules\Zabbix\ZabbixCron($config),
        default => null
    };
    
    if (!$cronClass || $cronClass->modEnabled() !== 'yes') {
        error_log("KCM Cron Module {$module}: Module is disabled, exiting");
        exit(0);
    }
    
    // Stop any existing crons for this module
    $controller->StopCronsForModule($module);
    
    // Start the cron for this module
    $pids = $controller->StartCronsForModule($module);
    
    if (empty($pids)) {
        error_log("KCM Cron Module {$module}: No processes started");
        exit(1);
    }
    
    // Write PID file for systemd
    $pidFile = "/run/kcm-cron-{$module}.pid";
    file_put_contents($pidFile, $pids[0]);
    
    error_log("KCM Cron Module {$module}: Started with PID " . $pids[0]);
    
    // Fork and exit parent process (systemd Type=forking requirement)
    $pid = pcntl_fork();
    
    if ($pid == -1) {
        // Fork failed
        error_log("KCM Cron Module {$module}: Fork failed");
        exit(1);
    } elseif ($pid) {
        // Parent process - exit immediately
        exit(0);
    }
    
    // Child process - continue running
    // The processes started by startCron() will continue in background
    
    exit(0);
    
} catch (\Exception $e) {
    error_log("KCM Cron Module {$module}: Error - " . $e->getMessage());
    exit(1);
}

