<?php

include '/usr/src/KAMS-Setting-file.php';

foreach ($UDPInterfaceName as $udpIface) {
    $iArray = explode("|", $udpIface);
    $ifArray[$iArray[0]]['name'] = $iArray[0];
    $ifArray[$iArray[0]]['alias'] = $iArray[1];
}

$cargo = "<table class='table'>";


foreach ($ifArray as $iface) {
    $cargo .= <<<EOL
           
<tr>
    <td>
        <table border ='1' class ='table table-sm table-striped'>
            <tr>
                <th colspan='5'>
                <a class = "ifaceLink" iface="{$iface['name']}" alias="{$iface['alias']}">{$iface['alias']}</a>
                </th>
            </tr>
            <tr>
                <td>One Hour</td><td><span id = "{$iface['name']}-hour"></span> ppm</td>
                <td rowspan = '4' class='w-25'>
                    <table id='table-gauge-{$iface['name']}' class=''>
                    <tr>
<td class='text-center'><span id = 'gauge-thresh-{$iface['name']}'></span>
</td>
</tr>
<tr>
                    <td>
                <canvas id = 'gauge-{$iface['name']}' class='gauge'></canvas>
                    </td>
                    </tr>
                    <tr>
                    <td class='text-center'>
                    <span id ='gauge-number-{$iface['name']}'></span>                    
                    </td>
                    </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>Thirty Minutes</td>
                <td><span id = "{$iface['name']}-thirty"></span> ppm</td>
            </tr>
            <tr>
                <td>Ten Minutes</td>
                <td><span id = "{$iface['name']}-ten"></span> ppm</td>
            </tr>
            <tr>
                <td>Last Packet Time</td>
                <td><span id = "{$iface['name']}-lastTime"></span></td>
            </tr>
        </table>
    </td>
    
    </tr>
EOL;
}

$cargo .= "</tr></table>";

echo $cargo;
