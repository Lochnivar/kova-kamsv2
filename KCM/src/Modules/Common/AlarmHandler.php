<?php

namespace Kova\Kcm\Modules\Common;

use Kova\Kcm\Modules\Common\Database as DB;
use Kova\Kcm\Modules\Common\Communicator as Comms;

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

        $sql = "INSERT INTO kamsAlarms(active, timestamp, module, iface, msgline) VALUES (?,?,?,?,?)";
        $this->dbConn->dbQuery($sql, $active, $tnow, $mod, $iface, $msgline);

        $sql = "SELECT LAST_INSERT_ID()";
        $result = $this->dbConn->dbQuery($sql);

        
        $this->comms->SendComms($msgline);

        return $result[0];
    }

    public function clearAlarm($mod, $alarmID, $iface, $msgline)
    {
        $sql = "";
        switch ($mod) {
            case "moto":
                $sql = "UPDATE moto_channel_data SET alarm = NULL WHERE channel_id = ? AND alarm = ?";
                break;
            case "serial":
                $sql = "UPDATE serial_settings SET alarmID = NULL WHERE ifaceid = ? AND alarmID = ?";
                break;
            case "udp":
                $sql = "UPDATE udp_settings SET alarmID = NULL WHERE ifaceid = ? AND alarmID = ?";
                break;
            default:
                break;
        }

        if ($sql) {
            $this->dbConn->dbQuery($sql, $iface, $alarmID);
        }
        $sql = "UPDATE kamsAlarms SET active = '0' WHERE id = ?";

        $this->dbConn->dbQuery($sql, $alarmID);

        $this->comms->SendComms($msgline);

    }

    public function checkAlarm($alarmID) {}
}
