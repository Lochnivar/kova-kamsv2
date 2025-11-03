<?php
include  '/var/www/html/channels/php-mysql/connection-channels.php';
date_default_timezone_set('America/New_York');


$channel_name = $_POST["channel-name"];
$channel_id = $_POST["channel-id"];
$channel_time = $_POST["channel-time"];
$time_type = $_POST["time_type"];


$query = "Insert INTO channel_data (channel_name,channel_id,timeout_number,timeout_value) VALUES ('$channel_name','$channel_id','$channel_time','$time_type')";
$result = mysqli_query($link, $query);
//echo $query;

if (mysqli_error($link) != ''){
	echo "<h1><center>".mysqli_error($link)."</center></h1>";
}else{
echo "<h1><center>Adding Channel to the Table</center><h1>";
}

header ("Refresh:3;url=../admin.php");

?>


