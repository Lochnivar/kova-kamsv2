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
        <style>
        .home-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .home-section {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .home-section h1, .home-section h2 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
        }
        .home-section h1 a, .home-section h2 a {
            color: #007bff;
            text-decoration: none;
        }
        .home-section h1 a:hover, .home-section h2 a:hover {
            text-decoration: underline;
        }
        .system-status-section {
            text-align: center;
        }
        #sysHealthSum {
            margin-top: 15px;
        }
        #sysHealthSum table {
            margin: 0 auto;
            border-collapse: separate;
            border-spacing: 30px 10px;
            border: none;
        }
        #sysHealthSum table td {
            padding: 15px;
            vertical-align: top;
            border: none;
            text-align: center;
        }
        #sysHealthSum table svg {
            display: block;
            margin: 0 auto 10px;
            width: 64px;
            height: 64px;
        }
        #sysHealthSum table td br {
            display: block;
            margin-top: 5px;
        }
        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .module-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            min-height: 200px;
        }
        .module-card h2 {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .gauge-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }
        .gauge-item {
            text-align: center;
            padding: 10px;
        }
        .gauge-item canvas {
            display: block;
            margin: 0 auto 10px;
        }
        .gauge-item .gauge-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        .gauge-item .gauge-timestamp {
            font-size: 12px;
            color: #666;
        }
        .moto-summary-table {
            width: 100%;
            margin-top: 15px;
        }
        .moto-summary-table td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        .moto-summary-table td:first-child {
            font-weight: 600;
            color: #666;
            width: 60%;
        }
        .moto-summary-table td:last-child {
            font-weight: bold;
            color: #333;
            text-align: right;
        }
        </style>

        <div class="home-container">
            <!-- System Status Section -->
            <div class="home-section system-status-section">
                <h1><a href="#" onclick="getSystemMonitor(); return false;">System Status</a></h1>
                <div id="sysHealthSum">
                    {$sysHealthSum}
                </div>
            </div>

            <!-- Module Grid -->
            <div class="module-grid">
                <!-- UDP Status Card -->
                <div class="module-card" id="udpCard" style="display: {$vis['udp']};">
                    <h2><a href="#" onclick="getUdpMonitor(); return false;">UDP Status</a></h2>
                    <div id="udpSum" class="gauge-container">
                        {$udpSum}
                    </div>
                </div>

                <!-- Serial Status Card -->
                <div class="module-card" id="serialCard" style="display: {$vis['serial']};">
                    <h2><a href="#" onclick="getSerialMonitor(); return false;">Serial Status</a></h2>
                    <div id="serialSum" class="gauge-container">
                        {$serSum}
                    </div>
                </div>

                <!-- Motorola Status Card -->
                <div class="module-card" id="motoCard" style="display: {$vis['moto']};">
                    <h2><a href="#" onclick="getMotorolaChannels(); return false;">Motorola Status</a></h2>
                    <div id="motoSum">
                        {$motoSum}
                    </div>
                </div>
            </div>
        </div>

EOF;





        return $homeContent;
    }
}
