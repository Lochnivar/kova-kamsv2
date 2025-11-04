<?php

/**
 * Entry point for the cron to start the crons process.
 */

 require_once(__DIR__ . "/vendor/autoload.php");

 $mod = new \Kova\Kcm\Modules\Crons\CronController;

echo "Starting Crons in CronMain" . PHP_EOL;

$mod->processCrons();
