<?php

namespace Kova\Kcm\Modules\Crons;

use Kova\Kcm\Modules\Common\Config as Config;
use Kova\Kcm\Modules\Motorola\MotoCron as MotoCron;
use Kova\Kcm\Modules\Serial\SerialCron as SerialCron;
use Kova\Kcm\Modules\Udp\UdpCron as UdpCron;
use Kova\Kcm\Modules\Zabbix\ZabbixCron as ZabbixCron;
use Kova\Kcm\Modules\Reporting\Reporting as Reporting;
use Kova\Kams\Common\Database as DB;
use Kova\Kcm\Modules\Common\Communicator as Comms;

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

        $sql = "TRUNCATE kamsCrons";

        $this->dbConn->dbQuery($sql);

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

        $sql = "SELECT * FROM kamsCrons";

        $result = $this->dbConn->dbQuery($sql);

        foreach ($result as $pid) {
            $cmd = "kill -9 " . $pid['procid'];

            exec($cmd, $output);

            var_dump($output);
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
