<?php

namespace Kova\Kams\Unified\Modules\UDP\Workers;

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

        if (empty($ifacesRaw) || !is_array($ifacesRaw)) {
            return [];
        }

        $serialArray = [];

        // getIfaces returns an associative array keyed by interface name
        // Each value is an object with interface, label, threshold
        foreach ($ifacesRaw as $iface) {
            // Skip if not an array
            if (!is_array($iface)) {
                continue;
            }
            
            // Extract interface name - use 'interface' field (new format) or 'name' field (old format)
            $ifaceName = $iface['interface'] ?? $iface['name'] ?? null;
            
            if (empty($ifaceName)) {
                continue;
            }
            
            // Use interface name as the ID for database queries
            $ifaceId = $ifaceName;
            
            // Only query data for this specific configured interface
            $serialArray[$ifaceName]['hour'] = $this->getUDPData($ifaceId, "-1 hour");
            $serialArray[$ifaceName]['thirty'] = $this->getUDPData($ifaceId, "-30 minutes");
            $serialArray[$ifaceName]['ten'] = $this->getUDPData($ifaceId, "-10 minutes");
            $serialArray[$ifaceName]['lastTime'] = $this->getLastPacketStamp($ifaceId);
        }

        return $serialArray;
    }

    public function getUDPData($iface, $timeVal){

         $sql = "SELECT * FROM udp_settings where ifaceid = ?";

        $results = $this->dbConn->dbQuery($sql, $iface);

        // Check if results exist before accessing
        if (empty($results) || !isset($results[0])) {
            // Return 0 if no UDP settings found for this interface
            return 0;
        }

        $alarmID = $results[0]['alarmID'] ?? null;

        $oneHourAgo = strtotime($timeVal);

        $sql = "SELECT count(epoch) AS num, avg(udp_packets) AS avgPackets FROM udp_data WHERE iface = ? AND epoch > ?";

        $results = $this->dbConn->dbQuery($sql, $iface, $oneHourAgo);

        // Check if results exist before accessing
        if (empty($results) || !isset($results[0])) {
            // Return 0 if no data found
            return 0;
        }

        $avg = $results[0]['avgPackets'] ?? 0;
        $count = $results[0]['num'] ?? 0;

        return $avg ?: 0;

    }

    
    public function getLastPacketStamp($iface)
    {
        $sql = "select MAX(epoch) as max_epoch from udp_data WHERE iface = ? AND udp_packets > 0";
        $result = $this->dbConn->dbQuery($sql, $iface);

        // Check if results exist before accessing
        if (empty($result) || !isset($result[0])) {
            // Return current timestamp if no data found
            return date("Y-m-d H:i:s");
        }

        $lasttime = $result[0]['max_epoch'] ?? $result[0][0] ?? null;

        if ($lasttime === null) {
            return date("Y-m-d H:i:s");
        }

        $dt = new \DateTime();
        $dt->setTimeStamp((int)$lasttime);

        return $dt->format("Y-m-d H:i:s");
    }
}
