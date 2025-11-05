<?php
include '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');


$serverID = $_POST["enable"];



$EnableQuery = "UPDATE Servers SET silenced = '0', silenced_all ='0' WHERE id = '$serverID'";
$EnableResult = mysqli_query($link, $EnableQuery);
$EnableSilQuery = "UPDATE Servers SET silenced_alarms = '' WHERE id = '$serverID'";
$EnableSilResult = mysqli_query($link, $EnableSilQuery);

//echo $EmailQuery;

if (mysqli_error($link) != ''){
	echo "<h1><center>".mysqli_error($link)."</center></h1>";
}else{
echo "<h1><center>ENABLING Alerts</center><h1>";
}

header ("Refresh:1;url=../index.php");
?>
