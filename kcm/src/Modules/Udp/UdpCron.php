<?php

namespace Kova\Kams\Kcm\Modules\Udp;

use Kova\Kams\Kcm\Modules\Common\Common as Common;
use Kova\Kams\Common\Database as DB;
use Kova\Kams\Kcm\Modules\Common\AlarmHandler as AH;

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
        $pids = [];

        foreach ($ifs as $iface) {
            $iArray = explode("|", $iface);
            $ifaceName = $iArray[0];
            
            // Pipe tcpdump directly to parser
            // Use process groups for better monitoring and cleanup
            $parserScript = __DIR__ . '/UdpParser.php';
            // Create a new process group with setsid so we can kill the entire pipeline
            $command = "setsid sh -c 'timeout 6000 /usr/bin/tcpdump udp -vvvvv -tt -l -i " . escapeshellarg($ifaceName) 
                . " | /usr/bin/php " . escapeshellarg($parserScript) . " " . escapeshellarg($ifaceName) 
                . " > /dev/null 2>&1' & echo $!";
            
            exec($command, $output);
            echo "Start Cron DB vars" . PHP_EOL;
            var_dump($this->mod, $output[$x], $ifaceName);

            // Store the process group leader PID for monitoring
            // This PID can be used to kill the entire pipeline (tcpdump + parser)
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

        var_dump("Ifaces", $ifaces);

        foreach ($ifaces as $iface) {
            var_dump("Iface", $iface);

            // Data is already in database from real-time parser
            // No need to read files - just query the latest data
            $this->loadLatestData($iface);

            // Step two : Check Alert Conditions
            // AlertCron now works from database data, not raw files
            $modStatus["alerts"] = $this->AlertCron(null, $iface);
        }
        
        // Step three: Report the results
        $modStatus['status'] = $this->ReportCron();

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
           ->from('udp_data')
           ->where('iface = :iface')
           ->orderBy('epoch', 'DESC')
           ->setMaxResults(1)
           ->setParameter('iface', $iface['ifaceid']);
        $result = $this->dbConn->executeQueryBuilder($qb);
        
        if (!empty($result)) {
            $this->thru[$iface['ifaceid']] = $result[0]['udp_packets'] ?? 0;
            $this->lastPacket[$iface['ifaceid']] = $result[0]['tstamp'] ?? null;
        }
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

        $logFile = kova_path('logs/kcm-cron-service.log');
        @file_put_contents($logFile, "UDP Cron Run Finished : " . $x  . " lines found, Last time stamp is " . $tstamp[0] . PHP_EOL, FILE_APPEND);

        $this->dbConn->insert('udp_data', [
            'iface' => $iface['ifaceid'],
            'udp_packets' => $x,
            'epoch' => $tnow,
            'tstamp' => $tstamp[0]
        ]);

        $this->thru[$iface['ifaceid']] = $x;
        $this->lastPacket[$iface['ifaceid']] = $tstamp[0];

        var_dump("Thru", $this->thru[$iface['ifaceid']]);
        //     $this->threshold[$iface['ifaceid']] = $iface['threshold'];
    }

    public function AlertCron($rawFile, $iface)
    {

        $alerts['active'] = [];
        $alerts['clear'] = [];

        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('*')
           ->from('udp_settings')
           ->where('ifaceid = :iface')
           ->setParameter('iface', $iface['ifaceid']);
        $results = $this->dbConn->executeQueryBuilder($qb);

        $alarmID = $results[0]['alarmID'];

        $oneHourAgo = strtotime("-1 hour");

        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('COUNT(epoch) AS num', 'AVG(udp_packets) AS avgPackets')
           ->from('udp_data')
           ->where('iface = :iface')
           ->andWhere('epoch > :epoch')
           ->setParameter('iface', $iface['ifaceid'])
           ->setParameter('epoch', $oneHourAgo);
        $results = $this->dbConn->executeQueryBuilder($qb);

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
                $qb = $this->dbConn->createQueryBuilder();
                $qb->select('msgLine')
                   ->from('kamsAlarms')
                   ->where('id = :id')
                   ->setParameter('id', $alarmID);
                $results = $this->dbConn->executeQueryBuilder($qb);

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

                $qb = $this->dbConn->createQueryBuilder();
                $qb->select('msgLine')
                   ->from('kamsAlarms')
                   ->where('id = :id')
                   ->setParameter('id', $alarmID);
                $results = $this->dbConn->executeQueryBuilder($qb);
                $alerts['clear'][$alarmID] = $results[0]['msgLine'] ?? null;
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
        $this->dbConn->update('udp_settings', ['alarmID' => $alarmID], ['ifaceid' => $iface]);
    }
}
