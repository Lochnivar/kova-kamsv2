<?php

namespace Kova\Unified\Modules\Common;

use IntelliTrend\Zabbix\ZabbixApi;
use Exception;
use Kova\Unified\Modules\Common\Config;

class Communicator
{

    public $cargo;
    public $config;

    public function __construct()
    {
        $configs = new Config;
        $this->config = $configs->config;
    }


    public function zabbixComm($method, $argArray)
    {

        $zmeth = $this->zabbixMethods($method);



        $result = $this->callZabbix($zmeth, $argArray);

        return $result;
    }


    public function getZBXHosts()
    {

        $argArray = [
            "countOutput" => false,
            "output" => ["hostid", "name"]
        ];

        $hosts =  $this->zabbixComm("host", $argArray);

        return $hosts;
    }

    public function getZBXHostHealth($hosts)
    {

        $zmeth = $this->zabbixMethods("item");

        $argArray = [
            "output" => ["hostid", "name", "lastvalue"],
            "filter" => [],
            "search" => ["key_" => "agent.ping"],
            "hostids" => $hosts
        ];

        $health = $this->zabbixComm("item", $argArray);

        return $health;
        //       var_dump($health);

    }

    private function callZabbix($zmeth, $argArray)
    {

        $zbx = new ZabbixApi();
        $options = array('sslVerifyPeer' => false, 'sslVerifyHost' => false);

        try {
            $zbx->login('http://' . $this->config['ZabbixIP'] . '/zabbix', 'Admin', 'zabbix', $options);
            $cargo = $zbx->call($zmeth, $argArray);
        } catch (Exception $e) {
            echo "Error in Zabbix " . $e->getMessage() . PHP_EOL;
            exit;
        }

        return $cargo;
    }

    private function zabbixMethods($method)
    {
        $methodArray = [
            'host' => 'host.get',
            'item' => 'item.get'
        ];

        return $methodArray[$method];
    }
}
