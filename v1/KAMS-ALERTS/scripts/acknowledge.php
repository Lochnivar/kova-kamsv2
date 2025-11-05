<?php
$serverID = $_POST["ip"];
$ackUser= $_POST["user"];
$now = time();
include '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');


$AckTimeQuery = "UPDATE Servers SET acknowledge = '1',ackName='$ackUser',ackTime='$now' WHERE server_ip = '$serverID'";
$AckTimeMSQL = mysqli_query($link, $AckTimeQuery);
$AckTimeResult = mysqli_fetch_array($AckTimeMSQL);



$AckQuery = "UPDATE Servers SET acknowledge = '1',ackName='$ackUser',ackTime='$now' WHERE server_ip = '$serverID'";
$AckResult = mysqli_query($link, $AckQuery);

$AckHistoryQuery = "UPDATE History SET ackName='$ackUser',ackTime='$now' WHERE server_id = '$serverID'  ORDER BY id DESC LIMIT 1";
$AckHistoryResult = mysqli_query($link, $AckHistoryQuery);

$AckHistoryAllQuery = "UPDATE History SET ackName='-',ackTime='$now' WHERE server_id = '$serverID' AND ackTime IS NULL";
$AckHistoryAllResult = mysqli_query($link, $AckHistoryAllQuery);

//echo $EmailQuery;

if (mysqli_error($link) != ''){
	echo "<h1><center>".mysqli_error($link)."</center></h1>";
}else{
echo "<h1><center>Acknowledging Alerts</center><h1>";
}

header ("Refresh:1;url=../index.php");











?>
