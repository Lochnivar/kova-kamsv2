<?php

require (__DIR__ . "/vendor/autoload.php");

use Kova\Kcm\Modules\Crons\CronController as Controller;

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
file_put_contents("/usr/src/KCM/src/Modules/Crons/KCM-Cron-Service.log", "Service Restarted, Starting Crons" . PHP_EOL, FILE_APPEND);
       
$pids = $controller->StartCrons();

echo "Process IDs" . PHP_EOL;
var_dump($pids);

//exit;

while(true){

foreach ($pids as $pid){
    $result = exec("ps | grep " . $pid);

    if(empty($result)){
        file_put_contents("/usr/src/KCM/src/Modules/Crons/KCM-Cron-Service.log", "Process " . $pid . " has stopped, exiting to restart crons" . PHP_EOL, FILE_APPEND);
        exit;
    }


}


}

