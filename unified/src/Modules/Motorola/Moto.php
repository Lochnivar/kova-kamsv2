<?php

namespace Kova\Kams\Unified\Modules\Motorola;

use Kova\Kams\Unified\Modules\Common\Common;


class Moto
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

    public function motoPage() {}

    public function motoSum()
    {
        $cargo = "";


        $cargo = <<<EOL
<table class = 'moto-summary-table'><tbody>
        <tr>
            <td>Active Channels</td>
            <td id = "motoActive">0</td>
        </tr>
        <tr>
            <td>Channel Warnings</td>
            <td id = "motoWarnings">0</td>
        </tr>
        <tr>
            <td>Not Monitored</td>
            <td id = "motoNM">0</td>
        </tr></tbody></table>
EOL;


        return $cargo;
    }
}
