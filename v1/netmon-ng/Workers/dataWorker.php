<?php

/**
 * The purpose of this file is to fetch the data needed to fuel the charts,
 * graphs, and gauges on the front page.
 */

include '/usr/src/KAMS-Setting-file.php';

foreach ($_POST as $k => $v) {
    $$k = $v;
}

switch ($action) {

    case "getUDPAvgs":

        if (isset($iface) && isset($alias)) {
            $ifArray[$iface] = ['name' => $iface, 'alias' => $alias];
        } else {
            foreach ($UDPInterfaceName as $udpIface) {
                $iArray = explode("|", $udpIface);
                $ifArray[$iArray[0]]['name'] = $iArray[0];
                $ifArray[$iArray[0]]['alias'] = $iArray[1];
            }
        }

        $udpAvgs = getUDPAvgs($ifArray);
        $cargo = json_encode($udpAvgs);


        break;

    case "getIfaceChartData":

        $cargo = getChartData($iface);

        break;
}

echo $cargo;


function getChartData($iface)
{

    $udplink = getDBConn();

    $CheckALLData = "select * from data_unified WHERE iface = '" . $iface . "'  ORDER BY epoch DESC  LIMIT 90";
    $CheckUDPQuery = mysqli_query($udplink, $CheckALLData);
    $data = [];
    $totalUDP = '0';
    for ($i = 0; $i  < mysqli_num_rows($CheckUDPQuery); $i++) {
        $CurrentUDPRow =  mysqli_fetch_array($CheckUDPQuery);
        $data[$i]["x"] =  date('Y-m-d H:i:s', $CurrentUDPRow['epoch']);
        $data[$i]["y"] = $CurrentUDPRow['udp_packets'];
        $UDProws = $i + 1;
        $totalUDP = $totalUDP + $CurrentUDPRow['udp_packets'];
    }

    $cargo = json_encode($data);

    return $cargo;
}

function getUDPAvgs($ifaces)
{
    $udpArray = [];

    foreach ($ifaces as $k => $v) {
        $udpArray[$k]['hour'] = getHourUDPData($k);
        $udpArray[$k]['thirty'] = getThirtyUDPData($k);
        $udpArray[$k]['ten'] = getTenUDPData($k);
        $udpArray[$k]['lastTime'] = getLastPacketStamp($k);
    }


    return $udpArray;
}

function getHourUDPData($iface)
{

    $dbConn = getDBConn();

    $totalUDPHour = 0;

    $CheckUDPDataHour = "select udp_packets from data_unified WHERE iface = '" . $iface . "' ORDER BY epoch DESC LIMIT 60";
    $CheckUDPQueryHour = mysqli_query($dbConn, $CheckUDPDataHour);
    for ($i = 0; $i  < mysqli_num_rows($CheckUDPQueryHour); $i++) {
        $CurrentUDPRowHour =  mysqli_fetch_array($CheckUDPQueryHour);
        $totalUDPHour += $CurrentUDPRowHour['udp_packets'];
        $TotalRows = $i;
    }

    unset($dbConn);

    return round(($totalUDPHour / $TotalRows), 2);
}

function getThirtyUDPData($iface)
{
    $dbConn = getDBConn();

    $totalUDPThirty = 0;

    $CheckUDPDataThirty = "select udp_packets from data_unified WHERE iface = '" . $iface . "' ORDER BY epoch DESC LIMIT 30";
    $CheckUDPQueryThirty = mysqli_query($dbConn, $CheckUDPDataThirty);
    for ($i = 0; $i  < mysqli_num_rows($CheckUDPQueryThirty); $i++) {
        $CurrentUDPRowThirty =  mysqli_fetch_array($CheckUDPQueryThirty);
        $totalUDPThirty += $CurrentUDPRowThirty['udp_packets'];
        $TotalRows = $i;
    }

    unset($dbConn);

    return round(($totalUDPThirty / $TotalRows), 2);
}

function getTenUDPData($iface)
{
    $dbConn = getDBConn();

    $totalUDPTen = 0;

    $CheckUDPDataTen = "select udp_packets from data_unified WHERE iface = '" . $iface . "' ORDER BY epoch DESC LIMIT 10";
    $CheckUDPQueryTen = mysqli_query($dbConn, $CheckUDPDataTen);
    for ($i = 0; $i  < mysqli_num_rows($CheckUDPQueryTen); $i++) {
        $CurrentUDPRowTen =  mysqli_fetch_array($CheckUDPQueryTen);
        $totalUDPTen += $CurrentUDPRowTen['udp_packets'];
        $TotalRows = $i;
    }

    unset($dbConn);

    return round(($totalUDPTen / $TotalRows), 2);
}

function getLastPacketStamp($iface)
{
    $dbConn = getDBConn();

    $sql = "select MAX(tstamp) from data_unified WHERE iface = '" . $iface . "'";
    $result = mysqli_query($dbConn, $sql);

    $lasttime = mysqli_fetch_array($result);

    unset($dbConn);

    $dt = new DateTime();
    $dt->setTimeStamp($lasttime[0]);


    return $dt->format("Y-m-d H:i:s");
}

function getDBConn()
{
    $udplink = mysqli_connect("localhost", "webpage", "K0v@W3b", "Network_Mon");
    if (mysqli_connect_error()) {
        echo mysqli_error($udplink);
        die("Database Connection Error");
    }

    return $udplink;
}
