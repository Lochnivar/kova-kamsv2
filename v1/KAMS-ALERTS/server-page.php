<?php
include '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');


$ip = $_POST["ip"];

		

		//echo $AlarmQuery;

?>
<body style="margin:5;padding:5">
<h2>Server - <?php echo $_POST['server_name'];?></h2>


<?php
$AlarmQuery = "SELECT * FROM History where server_id='$ip'";
		$AlarmResult = mysqli_query($link, $AlarmQuery);
		echo "Total Alarms - ".mysqli_num_rows($AlarmResult);
		echo "<hr>";
		while($AlarmRow = mysqli_fetch_array($AlarmResult)) {
		echo $AlarmRow[event_time]." - ".$AlarmRow[event_alarm];
		echo "<br>";
		}
		
?>
