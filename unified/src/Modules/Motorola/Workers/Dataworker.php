<?php

namespace Kova\Kams\Unified\Modules\Motorola\Workers;

use Kova\Kams\Unified\Modules\Common\Database;
use Kova\Kams\Unified\Modules\Common\Common;

class Dataworker
{
    /**
     * The purpose of this file is to fetch the data needed to fuel the charts,
     * graphs, and gauges on the front page.
     */

    public $configs;
    public $cargo;
    public $dbConn;
    public $mod = "moto";

    public function __construct($configs)
    {
        $this->configs = $configs;
        $this->dbConn = new Database("kams");
    }


    public function getmotoAvgs()
    {

    }

    public function getMotoData()
    {

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

        $active = 0;
        
        foreach ($result as $rowOne) {

            $stringTime = "-" . $rowOne[4] . " " . $rowOne[5];

            $timeLimit = strtotime($stringTime);

            if ($rowOne[3] > $timeLimit) {
                $active++;
            } else {
                $issues++;
            }
        }

        $modStatus['activeChannels'] = $active;
        $modStatus['issues'] = $issues;



        return $modStatus;
    }


    public function getLastPacketStamp($iface)
    {
        $sql = "select MAX(epoch) from moto_data WHERE iface = '" . $iface . "' AND size > 0";
        $result = $this->dbConn->dbQuery($sql);

        $lasttime = $result[0];

        unset($dbConn);

        $dt = new \DateTime();
        $dt->setTimeStamp($lasttime[0]);


        return $dt->format("Y-m-d H:i:s");
    }
}
