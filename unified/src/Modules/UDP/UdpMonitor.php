<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\UDP;

use Kova\Kams\Common\Database;
use Kova\Kams\Common\Config;
use Kova\Kams\Core\Common;

/**
 * UdpMonitor
 * 
 * Provides UDP monitor display similar to v1/netmon-ng/index.html
 * but using v2 standards (DBAL, namespaces, etc.)
 */
class UdpMonitor
{
    private Database $db;
    private Config $config;
    private Common $common;

    public function __construct(?Database $db = null, ?Config $config = null, ?Common $common = null)
    {
        $this->db = $db ?? new Database('kams');
        $this->config = $config ?? new Config();
        $this->common = $common ?? new Common($this->config->config);
    }

    /**
     * Get UDP interfaces with their data
     * 
     * @return array{interfaces: array, data: array}
     */
    public function getInterfacesData(): array
    {
        // Get interfaces from config
        $ifacesRaw = $this->common->getIfaces('udp');
        
        $interfaces = [];
        $interfaceData = [];
        
        // Handle different iface formats
        if (!is_array($ifacesRaw)) {
            return ['interfaces' => [], 'data' => []];
        }
        
        foreach ($ifacesRaw as $iface) {
            $ifaceName = '';
            $ifaceAlias = '';
            
            if (is_array($iface)) {
                $ifaceName = $iface['name'] ?? '';
                $ifaceAlias = $iface['alias'] ?? $ifaceName;
            } elseif (is_string($iface)) {
                // Handle pipe-delimited format: "name|alias"
                $parts = explode('|', $iface);
                $ifaceName = $parts[0] ?? '';
                $ifaceAlias = $parts[1] ?? $ifaceName;
            }
            
            if (empty($ifaceName)) {
                continue;
            }
            
            $interfaces[] = [
                'name' => $ifaceName,
                'alias' => $ifaceAlias
            ];
            
            // Get UDP data for this interface
            $interfaceData[$ifaceName] = [
                'hour' => $this->getUDPData($ifaceName, '-1 hour'),
                'thirty' => $this->getUDPData($ifaceName, '-30 minutes'),
                'ten' => $this->getUDPData($ifaceName, '-10 minutes'),
                'lastTime' => $this->getLastPacketStamp($ifaceName),
                'threshold' => $this->getThreshold($ifaceName)
            ];
        }
        
        return [
            'interfaces' => $interfaces,
            'data' => $interfaceData
        ];
    }

    /**
     * Get UDP data for a specific interface and time period
     * 
     * @param string $iface Interface name
     * @param string $timeVal Time string (e.g., "-1 hour")
     * @return float Average UDP packets per minute
     */
    private function getUDPData(string $iface, string $timeVal): float
    {
        try {
            // Get alarm ID from settings
            $qb = $this->db->createQueryBuilder();
            $qb->select('alarmID')
               ->from('udp_settings')
               ->where('ifaceid = :iface')
               ->setParameter('iface', $iface)
               ->setMaxResults(1);
            
            $settingsResult = $this->db->executeQueryBuilder($qb);
            if (empty($settingsResult)) {
                return 0.0;
            }
            
            $oneHourAgo = strtotime($timeVal);
            
            // Get average UDP packets
            $qb = $this->db->createQueryBuilder();
            $qb->select('COUNT(epoch) as num')
               ->addSelect('AVG(udp_packets) as avgPackets')
               ->from('udp_data')
               ->where('iface = :iface')
               ->andWhere('epoch > :time')
               ->setParameter('iface', $iface)
               ->setParameter('time', $oneHourAgo);
            
            $result = $this->db->executeQueryBuilder($qb);
            
            if (empty($result) || empty($result[0]['avgPackets'])) {
                return 0.0;
            }
            
            return (float)$result[0]['avgPackets'];
        } catch (\Throwable $e) {
            return 0.0;
        }
    }

    /**
     * Get last packet timestamp for an interface
     * 
     * @param string $iface Interface name
     * @return string Formatted timestamp or empty string
     */
    private function getLastPacketStamp(string $iface): string
    {
        try {
            $qb = $this->db->createQueryBuilder();
            $qb->select('MAX(epoch) as max_epoch')
               ->from('udp_data')
               ->where('iface = :iface')
               ->andWhere('udp_packets > 0')
               ->setParameter('iface', $iface);
            
            $result = $this->db->executeQueryBuilder($qb);
            
            if (empty($result) || empty($result[0]['max_epoch'])) {
                return '';
            }
            
            $lastTime = (int)$result[0]['max_epoch'];
            $dt = new \DateTime();
            $dt->setTimestamp($lastTime);
            
            return $dt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Get threshold for an interface
     * 
     * @param string $iface Interface name
     * @return int Threshold value
     */
    private function getThreshold(string $iface): int
    {
        try {
            $qb = $this->db->createQueryBuilder();
            $qb->select('threshold')
               ->from('udp_settings')
               ->where('ifaceid = :iface')
               ->setParameter('iface', $iface)
               ->setMaxResults(1);
            
            $result = $this->db->executeQueryBuilder($qb);
            
            if (empty($result) || !isset($result[0]['threshold'])) {
                return 0;
            }
            
            return (int)$result[0]['threshold'];
        } catch (\Throwable $e) {
            return 0;
        }
    }
}

