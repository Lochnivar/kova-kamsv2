<?php

include '/usr/src/KAMS-Setting-file.php';

if ($MotorolaPage == "yes" && ($UDPMonitorPage == "yes" || $SerialMonitorPage == "yes" || $ZabbixPage == "yes")) {
    $response .= '<button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" class="active" href="../channels/index.php">Motorola-Channels</a></button>';
    } 
    if ($UDPMonitorPage == "yes") { 
        $response .= '<button style="background-color:yellow;border-radius: 6px;color:black"><a style="color:black;" href="../netmon/index-unified.php">UDP-Monitor</a></button>';
    } 
     if ($SerialMonitorPage == "yes") { 
        $response .= '<button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../serial/index.php">Serial-Monitor</a></button>';
     } 
    if ($ZabbixPage == "yes") { 
        $respnse .= '<button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../Monitor/index.php">Server-Monitor</a></button>';
    } 

echo $response;