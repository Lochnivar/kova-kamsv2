<?php

namespace Kova\Kcm\Modules\Udp;

use Kova\Kcm\Modules\Common\Common as Common;
use Kova\Kcm\Modules\Common\Database as DB;
use Kova\Kcm\Modules\Common\AlarmHandler as AH;

class UdpCron
{
    public $common;
    public $config;
    public $dbConn;
    public $ifaces;
    public $mod = "udp";
    public $fileName;
    public $thru;
    public $lastPacket;
    public $threshold;

    public function __construct($config)
    {
        $this->common = new Common($config->config);
        $this->dbConn = new DB('kams');
        $this->config = $config->config;
        $this->ifaces = $this->config['UDPInterfaceName'];
        $this->fileName = __DIR__ . "/" . $this->mod . "dump";
    }

    public function startCron()
    {

        $x = 0;
        $ifs = explode("~", $this->ifaces);

        foreach ($ifs as $iface) {

            $iArray = explode("|", $iface);
            $ifArray[$iArray[0]] = [];
            $udpIFaces[] = $iArray[0];

            // $command = "timeout 600 /usr/bin/tcpdump -tt -i " . $iArray[0] . ">>" . $this->fileName . "-" . $iArray[0] . "  & echo $!";

            //actual command.
            $command = "timeout 6000 /usr/bin/tcpdump udp -vvvvv -tt -l -i " . $iArray[0] . ">>" . $this->fileName . "-" . $iArray[0] . "  & echo $!";
            exec($command, $output);
            echo "Start Cron DB vars" . PHP_EOL;
            var_dump($this->mod, $output[$x], $iArray[0]);


            $this->dbConn->putProcID($this->mod, $output[$x], $iArray[0]);
            $pids[] = $output[$x];
            $x++;
        }
    }

    public function ProcessCron()
    {
        $modStatus = [];
        $ifaces = $this->dbConn->getIfaces($this->mod);

        var_dump("Ifaces", $ifaces);

        foreach ($ifaces as $iface) {
            var_dump("Iface", $iface);

            $rawFile = file($this->fileName . "-" . $iface['ifaceid']);

            // Step one: Record Cron results

            $this->RecordCron($rawFile, $iface);

            // Step two : Check Alert Conditions

            $modStatus["alerts"] = $this->AlertCron($rawFile, $iface);
            //Clean up file

            file_put_contents($this->fileName . "-" . $iface['ifaceid'], "");
        }
        // Step three: Report the results

        $modStatus['status'] = $this->ReportCron();

        return $modStatus;
    }

    public function RecordCron($file, $iface)
    {

        $x = count($file);
        $line = "";
        $tnow = time();

        //$lineCount = count($file);

        while (strpos($file[$x], "IP") == 0 && $x > 0) {
            $x--;
        }

        $line = $file[$x];
        $temp = explode(" ", $line);
        $tstamp = explode(".", $temp[0]);

        file_put_contents("/usr/src/KCM/src/Modules/Crons/KCM-Cron-Service.log", "UDP Cron Run Finished : " . $x  . " lines found, Last time stamp is " . $tstamp[0] . PHP_EOL, FILE_APPEND);

        $sql = "INSERT INTO udp_data (iface, udp_packets,epoch, tstamp) VALUES  (?,?,?,?)";

        $this->dbConn->dbQuery($sql, $iface['ifaceid'], $x, $tnow, $tstamp[0]);

        $this->thru[$iface['ifaceid']] = $x;
        $this->lastPacket[$iface['ifaceid']] = $tstamp[0];

        var_dump("Thru", $this->thru[$iface['ifaceid']]);
        //     $this->threshold[$iface['ifaceid']] = $iface['threshold'];
    }

    public function AlertCron($rawFile, $iface)
    {

        $alerts['active'] = [];
        $alerts['clear'] = [];

        $sql = "SELECT * FROM udp_settings where ifaceid = ?";

        $results = $this->dbConn->dbQuery($sql, $iface['ifaceid']);

        $alarmID = $results[0]['alarmID'];

        $oneHourAgo = strtotime("-1 hour");

        $sql = "SELECT count(epoch) AS num, avg(udp_packets) AS avgPackets FROM udp_data WHERE iface = ? AND epoch > ?";

        $results = $this->dbConn->dbQuery($sql, $iface['ifaceid'], $oneHourAgo);

        $avg = $results[0]['avgPackets'];
        $count = $results[0]['num'];

        $lowLimit = $this->config['udpTriggerLimit'];

        if ($avg < $lowLimit) {

            if (empty($alarmID)) {

                $valArray[1] = '';   //USER
                $valArray[2] = 'UDP Fell Below Threshold on the SPAN Port ' . $iface['ifaceid'];  //Message
                $valArray[3] = $avg;  //Value 1
                $valArray[4] = '';  //Value 2
                $valArray[5] = '';  //Value 3
                $valArray[6] =  ''; //Channel
                $valArray[7] = '1 Hour';  //Duration

                $msgline = $this->common->buildMsgLine($valArray);

                $ah = new AH();

                $alarmID = $ah->raiseAlarm("udp", $iface['ifaceid'], $msgline);

                var_dump($alarmID);

                $this->updateAlarmID($iface['ifaceid'], $alarmID[0]);

                $alerts['active'][$alarmID[0]] = implode("|", $valArray);
            } else {
                //Alarm Already Exists.  Continue
                echo "Alarm already Exists as #" . $alarmID . PHP_EOL;
                $sql = "SELECT msgLine FROM kamsAlarms where id = ?";

                $results = $this->dbConn->dbQuery($sql, $alarmID);

                $alerts['active'][$alarmID] = $results[0]['msgLine'];
            }
        } else {
            //check if there is an alarm so we can clear it.

            if (!empty($alarmID)) {
                //Active Alarm cleared.
                $valArray[1] = '';   //USER
                $valArray[2] = 'UDP Traffic Restored on the SPAN Port ' . $iface['ifaceid'];  //Message
                $valArray[3] = $avg;  //Value 1
                $valArray[4] = '';  //Value 2
                $valArray[5] = '';  //Value 3
                $valArray[6] =  ''; //Channel
                $valArray[7] = '1 Hour';  //Duration

                $msgline = $this->common->buildMsgLine($valArray);

                echo "Clearing active alarm #" . $alarmID . PHP_EOL;
                $ah = new AH();
                $ah->clearAlarm($this->mod, $alarmID, $iface['ifaceid'], $msgline);

                $sql = "SELECT msgLine FROM kamsAlarms where id = ?";

                $results = $this->dbConn->dbQuery($sql, $alarmID);
                $alerts['clear'][$alarmID] = $results[0]['msgLine'];
            } else {
                //No alarm exists and no new alarm, Continue
            }
        }

        return $alerts;
    }
    public function ReportCron()
    {

        $modStatus = [];


        $x = 0;
        $ifs = explode("~", $this->ifaces);

        foreach ($ifs as $iface) {

            $iArray = explode("|", $iface);
            $ifArray[$iArray[0]] = [];
            $udpIFaces[] = $iArray[0];

            $modStatus[$iArray[0]]['name'] = $iArray[1];
            $modStatus[$iArray[0]]['thru'] = $this->thru[$iArray[0]];
            $modStatus[$iArray[0]]['lastPacket'] = $this->lastPacket[$iArray[0]];
            $modStatus[$iArray[0]]['threshold'] = $iArray[2];
        }

        return $modStatus;
    }

    public function modEnabled()
    {
        return $this->config['UDPMonitorPage'];
    }

    public function updateAlarmID($iface, $alarmID)
    {
        $sql = "UPDATE udp_settings SET alarmID = ? WHERE ifaceid = ?";

        $this->dbConn->dbQuery($sql, $alarmID, $iface);
    }
}
