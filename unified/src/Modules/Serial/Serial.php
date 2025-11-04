<?php

namespace Kova\Kams\Unified\Modules\Serial;

use Kova\Kams\Unified\Modules\Common\Common;


class Serial
{

    public $cargo;
    public $configs;
    public $dbConn;
    private $mod = "serial";

    public function __construct($configs)
    {
        $this->configs = $configs;
     }

    public function homeSerial() {}

    public function serialPage() {}

    public function serialSum()
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
