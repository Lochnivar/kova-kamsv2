<?php

namespace Kova\Kams\Unified\Modules\Home;

use Kova\Kams\Unified\Modules\Motorola\Moto;
use Kova\Kams\Unified\Modules\SysHealth\SysHealth;
use Kova\Kams\Unified\Modules\UDP\Udp;
use Kova\Kams\Unified\Modules\Serial;
use Kova\Kams\Unified\Modules\Serial\Serial as SerialSerial;

class Home
{

    public $configs;

    public function __construct($configs)
    {
        $this->configs = $configs;
    }

    public function buildHome(): string
    {
        
        //var_dump($this->configs);
        /**
         * Fill Home Content out
         */

        $mod = new SysHealth();
     
        $sysHealthSum = $mod->getSystHealthSum();

        unset($mod);
        $mod = new Udp($this->configs);

        $udpSum = $mod->udpSum();

        unset($mod);

        $mod = new SerialSerial($this->configs);

        $serSum = $mod->serialSum();

        unset($mod);

        $mod = new Moto($this->configs);

        $motoSum = $mod->motoSum();

        $vis = [];

        $vis['moto'] = ($this->configs['MotorolaPage'] == strtolower("yes")) ? "block" : "none";
        $vis['udp'] = ($this->configs['UDPMonitorPage'] == strtolower("yes")) ? "block" : "none";
        $vis['serial'] = ($this->configs['SerialMonitorPage'] == strtolower("yes")) ? "block" : "none";
         


        $homeContent =  <<< EOF

        <table class='table table-bordered w-100' style="white-space:nowrap;">
        <tbody align='center'>
        <tr>
        <td colspan = '3'>
            <a href = '/Monitor/index.php'><h1>System Status</h1></a>
        </td></tr>
                <tr>
        <td colspan = '3'>
    <div id="sysHealthSum">
            {$sysHealthSum}
    </div>
    </td>
    </tr>
    <tr>
    <td id = "udpEnabled" style="display: {$vis['udp']};">
    <a href = "/netmon-ng/index.html">
        <h2>UDP Status</h2>
    </a>
    </td>
    <td id = "serialEnabled" style="display: {$vis['serial']};">
    <a href = "/serial/index.php">
        <h2>Serial Status</h2>
    </a>
    </td>
    <td id = "motoEnabled" style="display: {$vis['moto']};">
    <a href = "/channels/index.php">
        <h2>Motorola Status</h2>
    </a>
    </td>
    </tr>
    <tr>
        <td id="udpSum" style="display: {$vis['udp']};">
        {$udpSum}
        </td>
        <td id="serialSum" style="display: {$vis['serial']};">
        {$serSum}
        </td>
        <td id="motoSum" style="display: {$vis['moto']};">
        {$motoSum}
        </td>
        </tr>
        </tbody>
        </table>


EOF;





        return $homeContent;
    }
}
