<?php

require(__DIR__ . '/../vendor/autoload.php');

use Kova\Kams\Kcm\Modules\Crons\CronController as Controller;

/**
 * First, shut down all existing crons that are registered so that we don't get
 * multiple instances.
 */

$controller = new Controller;

$controller->StopCrons();

//exit;

$pids = [];

/**
 * Now restart Crons
 */
$logFile = kova_path('logs/kcm-cron-service.log');
@file_put_contents($logFile, "Service Restarted, Starting Crons" . PHP_EOL, FILE_APPEND);
       
$pids = $controller->StartCrons();

echo "Process IDs" . PHP_EOL;
var_dump($pids);

//exit;

/**
 * Monitor cron processes
 * With piping architecture, we need to check if the actual processes (tcpdump/cat/parser) are running
 */
while(true){
    $allRunning = true;
    
    foreach ($pids as $pid){
        // Check if the PID is still running
        $result = exec("ps -p " . escapeshellarg($pid) . " -o pid= 2>/dev/null");
        
        if(empty($result)){
            $logFile = kova_path('logs/kcm-cron-service.log');
            @file_put_contents($logFile, "Process " . $pid . " has stopped, exiting to restart crons" . PHP_EOL, FILE_APPEND);
            $allRunning = false;
            break;
        }
        
        // Additional check: verify the actual command is still running
        // This helps catch cases where PID might exist but process is zombie
        $cmdResult = exec("ps -p " . escapeshellarg($pid) . " -o comm= 2>/dev/null");
        if (empty($cmdResult) || $cmdResult === 'bash' || $cmdResult === 'sh') {
            // If only shell is running, the pipeline might have died
            // Check if parser/tcpdump processes are still in the process group
            $pgid = exec("ps -p " . escapeshellarg($pid) . " -o pgid= 2>/dev/null");
            if (!empty($pgid)) {
                $pgid = trim($pgid);
                // Check if tcpdump, cat, or php parser is in the process group
                $pgCheck = exec("pgrep -g " . escapeshellarg($pgid) . " -f '(tcpdump|cat.*dev|Parser\.php)' 2>/dev/null");
                if (empty($pgCheck)) {
                    $logFile = kova_path('logs/kcm-cron-service.log');
                    @file_put_contents($logFile, "Process group " . $pgid . " has no active data collection processes, exiting to restart" . PHP_EOL, FILE_APPEND);
                    $allRunning = false;
                    break;
                }
            }
        }
    }
    
    if (!$allRunning) {
        exit;
    }
    
    // Sleep before next check (reduce CPU usage)
    sleep(5);
}

