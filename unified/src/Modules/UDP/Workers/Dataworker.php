<?php

namespace Kova\Unified\Modules\UDP\Workers;

use Kova\Unified\Modules\Common\Database;
use Kova\Unified\Modules\Common\Common;

class Dataworker
{
    /**
     * The purpose of this file is to fetch the data needed to fuel the charts,
     * graphs, and gauges on the front page.
     */

    public $configs;
    public $cargo;
    public $dbConn;
    public $mod = "udp";

    public function __construct($configs)
    {
        $this->configs = $configs;
        $this->dbConn = new Database("kams");
    }

        public function getUDPAvgs()
    {

        date_default_timezone_set('America/New_York');
        
        $common = new Common($this->configs);


        $ifacesRaw = $common->getIfaces($this->mod);


        if (is_array($ifacesRaw)) {
            $ifaces = $ifacesRaw;
        } else {
            $ifaces = explode("~", $ifacesRaw);
        }

        $serialArray = [];

        foreach ($ifaces as $k => $v) {
            $serialArray[$k]['hour'] = $this->getUDPData($k, "-1 hour");
            $serialArray[$k]['thirty'] = $this->getUDPData($k, "-30 minutes");
            $serialArray[$k]['ten'] = $this->getUDPData($k, "-10 minutes");
            $serialArray[$k]['lastTime'] = $this->getLastPacketStamp($k);
            }


        return $serialArray;
    }

    public function getUDPData($iface, $timeVal){

         $sql = "SELECT * FROM udp_settings where ifaceid = ?";

        $results = $this->dbConn->dbQuery($sql, $iface);

        $alarmID = $results[0]['alarmID'];

        $oneHourAgo = strtotime($timeVal);

        $sql = "SELECT count(epoch) AS num, avg(udp_packets) AS avgPackets FROM udp_data WHERE iface = ? AND epoch > ?";

        $results = $this->dbConn->dbQuery($sql, $iface, $oneHourAgo);

        $avg = $results[0]['avgPackets'];
        $count = $results[0]['num'];

        return $avg;

    }

    
    public function getLastPacketStamp($iface)
    {
        $sql = "select MAX(epoch) from udp_data WHERE iface = '" . $iface . "' AND udp_packets > 0";
        $result = $this->dbConn->dbQuery($sql);

        $lasttime = $result[0];

        unset($dbConn);

        $dt = new \DateTime();
        $dt->setTimeStamp($lasttime[0]);


        return $dt->format("Y-m-d H:i:s");
    }
}
