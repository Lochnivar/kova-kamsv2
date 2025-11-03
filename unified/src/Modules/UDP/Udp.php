<?php

namespace Kova\Unified\Modules\UDP;

use Kova\Unified\Modules\Common\Common;


class Udp
{

    public $cargo;
    public $configs;
    public $dbConn;
    public $mod = "udp";
    public function __construct($configs)
    {
        $this->configs = $configs;
     }

    public function homeUDP() {}

    public function udpPage() {}

    public function udpSum()
    {
        $cargo = "";

        $cargo = "<table class = 'table'><tbody align='center'><tr>";

        $common = new Common($this->configs);

        $ifaces = $common->getIfaces($this->mod);

        foreach ($ifaces as $iface) {
            $cargo .= <<<EOL
                        <td>
                            <canvas id = 'gauge-{$iface['name']}' class='gauge' width='175' height = '128'></canvas>
                            <br />{$iface['alias']}
                            <br /><span id = 'lastpacket-{$iface['name']}'></span>
                        </td>
                  
        EOL;
        }

        $cargo .= "</tr></tbody></table>";

        return $cargo;
    }

 }
