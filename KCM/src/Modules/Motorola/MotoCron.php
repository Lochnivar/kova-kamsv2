<?php

namespace Kova\Kcm\Modules\Motorola;

use Kova\Kcm\Modules\Common\Common as Common;
use Kova\Kcm\Modules\Common\Communicator;
use Kova\Kcm\Modules\Common\Database as DB;
use Kova\Kcm\Modules\Common\AlarmHandler as AH;


class MotoCron
{

    public $common;
    public $config;
    public $dbConn;
    public $ifaces;
    public $mod = "moto";
    public $fileName;
    public $modEnabled;

    public function __construct($config)
    {

        $this->dbConn = new DB('kams');
        $this->config = $config->config;
        $this->common = new Common($this->config);
        $this->ifaces = $this->config['MotorolaInterfaceName'];
        $this->fileName = __DIR__ . "/" . $this->mod . "dump";
        $this->modEnabled = strtolower($this->config['MotorolaPage']);
    }

    public function startCron()
    {
        $x = 0;

        $ifs = explode("~", $this->ifaces);

        foreach ($ifs as $iface) {

            $iArray = explode("|", $iface);
            $ifArray[$iArray[0]] = [];
            $udpIFaces[] = $iArray[0];
            $command = "timeout 6000 /usr/bin/tcpdump -vvvvv -tt -A -i " . $iArray[0] . " dst port 50150 and greater 100 >>" . $this->fileName . "-" . $iArray[0] . " & echo $!";
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

        var_dump("ifaces", $ifaces);
        foreach ($ifaces as $iface) {

            var_dump($iface['ifaceid']);

            $rawFile = file($this->fileName . "-" . $iface['ifaceid']);

            $file = file_get_contents($this->fileName . "-" . $iface['ifaceid']);

            // Step one: Record Cron results

            $this->RecordCron($rawFile);

            // Step two : Check Alert Conditions
            $modStatus["alerts"] = $this->AlertCron();

            // Step three: Report the results
            $modStatus['status'] = $this->ReportCron();

            //Clean up file

            file_put_contents($this->fileName . "-" . $iface['ifaceid'], "");
        }

        var_dump("Tracker 1", $modStatus);

        return $modStatus;
    }

    public function AlertCron()
    {
        $alerts = [];
        $sql = "SELECT * from moto_channel_data WHERE timeout_number <> '0' ORDER BY last_activity DESC ,channel_id";
        $results = $this->dbConn->dbQuery($sql);
        $valArray = [];
        $issues = 0;

        $alerts['active'] = [];
        $alerts['clear'] = [];

        foreach ($results as $row) {
            $TimeValue = $row[4] == '1' ? substr_replace($row[5], "", -1) : $TimeValue = $row[5];
            switch ($row[5]) {
                case "Hours":
                    $timeCheck = time() - ($row[4] * 3600);
                    break;
                case "Days":
                    $timeCheck = time() - ($row[4] * 86400);
                    break;
                case "Weeks":
                    $timeCheck = time() - ($row[4] * 604800);
                    break;
            }

            if ($row[3] < $timeCheck && $row[3] != "") {

                //No current Alarm, raise new alarm.
                if (is_null($row['alarm'])) {
                    var_dump("Raising Alarm");
                    $valArray[1] = '';   //USER
                    $valArray[2] = 'Motorola Recorder Channel No Data Issue';  //Message
                    $valArray[3] = str_replace(',', '', date('Y-m-d H:i:s', $row['last_activity'])) . " + "; //Value 1
                    $valArray[4] = '';  //Value 2
                    $valArray[5] = '';  //Value 3
                    $valArray[6] =  $row[2] . " + "; //Channel
                    $valArray[7] = $row[4] . " " . $TimeValue . " + ";  //Duration

                    $msgline = $this->common->buildMsgLine($valArray);

                    $ah = new AH();

                    $alarmID = $ah->raiseAlarm("moto", null, $msgline);

                    $this->updateChannelAlarm($row['channel_id'], $alarmID[0]);

                    $alerts['active'][$alarmID[0]] = implode("|", $valArray);
                } else {
                    //Alarm still active.  Get Current Alarm Line

                    $sql = "SELECT msgLine FROM kamsAlarms where id = ?";

                    $results = $this->dbConn->dbQuery($sql, $row['alarm']);

                    $alerts['active'][$row['alarm']] = $results[0]['msgLine'];

                    echo "Alarm Active, Skipping" . PHP_EOL;
                }
            } else {
                if (!empty($row['alarm'])) {
                    //Active Alarm cleared.
                    var_dump("Clearing Alarm");
                    $valArray[1] = '';   //USER
                    $valArray[2] = 'Motorola Recorder Channel Volume Restored';  //Message
                    $valArray[3] = str_replace(',', '', date('Y-m-d H:i:s', $row['last_activity'])) . " + "; //Value 1
                    $valArray[4] = '';  //Value 2
                    $valArray[5] = '';  //Value 3
                    $valArray[6] =  $row[2] . " + "; //Channel
                    $valArray[7] = $row[4] . " " . $TimeValue . " + ";  //Duration

                    $msgline = $this->common->buildMsgLine($valArray);

                    $ah = new AH();
                    $ah->clearAlarm($this->mod, $row['alarm'], $row['channel_id'], $msgline);

                    $sql = "SELECT msgLine FROM kamsAlarms where id = ?";

                    $results = $this->dbConn->dbQuery($sql, $row['alarm']);


                    $alerts['clear'][$row['alarm']] = $results[0]['msgLine'];
                } else {
                    var_dump("No alarm no foul");
                }
            }
        }

        return $alerts;
    }

    public function ReportCron()
    {
        /**
         * Method of reporting.  Total channels - Channels active - not monitored;
         */

        $sql = "SELECT count(id) AS count FROM moto_channel_data WHERE timeout_number <> '0'";

        $request = $this->dbConn->dbQuery($sql);

        $countActive = $request[0]['count'];

        $modStatus['activeChannels'] = $countActive;

        $sql = "SELECT count(id) AS count FROM moto_channel_data WHERE timeout_number = '0'";

        $request = $this->dbConn->dbQuery($sql);

        $notMonitored = $request[0]['count'];

        $modStatus['notMonitored'] = $notMonitored;

        $sql = "SELECT * from moto_channel_data WHERE timeout_number <> '0' ORDER BY last_activity DESC ,channel_id";
        $result = $this->dbConn->dbQuery($sql);
        $issues = 0;

        foreach ($result as $rowOne) {

            if ($rowOne[4] == '1') {
                $TimeValue = substr_replace($rowOne[5], "", -1);
            } else {
                $TimeValue = $rowOne[5];
            }

            if ($rowOne[5] == "Hours") {
                $timeCheck = time() - ($rowOne[4] * 3600);
            }
            if ($rowOne[5] == "Days") {
                $timeCheck = time() - ($rowOne[4] * 86400);
            }
            if ($rowOne[5] == "Weeks") {
                $timeCheck = time() - ($rowOne[4] * 604800);
            }
            if ($rowOne[3] < $timeCheck && $rowOne[3] != "") {
                $issues++;
            }
            if ($rowOne[3] == "") {
                $issues++;
            }
        }

        $modStatus['issues'] = $issues;

        return $modStatus;
    }

    public function RecordCron($file, $iface = null)
    {

        echo "Recording Cron " . PHP_EOL;
        $timeNow = time();

        $sql = "SELECT * FROM moto_channel_data";

        $results = $this->dbConn->dbQuery($sql);

        $channelIDs = [];
        foreach ($file as $line) {

            if (strpos($line, "<DeviceID>") <> 0) {

                $stringpos = strpos($line, "<AstroEvent");
   
                $line = substr($line, $stringpos);

                $packet = simplexml_load_string($line);

                $lineid = $packet->CallStatusEventArgs->CallStatus->DeviceID;
                $channelIDs[] = (string) $lineid;
            }
        }
        $uniqueIDs = array_unique($channelIDs);

        foreach ($uniqueIDs as $id) {

            if ($id == ""){
                echo "ID is Blank, Skipping" . PHP_EOL; 
                continue;
            }

            $sql = "SELECT channel_id FROM moto_channel_data WHERE channel_id = ?";
            $result = $this->dbConn->dbQuery($sql, $id);

            if (count($result) == 0) {
                echo $id . " Missing, Inserting into table" . PHP_EOL;
                try {
                    $sql = "INSERT INTO moto_channel_data (channel_id) VALUES (?)";
                    $result = $this->dbConn->dbQuery($sql, $id);
                } catch (\Exception $e) {
                    echo "Error " . $e->getMessage();
                }
            }

            $sql = "UPDATE moto_channel_data SET last_activity = ? WHERE channel_id = ?";
            $result = $this->dbConn->dbQuery($sql, $timeNow, $id);
        }
    }

    private function updateChannelAlarm($channel, $alarmID)
    {
        $sql = "UPDATE moto_channel_data SET alarm = ? WHERE channel_id = ?";
        $this->dbConn->dbQuery($sql, (string) $alarmID, $channel);
    }

    public function modEnabled()
    {
        return $this->modEnabled;
    }
}
