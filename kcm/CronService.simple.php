<?php
/**
 * Simplified CronService - Let systemd handle monitoring
 * 
 * This version starts the cron processes and exits.
 * Systemd's Restart=on-failure will restart this service if it exits
 * with an error, ensuring processes are restarted when needed.
 * 
 * Benefits:
 * - No long-running PHP process
 * - Systemd handles monitoring natively
 * - Better resource management
 * - Systemd journal integration
 */

require(__DIR__ . '/../vendor/autoload.php');

use Kova\Kams\Kcm\Modules\Crons\CronController as Controller;

$controller = new Controller;

// Stop any existing crons (cleanup)
$controller->StopCrons();

// Log to systemd journal
error_log("KCM Cron Service: Starting data collection processes");

// Start enabled crons
$pids = $controller->StartCrons();

if (empty($pids)) {
    error_log("KCM Cron Service: No enabled modules to start");
    exit(0);
}

error_log("KCM Cron Service: Started " . count($pids) . " data collection processes");

// Exit successfully - processes are now running in background
// Systemd will restart this service if it exits with error code
// Note: For better monitoring, consider using systemd Type=forking
// with proper PID file management
exit(0);

