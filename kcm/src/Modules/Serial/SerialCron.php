<?php

namespace Kova\Kcm\Modules\Serial;

use Kova\Kcm\Modules\Common\Common as Common;
use Kova\Kams\Common\Database as DB;
use Kova\Kcm\Modules\Common\AlarmHandler as AH;
use Kova\Kcm\Modules\Common\Communicator;

class SerialCron
{

    public $common;
    public $config;
    public $dbConn;
    public $ifaces;
    public $mod = "serial";
    public $fileName;
    public $thru;
    public $lastPacket;
    public $threshold;

    public function __construct($config)
    {

        $this->dbConn = new DB('kams');
        $this->config = $config->config;
        $this->common = new Common($this->config);
        $this->ifaces = $this->config['SerialInterfaceName'];
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
            $command = "timeout 6000 cat /dev/" . $iArray[0] . " >> " . $this->fileName . "-" . $iArray[0] . " & echo $!";
            echo $command . PHP_EOL;
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

        foreach ($ifaces as $iface) {

            var_dump($iface['ifaceid']);

            $rawFile = file($this->fileName . "-" . $iface['ifaceid']);

            // Step one: Record Cron results

            $this->RecordCron($rawFile, $iface);

            // Step two : Check Alert Conditions

            $modStatus["alerts"] = $this->AlertCron($rawFile, $iface);

            // Step three: Report the results

            $modStatus['status'] = $this->ReportCron();
        }

        //Clean up file

        file_put_contents($this->fileName . "-" . $iface['ifaceid'], "");

        return $modStatus;
    }

    public function AlertCron($rawFile, $iface)
    {

        $alerts['active'] = [];
        $alerts['clear'] = [];

        $sql = "SELECT issueCount, alarmID FROM serial_settings WHERE ifaceid = ?";

        $results = $this->dbConn->dbQuery($sql, $iface['ifaceid']);

        $issueCount = $results[0]['issueCount'] ? $results[0]['issueCount'] : 0;
        $alarmID = $results[0]['alarmID'];

        var_dump($results);

        $lines = count($rawFile);
        //    $lines = 0;

        if ($lines < $this->config['SerialTriggerLimit']) {
            $hour = date('H');

            if ($issueCount <= 2.5) {
                $icInc = $this->checkNightHours($hour);
                $issueCount += $icInc;
                $sql = "UPDATE serial_settings SET issueCount = ? WHERE ifaceid = ?";

                $this->dbConn->dbQuery($sql, $issueCount, $iface['ifaceid']);
            } else {

                // Check to see if Alarm is already active
                if (is_null($alarmID)) {

                    //Send Email for Low Threshold
                    $valArray = [];
                    $valArray[1] = '';   //USER
                    $valArray[2] = 'Serial Data is Below Threshold';  //Message
                    $valArray[3] = count($rawFile);  //Value 1
                    $valArray[4] = '';  //Value 2
                    $valArray[5] = '';  //Value 3
                    $valArray[6] = 'Issue ' . $issueCount . ' of 3'; //Channel
                    $valArray[7] = '10 Minutes';  //Duration
                    $msgline = $this->common->buildMsgLine($valArray);

                    $ah = new AH();

                    $alarmID = $ah->raiseAlarm("serial", $iface['ifaceid'], $msgline);

                    $this->updateAlarmID($iface['ifaceid'], $alarmID[0]);

                    $alerts[] = implode("|", $valArray);

                    $alerts['active'][$alarmID[0]] = implode("|", $valArray);
                } else {
                    //Alarm still active

                    echo "Alarm Active, Skipping" . PHP_EOL;
                    $sql = "SELECT msgLine FROM kamsAlarms where id = ?";

                    $results = $this->dbConn->dbQuery($sql, $alarmID);

                    $alerts['active'][$alarmID] = $results[0]['msgLine'];
                }
            }
        } else {
            if ($alarmID) {
                //Active Alarm cleared.

                $valArray[1] = '';   //USER
                $valArray[2] = 'Serial Data volume has Been Restored';  //Message
                $valArray[3] = count($rawFile);  //Value 1
                $valArray[4] = '';  //Value 2
                $valArray[5] = '';  //Value 3
                $valArray[6] = ''; //Channel
                $valArray[7] = '10 Minutes';  //Duration

                $msgline = $this->common->buildMsgLine($valArray);
                $ah = new AH();
                $ah->clearAlarm("serial", $alarmID, $iface['ifaceid'], $msgline);

                $sql = "SELECT msgLine FROM kamsAlarms where id = ?";

                $results = $this->dbConn->dbQuery($sql, $alarmID);
                $alerts['clear'][$alarmID] = $results[0]['msgLine'];

                //Resetting Issue Count

                $sql = "UPDATE serial_settings SET issueCount = 0 WHERE ifaceid = ?";

                $this->dbConn->dbQuery($sql, $iface['ifaceid']);
            } else {

                $sql = "UPDATE serial_settings SET issueCount = 0 WHERE ifaceid = ?";

                $this->dbConn->dbQuery($sql, $iface['ifaceid']);
                //No alarm, no foul
            }
        }

        /**
         * Check and handle Agent code.
         */

        //  $this->checkAgent($rawFile, $iface);

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
            $modStatus[$iArray[0]]['threshold'] = $iArray[2];
        }

        return $modStatus;
    }

    public function RecordCron($file, $iface = null)
    {
        $tnow = time();


        $x = count($file);
        $line = "";
        $tnow = time();

        file_put_contents("/usr/src/KCM/src/Modules/Crons/KCM-Cron-Service.log", "Serial Cron Run Finished : " . $x  . " lines found, Last time stamp is " . $tnow . PHP_EOL, FILE_APPEND);
        $sql = "INSERT INTO serial_data (iface, size,epoch) VALUES  (?,?,?)";

        $this->dbConn->dbQuery($sql, $iface['ifaceid'], $x, $tnow);

        $this->thru[$iface['ifaceid']] = $x;
    }

    private function updateChannelAlarm($channel, $alarmID)
    {
        $sql = "UPDATE channel_data SET alarm = ? WHERE channel_id = ?";
        $this->dbConn->dbQuery($sql, (string) $alarmID, $channel);
    }

    public function modEnabled()
    {
        return $this->config['SerialMonitorPage'];
    }

    private function checkNightHours($hour)
    {

        $NightStart = strtotime($this->config['nightStartHour'] . '00');
        $NightEnd = strtotime($this->config['nightEndHour'] . '00');

        if ($hour >= $NightStart || $hour < $NightEnd) {
            $newNumber = .25;
        } else {
            $newNumber = 1;
        }

        return $newNumber;
    }

    public function checkAgent($rawFile, $iface)
    {

        //Change here for Check Orange was CALL upercase
        $parsedArray = ($this->common->getContents($rawFile, "Call DISCONNECTED BY AGENT = ", PHP_EOL));
        foreach ($parsedArray as $Alerts) {  //Start of Seperate Call Loop
            //$parsed = get_string_between($test, $ServerRow['server_ip'], 'ZBX');
            //echo $parsed."----";
            $parsed = $Alerts;

            echo $parsed;
            echo "\n";
            $Name = strtok($parsed, '/');
            echo $Name;
            echo "\n";
            $NumberOrig = filter_var($parsed, FILTER_SANITIZE_NUMBER_INT);
            $Number =  preg_replace('/\D/', '', $NumberOrig);
            echo $Number;
            echo "\n";
            //$Role = substr($parsed, strpos($parsed, "ROLE = ") + 7);
            $del = "ROLE = ";
            $pos = strpos($parsed, $del);
            $Role = substr($parsed, $pos + strlen($del), strlen($parsed));
            echo  $Role;
            echo "\n";
            echo "\n";

            $sql = "SELECT * FROM serial_entries WHERE AgentID = ?";

            $IDresult = $this->dbConn->dbQuery($sql, $Number);

            //Check if the Number Exists in the Database 
            if ($IDresult->num_rows === 0) {  //Start Duplicate Extension Loop 

                //Check if the Name already exists with another Extension 
                $sql = "SELECT * FROM serial_entries WHERE Name = ?";
                $NAMEresult = $this->dbConn->dbQuery($sql, $Name);


                if ($NAMEresult->num_rows !== 0) {  //Name Check Loop 
                    $sql = "INSERT INTO serial_entries (Name,Roles,AgentID) VALUES (?,?,?)";
                    $result = $this->dbConn->dbQuery($sql, $Name, $Role, $Number);
                    echo "Did Find a Name  is here \n";
                    print_r($result);

                    for ($i = 0; $i  < mysqli_num_rows($NAMEresult); $i++) {
                        $CurrentNAMERow =  mysqli_fetch_array($NAMEresult);
                        $Number .= "," . $CurrentNAMERow['AgentID'];
                        echo "The new number is " . $Number . "\n";
                    }

                    if ($this->config['addSerialUsersV15'] == 'true') {  //Check the KAMS-Setting-file to see if we are adding
                        include('/usr/src/Serial/serial-modify.php');
                    }
                } else { //End Name Check Loop 

                    echo "Didnt  Find a Name and added here \n";
                    $query = "Insert INTO Entries (Name,Roles,AgentID) VALUES ('$Name','$Role','$Number')";
                    $result = mysqli_query($link, $query);




                    $MyMessage = "The system tried to insert " . $query . " and if there was an error it was " . mysqli_error($link);
                    file_put_contents('/usr/src/Serial/issue-mysql.txt', $MyMessage);
                    echo "Im Here";

                    if ($this->config['addSerialUsersV15'] == 'true') {  //Check the KAMS-Setting-file to see if we are adding
                        include('/usr/src/Serial/serial-add.php');
                    } //End KAMS-Settings-file

                } //End of else statement 

            }  //End of Duplicate Extension Loop

        } //End of Seperate Call Loop 
    }

    public function updateAlarmID($iface, $alarmID)
    {
        $sql = "UPDATE serial_settings SET alarmID = ? WHERE ifaceid = ?";

        $this->dbConn->dbQuery($sql, $alarmID, $iface);
    }
}
