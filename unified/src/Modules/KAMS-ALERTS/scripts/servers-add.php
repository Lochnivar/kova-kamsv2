<?php
include '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');


$server_name = $_POST["server-name"];
$server_system = $_POST["system_type"];
$server_id = $_POST["server-ip"];


$query = "Insert INTO Servers (name,server_ip,system_type) VALUES ('$server_name','$server_id','$server_system')";
//echo $query;
$result = mysqli_query($link, $query);

if (mysqli_error($link) != ''){
	echo "<h1><center>".mysqli_error($link)."</center></h1>";
}else{
echo "<h1><center>Adding server to the Table</center><h1>";
}

header ("Refresh:3;url=../index.php");

?>