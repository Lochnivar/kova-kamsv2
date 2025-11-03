<?php
include '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');


$alarm_name = $_POST["alarm-name"];

$alarm_id = $_POST["alarm-description"];
$alarm_oid = $_POST["alarm-oid"];
$alarm_system = $_POST["system_type"];


$query = "Insert INTO Alarms (alarm_name,description,OID,system_type) VALUES ('$alarm_name','$alarm_id','$alarm_oid','$alarm_system')";
$result = mysqli_query($link, $query);
//echo $query;

if (mysqli_error($link) != ''){
	echo "<h1><center>".mysqli_error($link)."</center></h1>";
}else{
echo "<h1><center>Adding Alarm to the Table</center><h1>";
}

header ("Refresh:3;url=../alarms-admin.php");

?>
