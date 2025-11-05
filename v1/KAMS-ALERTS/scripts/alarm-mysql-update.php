<?php
include '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');


$alarmName = $_POST["alarm_name"];
$alarmDescription = $_POST["alarm_description"];
$id = $_POST["id"];
$oid = $_POST["alarm_oid"];
$severity = $_POST["alarm_severity"];
$delete = $_POST["delete"];

if ($delete != 'on'){
$query = "UPDATE Alarms SET alarm_name='$alarmName',description='$alarmDescription',OID='$oid',severity='$severity' WHERE alarm_id=$id";
//echo $query;
$result = mysqli_query($link, $query);

if (mysqli_error($link) != ''){
	echo "<h1><center>".mysqli_error($link)."</center></h1>";
}else{
echo "<h1><center>Editing Alarm</center><h1>";
}

} else {
$query = "DELETE from Alarms WHERE alarm_id=$id";
$result = mysqli_query($link, $query);	

if (mysqli_error($link) != ''){
	echo "<h1><center>".mysqli_error($link)."</center></h1>";
}else{
echo "<h1><center>Deleting Alarm</center><h1>";
}

}


header ("Refresh:3;url=../alarms-admin.php");


?>