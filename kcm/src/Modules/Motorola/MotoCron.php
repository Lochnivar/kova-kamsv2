<?php

namespace Kova\Kams\Kcm\Modules\Motorola;

use Kova\Kams\Kcm\Modules\Common\Common as Common;
use Kova\Kams\Kcm\Modules\Common\Communicator;
use Kova\Kams\Common\Database as DB;
use Kova\Kams\Kcm\Modules\Common\AlarmHandler as AH;


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
        $this->fileName = kova_path('tmp/' . $this->mod . 'dump');
        $this->modEnabled = strtolower($this->config['MotorolaPage']);
    }

    public function startCron()
    {
        $x = 0;
        $ifs = explode("~", $this->ifaces);
        $pids = [];

        foreach ($ifs as $iface) {
            $iArray = explode("|", $iface);
            $ifaceName = $iArray[0];
            
            // Pipe tcpdump directly to parser
            // Use process groups for better monitoring and cleanup
            $parserScript = __DIR__ . '/MotoParser.php';
            // Create a new process group with setsid so we can kill the entire pipeline
            $command = "setsid sh -c 'timeout 6000 /usr/bin/tcpdump -vvvvv -tt -A -i " . escapeshellarg($ifaceName) 
                . " dst port 50150 and greater 100 | /usr/bin/php " . escapeshellarg($parserScript) 
                . " > /dev/null 2>&1' & echo $!";
            
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

        var_dump("ifaces", $ifaces);
        
        // Data is already in database from real-time parser
        // No need to read files - just process alerts and reports
        
        // Step two : Check Alert Conditions
        $modStatus["alerts"] = $this->AlertCron();

        // Step three: Report the results
        $modStatus['status'] = $this->ReportCron();

        var_dump("Tracker 1", $modStatus);

        return $modStatus;
    }

    public function AlertCron()
    {
        $alerts = [];
        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('*')
           ->from('moto_channel_data')
           ->where("timeout_number <> '0'")
           ->orderBy('last_activity', 'DESC')
           ->addOrderBy('channel_id', 'ASC');
        $results = $this->dbConn->executeQueryBuilder($qb);
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

                    $qb = $this->dbConn->createQueryBuilder();
                    $qb->select('msgLine')
                       ->from('kamsAlarms')
                       ->where('id = :id')
                       ->setParameter('id', $row['alarm']);
                    $results = $this->dbConn->executeQueryBuilder($qb);

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

                    $qb = $this->dbConn->createQueryBuilder();
                    $qb->select('msgLine')
                       ->from('kamsAlarms')
                       ->where('id = :id')
                       ->setParameter('id', $row['alarm']);
                    $results = $this->dbConn->executeQueryBuilder($qb);


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

        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('COUNT(id) AS count')
           ->from('moto_channel_data')
           ->where("timeout_number <> '0'");
        $request = $this->dbConn->executeQueryBuilder($qb);
        $countActive = $request[0]['count'] ?? 0;
        $modStatus['activeChannels'] = $countActive;

        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('COUNT(id) AS count')
           ->from('moto_channel_data')
           ->where("timeout_number = '0'");
        $request = $this->dbConn->executeQueryBuilder($qb);

        $notMonitored = $request[0]['count'] ?? 0;

        $modStatus['notMonitored'] = $notMonitored;

        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('*')
           ->from('moto_channel_data')
           ->where("timeout_number <> '0'")
           ->orderBy('last_activity', 'DESC')
           ->addOrderBy('channel_id', 'ASC');
        $result = $this->dbConn->executeQueryBuilder($qb);
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

    /**
     * RecordCron is no longer needed - data is recorded in real-time by MotoParser
     * This method is kept for backward compatibility but does nothing
     */
    public function RecordCron($file, $iface = null)
    {
        // Data is already in database from real-time parser
        // No action needed
        echo "RecordCron: Data already in database from real-time parser" . PHP_EOL;
        return;
    }

    private function updateChannelAlarm($channel, $alarmID)
    {
        $this->dbConn->update('moto_channel_data', ['alarm' => (string) $alarmID], ['channel_id' => $channel]);
    }

    public function modEnabled()
    {
        return $this->modEnabled;
    }
}
