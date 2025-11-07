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

        $common = new Common($this->configs);

        $ifaces = $common->getIfaces($this->mod);

        if (empty($ifaces)) {
            // Show empty state if no interfaces configured
            return '<div style="text-align: center; padding: 20px; color: #666;">No serial interfaces configured</div>';
        }

        // getIfaces returns an associative array keyed by interface name
        // Each value is an object with interface, label, threshold
        foreach ($ifaces as $iface) {
            // Handle both old format (name/alias) and new format (interface/label)
            $ifaceName = $iface['interface'] ?? $iface['name'] ?? '';
            $label = !empty($iface['label']) ? $iface['label'] : (!empty($iface['alias']) ? $iface['alias'] : $ifaceName);
            
            if (empty($ifaceName)) {
                continue;
            }
            
            $cargo .= <<<EOL
                        <div class="gauge-item">
                            <canvas id = 'gauge-serial-{$ifaceName}' class='gauge' width='175' height = '128'></canvas>
                            <div class="gauge-label">{$label}</div>
                            <div class="gauge-timestamp" id = 'lastpacket-serial-{$ifaceName}'></div>
                        </div>
        EOL;
        }

        return $cargo;
    }

 }
