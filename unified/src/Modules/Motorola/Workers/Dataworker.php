<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Motorola\Workers;

use Kova\Kams\Common\Database;

class Dataworker
{
    /**
     * The purpose of this file is to fetch the data needed to fuel the charts,
     * graphs, and gauges on the front page.
     */

    public array $configs;
    public Database $dbConn;
    public string $mod = "moto";

    public function __construct(array $configs)
    {
        $this->configs = $configs;
        $this->dbConn = new Database('kams');
    }

    /**
     * Get Motorola channel status data
     * Returns counts for active channels, warnings/issues, and unmonitored channels
     * 
     * @return array{activeChannels: int, issues: int, notMonitored: int}
     */
    public function getMotoData(): array
    {
        $timeNow = time();
        $issues = 0;
        $active = 0;
        
        // Get all monitored channels (timeout_number <> 0)
        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('*')
           ->from('moto_channel_data')
           ->where('timeout_number <> :zero')
           ->setParameter('zero', 0)
           ->orderBy('last_activity', 'DESC')
           ->addOrderBy('channel_id', 'ASC');
        
        $result = $this->dbConn->executeQueryBuilder($qb);
        
        // Process each channel to determine if active or has issues
        foreach ($result as $row) {
            $timeoutNumber = (int)($row['timeout_number'] ?? 0);
            $timeoutValue = (string)($row['timeout_value'] ?? 'Hours');
            $lastActivity = (int)($row['last_activity'] ?? 0);
            
            // Calculate time threshold based on timeout settings
            $timeCheck = $this->calculateTimeThreshold($timeNow, $timeoutNumber, $timeoutValue);
            
            // Check if channel is active or has issues
            if ($lastActivity == 0 || $lastActivity == '') {
                // No activity ever recorded
                $issues++;
            } elseif ($lastActivity > $timeCheck) {
                // Recent activity - channel is active
                $active++;
            } else {
                // Activity older than timeout - has issues
                $issues++;
            }
        }
        
        // Count unmonitored channels (timeout_number = 0)
        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('COUNT(id) as count')
           ->from('moto_channel_data')
           ->where('timeout_number = :zero')
           ->setParameter('zero', 0);
        
        $unmonitoredResult = $this->dbConn->executeQueryBuilder($qb);
        $notMonitored = (int)($unmonitoredResult[0]['count'] ?? 0);
        
        return [
            'activeChannels' => $active,
            'issues' => $issues,
            'notMonitored' => $notMonitored
        ];
    }
    
    /**
     * Calculate time threshold based on timeout settings
     * 
     * @param int $timeNow Current timestamp
     * @param int $timeoutNumber Timeout number
     * @param string $timeoutValue Timeout unit (Hours, Days, Weeks)
     * @return int Timestamp threshold
     */
    private function calculateTimeThreshold(int $timeNow, int $timeoutNumber, string $timeoutValue): int
    {
        $timeoutValue = ucfirst(strtolower(trim($timeoutValue)));
        
        switch ($timeoutValue) {
            case 'Hours':
                return $timeNow - ($timeoutNumber * 3600);
            case 'Days':
                return $timeNow - ($timeoutNumber * 86400);
            case 'Weeks':
                return $timeNow - ($timeoutNumber * 604800);
            default:
                // Default to hours if unknown
                return $timeNow - ($timeoutNumber * 3600);
        }
    }

    /**
     * Get last packet timestamp for an interface
     * 
     * @param string $iface Interface name
     * @return string Formatted timestamp or empty string if no data
     */
    public function getLastPacketStamp(string $iface): string
    {
        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('MAX(epoch) as max_epoch')
           ->from('moto_data')
           ->where('iface = :iface')
           ->andWhere('size > 0')
           ->setParameter('iface', $iface);
        
        $result = $this->dbConn->executeQueryBuilder($qb);
        
        if (empty($result) || empty($result[0]['max_epoch'])) {
            return '';
        }
        
        $lastTime = (int)$result[0]['max_epoch'];
        $dt = new \DateTime();
        $dt->setTimestamp($lastTime);
        
        return $dt->format("Y-m-d H:i:s");
    }
}
