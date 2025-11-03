<?php

namespace Kova\Unified\Modules\Common;

class Common
{

    public $cargo;
    public $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getNavBar()
    {

        $navBar = "";

        if ($this->config['MotorolaPage'] == "yes" && ($this->config['UDPMonitorPage'] == "yes" || $this->config['SerialMonitorPage'] == "yes" || $this->config['ZabbixPage'] == "yes")) {
            $navBar .= '<button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" class="active" href="../channels/index.php">Motorola-Channels</a></button>';
        }
        if ($this->config['UDPMonitorPage'] == "yes") {
            $navBar .= '<button style="background-color:yellow;border-radius: 6px;color:black"><a style="color:black;" href="../netmon-ng/index.html">UDP-Monitor</a></button>';
        }
        if ($this->config['SerialMonitorPage'] == "yes") {
            $navBar .= '<button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../serial/index.php">Serial-Monitor</a></button>';
        }
        if ($this->config['ZabbixPage'] == "yes") {
            $navBar .= '<button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../Monitor/index.php">Server-Monitor</a></button>';
        }

        echo $navBar;
    }

    public function getSiteName()
    {

        echo $this->config['SiteName'];
    }

    public function getIfaces($mod)
    {

        $cargo = "";

        //  var_dump($this->config);

        $configMod = "";
        switch ($mod) {
            case "udp":

                $configMod = "UDPInterfaceName";
                break;

            case "serial":
                $configMod = "SerialInterfaceName";
                break;

            case "moto":
                $configMod = "MotorolaInterfaceName";
                break;

            case "zbx":
                $configMod = "zbx";
                break;

            default:

                break;
        }


        if (is_array($this->config[$configMod])) {
            $ifaces = $this->config[$configMod];
        } else {
            $ifaces = explode("~", $this->config[$configMod]);
        }
        

        foreach ($ifaces as $iface) {
           
            $iArray = explode("|", $iface);
            $ifArray[$iArray[0]]['name'] = $iArray[0];
            $ifArray[$iArray[0]]['alias'] = $iArray[1];
            $ifArray[$iArray[0]]['threshold'] = $iArray[2];
        }

        //var_dump($ifArray);

        // $cargo = json_encode($ifArray);

        return $ifArray;
    }
}
