<?php

namespace Kova\Kams\Core;

use IntelliTrend\Zabbix\ZabbixApi;
use Exception;
use Kova\Kams\Common\Config;

/**
 * Consolidated Communicator class with all communication functionality
 * from both Unified (Zabbix) and KCM (SSH) modules.
 */
class Communicator
{
    public $config;
    public $common;

    public function __construct(?Config $config = null)
    {
        if ($config === null) {
            $config = new Config();
        }
        $this->config = is_object($config) ? $config->config : $config;
        $this->common = new Common($config);
    }

    // ========== Unified Module Methods (Zabbix) ==========

    /**
     * Execute Zabbix API call.
     * 
     * @param string $method Method name (host, item)
     * @param array $argArray Arguments for the API call
     * @return mixed API response
     */
    public function zabbixComm(string $method, array $argArray)
    {
        $zmeth = $this->zabbixMethods($method);
        return $this->callZabbix($zmeth, $argArray);
    }

    /**
     * Get Zabbix hosts.
     * 
     * @return array Array of hosts with hostid and name
     */
    public function getZBXHosts(): array
    {
        try {
            $argArray = [
                "countOutput" => false,
                "output" => ["hostid", "name"]
            ];

            return $this->zabbixComm("host", $argArray);
        } catch (Exception $e) {
            // Return empty array if Zabbix is not configured or unavailable
            error_log("Failed to get Zabbix hosts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get Zabbix host health status.
     * 
     * @param string|int $hosts Host ID
     * @return array Array of host health items
     */
    public function getZBXHostHealth($hosts): array
    {
        try {
            $argArray = [
                "output" => ["hostid", "name", "lastvalue"],
                "filter" => [],
                "search" => ["key_" => "agent.ping"],
                "hostids" => $hosts
            ];

            return $this->zabbixComm("item", $argArray);
        } catch (Exception $e) {
            // Return empty array if Zabbix is not configured or unavailable
            error_log("Failed to get Zabbix host health: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Call Zabbix API.
     * 
     * @param string $zmeth Zabbix API method
     * @param array $argArray Arguments
     * @return mixed API response
     * @throws Exception If API call fails or config is missing
     */
    private function callZabbix(string $zmeth, array $argArray)
    {
        $zabbixIP = $this->config['ZabbixIP'] ?? null;
        $zabbixUser = $this->config['ZabbixUser'] ?? 'Admin';
        $zabbixPass = $this->config['ZabbixPass'] ?? 'zabbix';
        
        if (empty($zabbixIP)) {
            throw new Exception('ZabbixIP is not configured in settings. Please set ZabbixIP in the admin settings.');
        }
        
        $zbx = new ZabbixApi();
        $options = ['sslVerifyPeer' => false, 'sslVerifyHost' => false];

        try {
            $zbx->login('http://' . $zabbixIP . '/zabbix', $zabbixUser, $zabbixPass, $options);
            $cargo = $zbx->call($zmeth, $argArray);
        } catch (Exception $e) {
            error_log("Error in Zabbix: " . $e->getMessage());
            throw $e;
        }

        return $cargo;
    }

    /**
     * Map method names to Zabbix API methods.
     * 
     * @param string $method Method name
     * @return string Zabbix API method
     */
    private function zabbixMethods(string $method): string
    {
        $methodArray = [
            'host' => 'host.get',
            'item' => 'item.get'
        ];

        return $methodArray[$method] ?? '';
    }

    // ========== KCM Module Methods (SSH) ==========

    /**
     * Send communication message via SSH.
     * 
     * @param string $msgLine Message line to send
     * @return string Message line that was sent
     */
    public function SendComms(string $msgLine): string
    {
        echo "comm Sent" . PHP_EOL;

        // SSH communication disabled by default - uncomment to enable
        /*
        $primaryHost = $this->config['sshPrimaryHost'] ?? '40.142.26.130';
        $backupHost = $this->config['sshBackupHost'] ?? '41.143.128.194';
        $messagePath = $this->config['messagePath'] ?? '/usr/KAMS/kams-messages.csv';
        
        $output = system("ssh -t " . $this->config['sshName'] . "@{$primaryHost} '/usr/bin/echo " . $msgLine . "  >> {$messagePath}'", $return_var);
        echo $return_var . " - the value returned";

        if ($return_var !== 0) {
            $outputBK = system("ssh -t " . $this->config['sshName'] . "@{$backupHost} '/usr/bin/echo " . $msgLine . "  >> {$messagePath}'", $returnBK_var);
            echo $returnBK_var . " - the value returned";
        }
        */

        return $msgLine;
    }

    /**
     * Send report via SSH.
     * 
     * @param array $report Report data to send
     * @return void
     */
    public function sendReport(array $report): void
    {
        $cargo = base64_encode(json_encode($report));

        var_dump($cargo);
        $endpoint = $this->config['kcmEndpoint'] ?? kova_path('kcm/endpoint.php');
        $sshHost = $this->config['sshHost'] ?? '40.143.128.194';
        $cmd = "ssh -t " . $this->config['sshName'] . "@{$sshHost} '/usr/bin/php {$endpoint}' $cargo";
        system($cmd, $responseMain);
    }
}

