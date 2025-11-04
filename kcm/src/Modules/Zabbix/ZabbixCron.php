<?php

namespace Kova\Kcm\Modules\Zabbix;

use Kova\Kcm\Modules\Common\Common as Common;
use Kova\Kams\Common\Database as DB;
use Kova\Kcm\Modules\Common\AlarmHandler as AH;
use Kova\Kcm\Modules\Common\Communicator;
use Kova\Kcm\Modules\Zabbix\ZabbixComms as ZComms;

class ZabbixCron
{

    public $common;
    public $config;
    public $dbConn;
    public $ifaces;
    public $mod = "zabbix";
    public $fileName;
    public $zComms;
    public $modEnabled;

    public function __construct($config)
    {

        $this->dbConn = new DB('kams');
        $this->zComms = new ZComms;
        $this->config = $config->config;
        $this->common = new Common($this->config);
        $this->modEnabled = strtolower($this->config['ZabbixPage']);
    }

    public function startCron()
    {
        /**
         * No Cron to run.
         */
        echo "NO Cron to Run. " . PHP_EOL;
        return;
    }


    public function ProcessCron()
    {
        $modStatus = [];

        echo "Processing Zabbix" . PHP_EOL;
        $modStatus['status'] = $this->ReportCron();
        $modStatus['alerts'] = $this->AlertCron($modStatus['status']);



        return $modStatus;
    }

    public function AlertCron($modStatus)
    {
        $alerts = [];

        foreach ($modStatus as $server) {


            // Check if an alarm exists for this server/iface

            $sql = "SELECT id FROM kamsAlarms WHERE module = 'zabbix' AND iface = ? AND active = '1'";
            $results = $this->dbConn->dbQuery($sql, $server['name']);

            $alarmID = ($results) ? $results[0] : "";


            if ($server['active'] == "0") {

                if (!$alarmID) {
                    echo "Raising Alarm" . PHP_EOL;
                    $valArray = [];
                    $valArray[1] = '';   //USER
                    $valArray[2] = "Server Did Not Check In";  //Message
                    $valArray[3] = 'Zabbix';  //Value 1
                    $valArray[4] = '';  //Value 2
                    $valArray[5] = '';  //Value 3
                    $valArray[6] = $server['name']; //Channel
                    $valArray[7] = '';  //Duration
                    $msgline = $this->common->buildMsgLine($valArray);

                    $ah = new AH();

                    $alarmID = $ah->raiseAlarm("zabbix", $server['name'], $msgline);

                    $alerts[] = implode("|", $valArray);
                } else {
                    //Alarm still active

                    echo "Alarm Active, Skipping" . PHP_EOL;
                }
            } else {
                if ($alarmID) {
                    //Active Alarm cleared.
                    $ah = new AH();;

                    $valArray[1] = '';   //USER
                    $valArray[2] = 'Server Has Checked In';  //Message
                    $valArray[3] = 'Zabbix';  //Value 1
                    $valArray[4] = '';  //Value 2
                    $valArray[5] = '';  //Value 3
                    $valArray[6] = $server['name']; //Channel
                    $valArray[7] = '';  //Duration

                    $msgline = $this->common->buildMsgLine($valArray);
                    $ah->clearAlarm("zabbix", $alarmID['id'], $server['name'], $msgline);
                } else {
                    //No alarm, no foul
                }
            }
        }

        return $alerts;
    }

    public function ReportCron()
    {

        $zbxHealth = [];

        $hosts = $this->zComms->getZBXHosts();

       // var_dump($hosts);

        foreach ($hosts as $host) {
            $health = $this->zComms->getZBXHostHealth($host['hostid']);
            //var_dump("Server Health", $health);
            $zbxHealth[$host['hostid']]['name'] = $host['name'];
            // $zbxHealth[$host['hostid']]['hostid'] = $host['hostid'];
            //   /  $zbxHealth[$host['hostid']]['active'] = "1";
            
            $zbxHealth[$host['hostid']]['active'] = $health[0]['lastvalue'];

            //  var_dump($health);
        }

      //  var_dump($zbxHealth);
        return $zbxHealth;
    }

    public function RecordCron($file, $iface = null)
    {
        $tnow = time();
    }

    public function modEnabled()
    {
        return $this->modEnabled;
    }
}
