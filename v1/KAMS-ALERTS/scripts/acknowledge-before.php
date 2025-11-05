<?php
$serverID = $_POST["ip"];
$ackUser= $_POST["user"];
$now = time();
include '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');






$AckQuery = "UPDATE Servers SET acknowledge = '1',ackName='$ackUser',ackTime='$now' WHERE server_ip = '$serverID'";
$AckResult = mysqli_query($link, $AckQuery);


//echo $EmailQuery;

if (mysqli_error($link) != ''){
	echo "<h1><center>".mysqli_error($link)."</center></h1>";
}else{
echo "<h1><center>Acknowledging Alerts</center><h1>";
}

header ("Refresh:1;url=../index.php");











?>