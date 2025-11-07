<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Serial;

use Kova\Kams\Common\Database;
use Kova\Kams\Common\Config;

/**
 * SerialMonitor
 * 
 * Provides Serial traffic monitor display similar to v1/serial/index.php
 * but using v2 standards (DBAL, namespaces, etc.)
 */
class SerialMonitor
{
    private Database $db;
    private Config $config;
    private int $timeNow;
    private string $dbName;

    public function __construct(?Database $db = null, ?Config $config = null, string $dbKey = 'serial')
    {
        $this->dbName = $dbKey;
        $this->db = $db ?? new Database($dbKey);
        $this->config = $config ?? new Config();
        $this->timeNow = time();
    }

    /**
     * Render the full serial monitor page
     * Includes graph data, averages, and warnings
     * 
     * @return array{graphData: string, averages: array, warnings: array, rowCount: int, totalRows: int}
     */
    public function renderMonitorData(): array
    {
        // Get graph time setting from config or default to 45
        // Try to get from Serial_Users database settings table first
        $graphTime = 45; // default
        try {
            $qb = $this->db->createQueryBuilder();
            $qb->select('graph_time')
               ->from('settings')
               ->setMaxResults(1);
            $result = $this->db->executeQueryBuilder($qb);
            if (!empty($result) && isset($result[0]['graph_time'])) {
                $graphTime = (int)$result[0]['graph_time'];
            }
        } catch (\Throwable $e) {
            // Fall back to config or default
            $graphTime = $this->config->getInt('SerialGraphTime') ?? 45;
        }
        
        // Get serial trigger limit - try from database first
        $serialTriggerLimit = 0; // default
        try {
            $qb = $this->db->createQueryBuilder();
            $qb->select('SerialTriggerLimit')
               ->from('settings')
               ->setMaxResults(1);
            $result = $this->db->executeQueryBuilder($qb);
            if (!empty($result) && isset($result[0]['SerialTriggerLimit'])) {
                $serialTriggerLimit = (int)$result[0]['SerialTriggerLimit'];
            }
        } catch (\Throwable $e) {
            // Fall back to config or default
            $serialTriggerLimit = $this->config->getInt('SerialTriggerLimit') ?? 0;
        }
        
        // Get graph data
        $graphData = $this->getGraphData($graphTime);
        
        // Get averages
        $averages = $this->getAverages();
        
        // Get warnings
        $warnings = $this->getWarnings($averages, $serialTriggerLimit, $graphTime);
        
        return [
            'graphData' => $graphData['data'],
            'averages' => $averages,
            'warnings' => $warnings,
            'rowCount' => $graphData['rowCount'],
            'totalRows' => $graphData['totalRows'],
            'graphTime' => $graphTime,
            'totalSerial' => $graphData['totalSerial']
        ];
    }

    /**
     * Get graph data for Morris.js chart
     * 
     * @param int $limit Number of data points
     * @return array{data: array, rowCount: int, totalRows: int, totalSerial: float}
     */
    private function getGraphData(int $limit): array
    {
        try {
            $qb = $this->db->createQueryBuilder();
            $qb->select('*')
               ->from('traffic_mon')
               ->orderBy('id', 'DESC')
               ->setMaxResults($limit);
            
            $results = $this->db->executeQueryBuilder($qb);
            
            $graphDataArray = [];
            $totalSerial = 0;
            $rowCount = 0;
            
            foreach ($results as $row) {
                $epoch = (int)($row['epoch'] ?? 0);
                $size = (float)($row['size'] ?? 0);
                
                // Format date for Morris.js
                $dateStr = date('Y-m-d H:i:s', $epoch);
                $dataPoint = (float)number_format($size / 1024, 2, '.', '');
                
                // Build proper array structure for JSON encoding
                $graphDataArray[] = [
                    'x' => $dateStr,
                    'y' => $dataPoint
                ];
                
                $totalSerial += $size;
                $rowCount++;
            }
            
            // Reverse array to show oldest first (Morris.js expects chronological order)
            $graphDataArray = array_reverse($graphDataArray);
            
            $totalSerialKB = number_format($totalSerial / 1024, 2);
            
            return [
                'data' => $graphDataArray,
                'rowCount' => $rowCount,
                'totalRows' => count($results),
                'totalSerial' => (float)$totalSerialKB
            ];
        } catch (\Throwable $e) {
            // Return empty data on error
            return [
                'data' => [],
                'rowCount' => 0,
                'totalRows' => 0,
                'totalSerial' => 0.0
            ];
        }
    }

    /**
     * Get averages for 1 hour, 2 hours, 3 hours
     * 
     * @return array{oneHour: float, twoHours: float, threeHours: float, oneHourHidden: bool, twoHoursHidden: bool, threeHoursHidden: bool, totalRows: int}
     */
    private function getAverages(): array
    {
        try {
            // Get 1 hour average (6 data points, 10 min intervals)
            $qb1Hour = $this->db->createQueryBuilder();
            $qb1Hour->select('size')
                    ->from('traffic_mon')
                    ->orderBy('id', 'DESC')
                    ->setMaxResults(6);
            
            $results1Hour = $this->db->executeQueryBuilder($qb1Hour);
            $total1Hour = 0;
            foreach ($results1Hour as $row) {
                $total1Hour += (float)($row['size'] ?? 0);
            }
            $avg1Hour = round(($total1Hour / 6) / 1024, 2);
            
            // Get 2 hours average (12 data points)
            $qb2Hours = $this->db->createQueryBuilder();
            $qb2Hours->select('size')
                     ->from('traffic_mon')
                     ->orderBy('id', 'DESC')
                     ->setMaxResults(12);
            
            $results2Hours = $this->db->executeQueryBuilder($qb2Hours);
            $total2Hours = 0;
            $count2Hours = count($results2Hours);
            foreach ($results2Hours as $row) {
                $total2Hours += (float)($row['size'] ?? 0);
            }
            $avg2Hours = $count2Hours >= 12 ? round(($total2Hours / 12) / 1024, 2) : 0;
            $twoHoursHidden = $count2Hours < 12;
            
            // Get 3 hours average (18 data points)
            $qb3Hours = $this->db->createQueryBuilder();
            $qb3Hours->select('size')
                     ->from('traffic_mon')
                     ->orderBy('id', 'DESC')
                     ->setMaxResults(18);
            
            $results3Hours = $this->db->executeQueryBuilder($qb3Hours);
            $total3Hours = 0;
            $count3Hours = count($results3Hours);
            foreach ($results3Hours as $row) {
                $total3Hours += (float)($row['size'] ?? 0);
            }
            $avg3Hours = $count3Hours >= 18 ? round(($total3Hours / 18) / 1024, 2) : 0;
            $threeHoursHidden = $count3Hours < 18;
            
            return [
                'oneHour' => $avg1Hour,
                'twoHours' => $avg2Hours,
                'threeHours' => $avg3Hours,
                'oneHourHidden' => false,
                'twoHoursHidden' => $twoHoursHidden,
                'threeHoursHidden' => $threeHoursHidden,
                'totalRows' => $count3Hours
            ];
        } catch (\Throwable $e) {
            // Return empty averages on error
            return [
                'oneHour' => 0,
                'twoHours' => 0,
                'threeHours' => 0,
                'oneHourHidden' => false,
                'twoHoursHidden' => true,
                'threeHoursHidden' => true,
                'totalRows' => 0
            ];
        }
    }

    /**
     * Get warnings based on thresholds
     * 
     * @param array $averages
     * @param int $triggerLimit
     * @param int $graphTime
     * @return array{belowThreshold: bool, noData: bool}
     */
    private function getWarnings(array $averages, int $triggerLimit, int $graphTime): array
    {
        $belowThreshold = false;
        $noData = false;
        
        // Check if average is below threshold
        if ($triggerLimit > 0 && $averages['oneHour'] < $triggerLimit) {
            $belowThreshold = true;
        }
        
        // Check if we have enough data
        if ($averages['totalRows'] < 6) {
            $noData = true;
        }
        
        return [
            'belowThreshold' => $belowThreshold,
            'noData' => $noData
        ];
    }
}

