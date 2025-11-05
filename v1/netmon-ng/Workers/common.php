<?php

/**
 * processes settings from KAMS Setting File and returns
 */

include '/usr/src/KAMS-Setting-file.php';

foreach ($_POST as $k => $v) {
    $$k = $v;
}


switch ($action) {

    case "getNavBar":

        $navBar = "";

        if ($MotorolaPage == "yes" && ($UDPMonitorPage == "yes" || $SerialMonitorPage == "yes" || $ZabbixPage == "yes")) {
            $navBar .= '<button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" class="active" href="../channels/index.php">Motorola-Channels</a></button>';
        }
        if ($UDPMonitorPage == "yes") {
            $navBar .= '<button style="background-color:yellow;border-radius: 6px;color:black"><a style="color:black;" href="../netmon-ng/index.html">UDP-Monitor</a></button>';
        }
        if ($SerialMonitorPage == "yes") {
            $navBar .= '<button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../serial/index.php">Serial-Monitor</a></button>';
        }
        if ($ZabbixPage == "yes") {
            $navBar .= '<button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../Monitor/index.php">Server-Monitor</a></button>';
        }

        echo $navBar;


        break;

    case "getSiteName":

        echo $SiteName;

        break;

    case "getIfaces":

        $cart = "";

        foreach ($UDPInterfaceName as $udpIface) {
            $iArray = explode("|", $udpIface);
            $ifArray[$iArray[0]]['name'] = $iArray[0];
            $ifArray[$iArray[0]]['alias'] = $iArray[1];
            $ifArray[$iArray[0]]['threshold'] = $iArray[2];
        }

        $cart = json_encode($ifArray);

        echo $cart;

        break;


    default:

        break;
}
