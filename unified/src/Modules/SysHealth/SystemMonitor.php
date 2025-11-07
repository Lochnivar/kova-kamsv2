<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\SysHealth;

use Kova\Kams\Common\Database;
use Kova\Kams\Common\Config;
use Kova\Kams\Unified\Modules\Common\Communicator;

/**
 * SystemMonitor
 * 
 * Provides System status monitor display similar to v1/Monitor/index.php
 * Shows Zabbix server status with current problems and history
 */
class SystemMonitor
{
    private Database $db;
    private Config $config;
    private Communicator $communicator;

    public function __construct(?Database $db = null, ?Config $config = null, ?Communicator $communicator = null)
    {
        $this->config = $config ?? new Config();
        // Unified Communicator doesn't take parameters - it creates its own Config
        $this->communicator = $communicator ?? new Communicator();
        
        // Use provided database connection or create one for zabbix
        if ($db !== null) {
            $this->db = $db;
        } else {
            // Try to use zabbix database key, fallback to creating direct connection
            try {
                $this->db = new Database('zabbix');
            } catch (\Exception $e) {
                // Create direct connection to zabbix database using same credentials as kams
                $this->db = $this->createZabbixConnection();
            }
        }
    }
    
    /**
     * Create a direct connection to Zabbix database
     * Uses same credentials as kams but different database name
     */
    private function createZabbixConnection(): Database
    {
        // Read kams config to get connection details
        $kamsDb = new Database('kams');
        $kamsConn = $kamsDb->getDb()->getConnection();
        $params = $kamsConn->getParams();
        
        // Create new connection with zabbix database name
        $zabbixParams = [
            'dbname' => 'zabbix',
            'user' => $params['user'] ?? 'kams',
            'password' => $params['password'] ?? 'kams7906',
            'host' => $params['host'] ?? 'localhost',
            'driver' => 'pdo_mysql',
            'charset' => 'utf8mb4'
        ];
        
        $zabbixConn = \Doctrine\DBAL\DriverManager::getConnection($zabbixParams);
        
        // Create a temporary DbAdapter with zabbix config
        $adapter = new \Kova\Kams\Bones\DbAdapter();
        
        // Use reflection to inject zabbix config (hacky but works)
        $reflection = new \ReflectionClass($adapter);
        
        // Get and modify env property
        $envProp = $reflection->getProperty('env');
        $envProp->setAccessible(true);
        $env = $envProp->getValue($adapter);
        $env['zabbix'] = [
            'database' => [
                'host' => $zabbixParams['host'],
                'username' => $zabbixParams['user'],
                'password' => $zabbixParams['password'],
                'db' => $zabbixParams['dbname'],
                'driver' => $zabbixParams['driver'],
                'charset' => $zabbixParams['charset']
            ]
        ];
        $envProp->setValue($adapter, $env);
        
        // Cache the connection
        $connProp = $reflection->getProperty('connections');
        $connProp->setAccessible(true);
        $connections = $connProp->getValue($adapter);
        $connections['zabbix'] = $zabbixConn;
        $connProp->setValue($adapter, $connections);
        
        return new Database('zabbix', $adapter);
    }

    /**
     * Get system monitor data
     * Returns current server status and history
     * 
     * @return array{servers: array, history: array}
     */
    public function getMonitorData(): array
    {
        // Get list of servers from config or Zabbix
        $servers = $this->getServersList();
        
        // Get current problems for each server
        $serverStatus = $this->getServerStatus($servers);
        
        // Get last 7 days history
        $history = $this->getHistory();
        
        return [
            'servers' => $serverStatus,
            'history' => $history
        ];
    }

    /**
     * Get list of servers to monitor
     * 
     * @return array List of server hostnames
     */
    private function getServersList(): array
    {
        try {
            // Try to get from Zabbix API first
            $hosts = $this->communicator->getZBXHosts();
            if (!empty($hosts)) {
                return array_map(function($host) {
                    return $host['name'];
                }, $hosts);
            }
        } catch (\Exception $e) {
            // Fall back to config or empty list
        }
        
        // Fallback: try to get from config
        $serverList = $this->config->get('ServerList', '');
        if (!empty($serverList)) {
            if (is_array($serverList)) {
                return $serverList;
            }
            return explode(',', $serverList);
        }
        
        return [];
    }

    /**
     * Get current status for each server
     * 
     * @param array $servers List of server hostnames
     * @return array Server status data
     */
    private function getServerStatus(array $servers): array
    {
        $serverStatus = [];
        $timeNow = time();
        
        foreach ($servers as $server) {
            try {
                // Query for current problems (r_clock = 0 means not resolved)
                // Note: Original query joins problem table 3 times, but we only need one join
                $qb = $this->db->createQueryBuilder();
                $qb->select('h.host', 'e.clock', 't.description', 'p.severity', 'p.r_clock')
                   ->from('hosts', 'h')
                   ->innerJoin('h', 'items', 'i', 'h.hostid = i.hostid')
                   ->innerJoin('i', 'functions', 'f', 'i.itemid = f.itemid')
                   ->innerJoin('f', 'triggers', 't', 'f.triggerid = t.triggerid')
                   ->innerJoin('t', 'events', 'e', 't.triggerid = e.objectid')
                   ->innerJoin('e', 'problem', 'p', 'e.clock = p.clock')
                   ->where('h.host = :host')
                   ->andWhere('p.r_clock = :zero')
                   ->setParameter('host', $server)
                   ->setParameter('zero', 0)
                   ->orderBy('e.clock', 'DESC')
                   ->setMaxResults(1);
                
                $result = $this->db->executeQueryBuilder($qb);
                
                if (!empty($result) && !empty($result[0]['description'])) {
                    $row = $result[0];
                    $timeDiff = $timeNow - (int)($row['clock'] ?? 0);
                    $severity = (int)($row['severity'] ?? 0);
                    $description = (string)($row['description'] ?? '');
                    
                    $serverStatus[] = [
                        'host' => $server,
                        'severity' => $severity,
                        'description' => $description,
                        'clock' => (int)($row['clock'] ?? 0),
                        'timeDiff' => $timeDiff,
                        'hasProblem' => true
                    ];
                } else {
                    // Server is OK (no problems or resolved)
                    $serverStatus[] = [
                        'host' => $server,
                        'severity' => 0,
                        'description' => '',
                        'clock' => 0,
                        'timeDiff' => 0,
                        'hasProblem' => false
                    ];
                }
            } catch (\Throwable $e) {
                // On error, mark server as OK
                $serverStatus[] = [
                    'host' => $server,
                    'severity' => 0,
                    'description' => '',
                    'clock' => 0,
                    'timeDiff' => 0,
                    'hasProblem' => false
                ];
            }
        }
        
        return $serverStatus;
    }

    /**
     * Get last 7 days history
     * 
     * @return array History events
     */
    private function getHistory(): array
    {
        try {
            $timeNow = time();
            $last7Days = $timeNow - 604800;
            
            $qb = $this->db->createQueryBuilder();
            $qb->select('h.host', 'e.clock', 't.description', 'p.severity')
               ->from('hosts', 'h')
               ->innerJoin('h', 'items', 'i', 'h.hostid = i.hostid')
               ->innerJoin('i', 'functions', 'f', 'i.itemid = f.itemid')
               ->innerJoin('f', 'triggers', 't', 'f.triggerid = t.triggerid')
               ->innerJoin('t', 'events', 'e', 't.triggerid = e.objectid')
               ->innerJoin('e', 'problem', 'p', 'e.clock = p.clock')
               ->where('e.clock > :last7Days')
               ->setParameter('last7Days', $last7Days)
               ->orderBy('e.clock', 'DESC')
               ->setMaxResults(25);
            
            $results = $this->db->executeQueryBuilder($qb);
            
            $history = [];
            foreach ($results as $row) {
                $host = $row['host'] ?? '';
                // Replace "Zabbix server" with "Monitor Server" for display
                if ($host === 'Zabbix server') {
                    $host = 'Monitor Server';
                }
                
                $history[] = [
                    'host' => $host,
                    'clock' => (int)($row['clock'] ?? 0),
                    'description' => (string)($row['description'] ?? ''),
                    'severity' => (int)($row['severity'] ?? 0)
                ];
            }
            
            return $history;
        } catch (\Throwable $e) {
            return [];
        }
    }
}

