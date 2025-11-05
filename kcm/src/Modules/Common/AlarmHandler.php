<?php

namespace Kova\Kams\Kcm\Modules\Common;

use Kova\Kams\Common\Database as DB;
use Kova\Kams\Kcm\Modules\Common\Communicator as Comms;

class AlarmHandler
{

    public $dbConn;
    public $valArray;
    public $comms;

    public function __construct()
    {

        $this->dbConn = new DB('kams');
        $this->comms = new Comms();

    }

    public function raiseAlarm($mod, $iface, $msgline)
    {

        $tnow = time();
        $active = "1";

        $this->dbConn->insert('kamsAlarms', [
            'active' => $active,
            'timestamp' => $tnow,
            'module' => $mod,
            'iface' => $iface,
            'msgline' => $msgline
        ]);

        $lastId = $this->dbConn->lastInsertId();

        
        $this->comms->SendComms($msgline);

        return ['LAST_INSERT_ID()' => $lastId];
    }

    public function clearAlarm($mod, $alarmID, $iface, $msgline)
    {
        switch ($mod) {
            case "moto":
                $this->dbConn->update('moto_channel_data', ['alarm' => null], ['channel_id' => $iface, 'alarm' => $alarmID]);
                break;
            case "serial":
                $this->dbConn->update('serial_settings', ['alarmID' => null], ['ifaceid' => $iface, 'alarmID' => $alarmID]);
                break;
            case "udp":
                $this->dbConn->update('udp_settings', ['alarmID' => null], ['ifaceid' => $iface, 'alarmID' => $alarmID]);
                break;
            default:
                break;
        }

        $this->dbConn->update('kamsAlarms', ['active' => '0'], ['id' => $alarmID]);

        $this->comms->SendComms($msgline);

    }

    public function checkAlarm($alarmID) {}
}
