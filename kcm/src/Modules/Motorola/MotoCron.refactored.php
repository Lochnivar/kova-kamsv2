<?php

declare(strict_types=1);

namespace Kova\Kams\Kcm\Modules\Motorola;

use Kova\Kams\Core\AbstractCron;
use Kova\Kams\Core\Container;
use Kova\Kams\Common\Config;
use Kova\Kams\Kcm\Modules\Common\AlarmHandler as AH;

/**
 * Motorola Cron - Refactored to use AbstractCron and DI Container.
 * 
 * This is a refactored example showing the new pattern.
 * Once verified, this can replace the original MotoCron.php
 */
class MotoCronRefactored extends AbstractCron
{
    protected function initialize(): void
    {
        $this->mod = "moto";
        $this->ifaces = $this->config['MotorolaInterfaceName'] ?? '';
        $this->modEnabled = strtolower($this->config['MotorolaPage'] ?? 'no');
        $this->fileName = kova_path('tmp/motodump');
    }

    public function startCron(): array
    {
        $pids = [];
        $ifs = explode("~", $this->ifaces);

        foreach ($ifs as $iface) {
            $iArray = explode("|", $iface);
            $ifArray[$iArray[0]] = [];
            $command = "timeout 6000 /usr/bin/tcpdump -vvvvv -tt -A -i " . $iArray[0] . " dst port 50150 and greater 100 >>" . $this->fileName . "-" . $iArray[0] . " & echo $!";
            exec($command, $output);
            
            $this->logger->info("Starting Motorola cron", ['interface' => $iArray[0], 'pid' => $output[0] ?? null]);
            
            if (isset($output[0])) {
                $this->dbConn->putProcID($this->mod, $output[0], $iArray[0]);
                $pids[] = $output[0];
            }
        }

        return $pids;
    }

    public function processCron(): array
    {
        return $this->ReportCron();
    }

    public function AlertCron(): array
    {
        $alerts = [];
        $results = $this->dbConn->select("SELECT * from moto_channel_data WHERE timeout_number <> '0' ORDER BY last_activity DESC ,channel_id");
        $valArray = [];
        $issues = 0;

        $alerts['active'] = [];
        $alerts['clear'] = [];

        foreach ($results as $row) {
            $TimeValue = $row[4] == '1' ? substr_replace($row[5], "", -1) : $row[5];
            $timeCheck = $this->calculateTimeCheck($row[4], $row[5]);

            if ($row[3] < $timeCheck && $row[3] != "") {
                if (is_null($row['alarm'])) {
                    $valArray = $this->buildAlarmArray($row, $TimeValue, 'Motorola Recorder Channel No Data Issue');
                    $msgline = $this->common->buildMsgLine($valArray);

                    $ah = new AH();
                    $alarmID = $ah->raiseAlarm("moto", null, $msgline);

                    $this->updateChannelAlarm($row['channel_id'], $alarmID[0]);
                    $alerts['active'][$alarmID[0]] = implode("|", $valArray);
                } else {
                    $results = $this->dbConn->select("SELECT msgLine FROM kamsAlarms where id = ?", $row['alarm']);
                    $alerts['active'][$row['alarm']] = $results[0]['msgLine'] ?? '';
                }
            } else {
                if (!empty($row['alarm'])) {
                    $valArray = $this->buildAlarmArray($row, $TimeValue, 'Motorola Recorder Channel Volume Restored');
                    $msgline = $this->common->buildMsgLine($valArray);

                    $ah = new AH();
                    $ah->clearAlarm($this->mod, $row['alarm'], $row['channel_id'], $msgline);

                    $results = $this->dbConn->select("SELECT msgLine FROM kamsAlarms where id = ?", $row['alarm']);
                    $alerts['clear'][$row['alarm']] = $results[0]['msgLine'] ?? '';
                }
            }
        }

        return $alerts;
    }

    public function ReportCron(): array
    {
        $modStatus = [];

        $request = $this->dbConn->select("SELECT count(id) AS count FROM moto_channel_data WHERE timeout_number <> '0'");
        $countActive = $request[0]['count'] ?? 0;
        $modStatus['activeChannels'] = $countActive;

        $request = $this->dbConn->select("SELECT count(id) AS count FROM moto_channel_data WHERE timeout_number = '0'");
        $notMonitored = $request[0]['count'] ?? 0;
        $modStatus['notMonitored'] = $notMonitored;

        $result = $this->dbConn->select("SELECT * from moto_channel_data WHERE timeout_number <> '0' ORDER BY last_activity DESC ,channel_id");
        $issues = 0;

        foreach ($result as $rowOne) {
            $TimeValue = $rowOne[4] == '1' ? substr_replace($rowOne[5], "", -1) : $rowOne[5];
            $timeCheck = $this->calculateTimeCheck($rowOne[4], $rowOne[5]);
            
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

    public function RecordCron($file, $iface = null): void
    {
        $this->logger->info("Recording Motorola cron", ['lines' => count($file)]);
        $timeNow = time();

        $results = $this->dbConn->select("SELECT * FROM moto_channel_data");
        $channelIDs = [];

        foreach ($file as $line) {
            if (strpos($line, "<DeviceID>") <> 0) {
                $stringpos = strpos($line, "<AstroEvent");
                $line = substr($line, $stringpos);
                $packet = simplexml_load_string($line);
                $lineid = $packet->CallStatusEventArgs->CallStatus->DeviceID ?? null;
                if ($lineid) {
                    $channelIDs[] = (string) $lineid;
                }
            }
        }

        $uniqueIDs = array_unique($channelIDs);

        foreach ($uniqueIDs as $id) {
            if ($id == "") {
                continue;
            }

            $result = $this->dbConn->select("SELECT channel_id FROM moto_channel_data WHERE channel_id = ?", $id);

            if (count($result) == 0) {
                $this->logger->info("Inserting new channel", ['channel_id' => $id]);
                try {
                    $this->dbConn->insert('moto_channel_data', ['channel_id' => $id]);
                } catch (\Exception $e) {
                    $this->logger->error("Error inserting channel", ['channel_id' => $id, 'error' => $e->getMessage()]);
                }
            }

            $this->dbConn->update('moto_channel_data', ['last_activity' => $timeNow], ['channel_id' => $id]);
        }
    }

    private function calculateTimeCheck($value, $unit): int
    {
        switch ($unit) {
            case "Hours":
                return time() - ($value * 3600);
            case "Days":
                return time() - ($value * 86400);
            case "Weeks":
                return time() - ($value * 604800);
            default:
                return 0;
        }
    }

    private function buildAlarmArray($row, string $timeValue, string $message): array
    {
        return [
            1 => '',
            2 => $message,
            3 => str_replace(',', '', date('Y-m-d H:i:s', $row['last_activity'] ?? time())) . " + ",
            4 => '',
            5 => '',
            6 => $row[2] . " + ",
            7 => $row[4] . " " . $timeValue . " + "
        ];
    }

    private function updateChannelAlarm(string $channel, $alarmID): void
    {
        $this->dbConn->update('moto_channel_data', ['alarm' => (string) $alarmID], ['channel_id' => $channel]);
    }
}

