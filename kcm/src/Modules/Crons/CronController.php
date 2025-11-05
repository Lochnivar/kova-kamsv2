<?php

namespace Kova\Kams\Kcm\Modules\Crons;

use Kova\Kams\Kcm\Modules\Common\Config as Config;
use Kova\Kams\Kcm\Modules\Motorola\MotoCron as MotoCron;
use Kova\Kams\Kcm\Modules\Serial\SerialCron as SerialCron;
use Kova\Kams\Kcm\Modules\Udp\UdpCron as UdpCron;
use Kova\Kams\Kcm\Modules\Zabbix\ZabbixCron as ZabbixCron;
use Kova\Kams\Kcm\Modules\Reporting\Reporting as Reporting;
use Kova\Kams\Common\Database as DB;
use Kova\Kams\Kcm\Modules\Common\Communicator as Comms;

class CronController
{
    public $sysStatus;
    public $config;
    public $mods = ['moto', 'serial', 'udp', 'zabbix'];
    public $dbConn;
    public $comms;
    public $cronStamp;

    public function __construct()
    {
        $this->config = new Config;
        $this->dbConn = new DB("kams");

        $this->cronStamp = time();
    }


    public function StartCrons()
    {

        //reset the kamsCrons table 

        $this->dbConn->executeQuery("TRUNCATE kamsCrons");

        $pids = [];

        foreach ($this->mods as $mod) {
            switch ($mod) {
                case "moto":
                    $cronStart = new MotoCron($this->config);
                    break;
                case "serial":
                    $cronStart = new SerialCron($this->config);
                    break;
                case "udp":
                    $cronStart = new UdpCron($this->config);
                    break;
                case "zabbix":
                    $cronStart = new ZabbixCron($this->config);
                    break;
                default:
                    unset($cronStart);
                    break;
            }

            if ($cronStart->modEnabled() == "yes") {
                echo "Mod Enabled, Starting " . $mod . " Crons" . PHP_EOL;
                $cronStart->startCron();
            } else {
                echo $mod . " Mod Disabled" . PHP_EOL;
            }
        }

        // Get the PIDs

        $pids = $this->dbConn->getCurrentPIDs();

        var_dump($pids);

        return $pids;

        //        exit;
    }

    public function StopCrons()
    {
        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('*')
           ->from('kamsCrons');
        $result = $this->dbConn->executeQueryBuilder($qb);

        foreach ($result as $row) {
            $pid = $row['procid'];
            
            // Get process group ID to kill entire pipeline
            $pgid = exec("ps -p " . escapeshellarg($pid) . " -o pgid= 2>/dev/null");
            
            if (!empty($pgid)) {
                $pgid = trim($pgid);
                // Kill the entire process group to ensure tcpdump/cat and parser all stop
                $cmd = "kill -9 -" . escapeshellarg($pgid) . " 2>/dev/null";
                exec($cmd, $output);
            }
            
            // Also kill the specific PID as fallback
            $cmd = "kill -9 " . escapeshellarg($pid) . " 2>/dev/null";
            exec($cmd, $output);

            var_dump($output);
        }
    }

    /**
     * Start crons for a specific module
     */
    public function StartCronsForModule(string $module): array
    {
        $pids = [];
        
        // Get module-specific cron class
        switch ($module) {
            case "moto":
                $cronStart = new MotoCron($this->config);
                break;
            case "serial":
                $cronStart = new SerialCron($this->config);
                break;
            case "udp":
                $cronStart = new UdpCron($this->config);
                break;
            case "zabbix":
                $cronStart = new ZabbixCron($this->config);
                break;
            default:
                return [];
        }

        if ($cronStart->modEnabled() == "yes") {
            echo "Mod Enabled, Starting " . $module . " Crons" . PHP_EOL;
            $pids = $cronStart->startCron();
        } else {
            echo $module . " Mod Disabled" . PHP_EOL;
        }

        return $pids;
    }

    /**
     * Stop crons for a specific module
     */
    public function StopCronsForModule(string $module): void
    {
        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('*')
           ->from('kamsCrons')
           ->where('module = :module')
           ->setParameter('module', $module);
        $result = $this->dbConn->executeQueryBuilder($qb);

        foreach ($result as $row) {
            $pid = $row['procid'];
            
            // Get process group ID to kill entire pipeline
            $pgid = exec("ps -p " . escapeshellarg($pid) . " -o pgid= 2>/dev/null");
            
            if (!empty($pgid)) {
                $pgid = trim($pgid);
                // Kill the entire process group
                $cmd = "kill -9 -" . escapeshellarg($pgid) . " 2>/dev/null";
                exec($cmd, $output);
            }
            
            // Also kill the specific PID as fallback
            $cmd = "kill -9 " . escapeshellarg($pid) . " 2>/dev/null";
            exec($cmd, $output);
        }
    }

    public function ZabbixCron() {}

    public function processCrons()
    {



        $systemStat = [];

        $systemStat['site'] = $this->config->config['sshName'];
        $systemStat['timestamp'] = $this->cronStamp;

        foreach ($this->mods as $mod) {
            switch ($mod) {
                case "moto":
                    $cron = new MotoCron($this->config);
                    break;
                case "serial":
                    $cron = new SerialCron($this->config);
                    break;
                case "udp":
                    $cron = new UdpCron($this->config);
                    break;
                case "zabbix":
                    $cron = new ZabbixCron($this->config);
                    break;
                default:

                    break;
            }


            if ($cron->modEnabled() == "yes") {
                echo "Mod Enabled, Processing " . $mod . " Results" . PHP_EOL;

                $systemStat[$mod] = $cron->processCron();
                $systemStat[$mod]['enabled'] = "true";
            } else {
                $systemStat[$mod]['enabled'] = "false";
                echo $mod . " Mod Disabled" . PHP_EOL;
            }
        }
        var_dump($systemStat);

        $comms = new Comms;

        $comms->sendReport($systemStat);
    }


    public function BuildReport() {}
}
