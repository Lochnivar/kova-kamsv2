<?php

namespace Kova\Kams\Kcm\Modules\Serial;

use Kova\Kams\Kcm\Modules\Common\Common as Common;
use Kova\Kams\Common\Database as DB;
use Kova\Kams\Kcm\Modules\Common\AlarmHandler as AH;
use Kova\Kams\Kcm\Modules\Common\Communicator;

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
        $pids = [];

        foreach ($ifs as $iface) {
            $iArray = explode("|", $iface);
            $ifaceName = $iArray[0];
            
            // Pipe serial device directly to parser
            // Use process groups for better monitoring and cleanup
            $parserScript = __DIR__ . '/SerialParser.php';
            // Create a new process group with setsid so we can kill the entire pipeline
            $command = "setsid sh -c 'timeout 6000 cat /dev/" . escapeshellarg($ifaceName) 
                . " | /usr/bin/php " . escapeshellarg($parserScript) . " " . escapeshellarg($ifaceName) 
                . " > /dev/null 2>&1' & echo $!";
            
            echo $command . PHP_EOL;
            exec($command, $output);
            echo "Start Cron DB vars" . PHP_EOL;
            var_dump($this->mod, $output[$x], $ifaceName);

            // Store the process group leader PID for monitoring
            $shellPid = $output[$x];
            $this->dbConn->putProcID($this->mod, $shellPid, $ifaceName);
            $pids[] = $shellPid;
            $x++;
        }
        
        return $pids;
    }


    public function ProcessCron()
    {
        $modStatus = [];
        $ifaces = $this->dbConn->getIfaces($this->mod);

        foreach ($ifaces as $iface) {
            var_dump($iface['ifaceid']);

            // Data is already in database from real-time parser
            // No need to read files - just query the latest data
            $this->loadLatestData($iface);

            // Step two : Check Alert Conditions
            // AlertCron now works from database data, not raw files
            $modStatus["alerts"] = $this->AlertCron(null, $iface);

            // Step three: Report the results
            $modStatus['status'] = $this->ReportCron();
        }

        return $modStatus;
    }
    
    /**
     * Load latest data from database (replaces file reading)
     */
    private function loadLatestData($iface): void
    {
        // Get the most recent data entry for this interface
        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('*')
           ->from('serial_data')
           ->where('iface = :iface')
           ->orderBy('epoch', 'DESC')
           ->setMaxResults(1)
           ->setParameter('iface', $iface['ifaceid']);
        $result = $this->dbConn->executeQueryBuilder($qb);
        
        if (!empty($result)) {
            $this->thru[$iface['ifaceid']] = $result[0]['size'] ?? 0;
        }
    }

    public function AlertCron($rawFile, $iface)
    {
        $alerts['active'] = [];
        $alerts['clear'] = [];

        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('issueCount', 'alarmID')
           ->from('serial_settings')
           ->where('ifaceid = :iface')
           ->setParameter('iface', $iface['ifaceid']);
        $results = $this->dbConn->executeQueryBuilder($qb);

        $issueCount = $results[0]['issueCount'] ? $results[0]['issueCount'] : 0;
        $alarmID = $results[0]['alarmID'];

        var_dump($results);

        // Get line count from database instead of file
        // Get the most recent data entry
        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('size')
           ->from('serial_data')
           ->where('iface = :iface')
           ->orderBy('epoch', 'DESC')
           ->setMaxResults(1)
           ->setParameter('iface', $iface['ifaceid']);
        $dataResult = $this->dbConn->executeQueryBuilder($qb);
        
        $lines = !empty($dataResult) ? ($dataResult[0]['size'] ?? 0) : 0;

        if ($lines < $this->config['SerialTriggerLimit']) {
            $hour = date('H');

            if ($issueCount <= 2.5) {
                $icInc = $this->checkNightHours($hour);
                $issueCount += $icInc;
                $this->dbConn->update('serial_settings', ['issueCount' => $issueCount], ['ifaceid' => $iface['ifaceid']]);
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
                    $qb = $this->dbConn->createQueryBuilder();
                    $qb->select('msgLine')
                       ->from('kamsAlarms')
                       ->where('id = :id')
                       ->setParameter('id', $alarmID);
                    $results = $this->dbConn->executeQueryBuilder($qb);

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

                $results = $this->dbConn->select("SELECT msgLine FROM kamsAlarms where id = ?", $alarmID);
                $alerts['clear'][$alarmID] = $results[0]['msgLine'];

                //Resetting Issue Count

                $this->dbConn->update('serial_settings', ['issueCount' => 0], ['ifaceid' => $iface['ifaceid']]);
            } else {

                $this->dbConn->update('serial_settings', ['issueCount' => 0], ['ifaceid' => $iface['ifaceid']]);
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

        $logFile = kova_path('logs/kcm-cron-service.log');
        @file_put_contents($logFile, "Serial Cron Run Finished : " . $x  . " lines found, Last time stamp is " . $tnow . PHP_EOL, FILE_APPEND);
        $this->dbConn->insert('serial_data', [
            'iface' => $iface['ifaceid'],
            'size' => $x,
            'epoch' => $tnow
        ]);

        $this->thru[$iface['ifaceid']] = $x;
    }

    private function updateChannelAlarm($channel, $alarmID)
    {
        $this->dbConn->update('channel_data', ['alarm' => (string) $alarmID], ['channel_id' => $channel]);
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

            $qb = $this->dbConn->createQueryBuilder();
            $qb->select('*')
               ->from('serial_entries')
               ->where('AgentID = :agent')
               ->setParameter('agent', $Number);
            $IDresult = $this->dbConn->executeQueryBuilder($qb);

            //Check if the Number Exists in the Database 
            if (count($IDresult) === 0) {  //Start Duplicate Extension Loop 

                //Check if the Name already exists with another Extension 
                $qb = $this->dbConn->createQueryBuilder();
                $qb->select('*')
                   ->from('serial_entries')
                   ->where('Name = :name')
                   ->setParameter('name', $Name);
                $NAMEresult = $this->dbConn->executeQueryBuilder($qb);


                if (count($NAMEresult) !== 0) {  //Name Check Loop 
                    $this->dbConn->insert('serial_entries', [
                        'Name' => $Name,
                        'Roles' => $Role,
                        'AgentID' => $Number
                    ]);
                    echo "Did Find a Name  is here \n";
                    print_r($result);

                    foreach ($NAMEresult as $CurrentNAMERow) {
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
                    $issueLog = kova_path('logs/serial-issues.log');
                    @file_put_contents($issueLog, $MyMessage . PHP_EOL, FILE_APPEND);
                    echo "Im Here";

                    if ($this->config['addSerialUsersV15'] == 'true') {  //Check the KAMS-Setting-file to see if we are adding
                        $addScript = kova_path('kcm/src/Modules/Serial/serial-add.php');
                        if (file_exists($addScript)) {
                            include($addScript);
                        }
                    } //End KAMS-Settings-file

                } //End of else statement 

            }  //End of Duplicate Extension Loop

        } //End of Seperate Call Loop 
    }

    public function updateAlarmID($iface, $alarmID)
    {
        $this->dbConn->update('serial_settings', ['alarmID' => $alarmID], ['ifaceid' => $iface]);
    }
}
