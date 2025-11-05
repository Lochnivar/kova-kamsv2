<?php
include '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');


$ip = $_POST['ip'];
$epoch = time();
$last_day = $epoch - 86400;
$last_week = $epoch - 604800;
$last_month = $epoch - 2419200;
$six_months = $epoch - 15724800;
//Get Monitored Alarms		
$MonitoredQuery = "SELECT monitored_alarms FROM Servers where server_ip='$ip'";
$MonitoredResult = mysqli_query($link, $MonitoredQuery);
$MonitoredRow = mysqli_fetch_array($MonitoredResult);
$Alarms = (explode(',',$MonitoredRow['monitored_alarms']));

//echo $ip;
header ("Refresh:300;url=./index.php");
?>
<html>
<head>

</head> 

<body style="margin:5;padding:5;background-color:#F2F2F2;">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>

<h2>Server - <?php echo $_POST['server_name'];?> - Alarm History Last 30 Days</h2>

<div>
<button type=“button” style="margin:10px;background-color:#372E76;color:white;border-style:solid;padding:5px;border-radius:7px;font-family:Bedrock;float:left;"><a href='index.php'>Main Page</a></button>
<form  action="./Server-Search/index.php" method="post">
<button type="submit" style="margin:10px;background-color:#372E76;color:white;border-style:solid;padding:5px;border-radius:7px;font-family:Bedrock;float:left;">Search Page</button>
<input type="hidden" name="ip" value="<?php echo $ip; ?>" />
</form>
</div>
<br>


<table class="table table-striped">
  <thead>
    <tr>
      <th scope="col">Alarm ID</th>
	  <th scope="col">Description</th>
      <th scope="col">24 Hours</th>
      <th scope="col">7 Days</th>
	  <th scope="col">30 Days</th>
    </tr>
  </thead>
  <tbody>
    

 
    <?php
foreach($Alarms as $value){
	if ($value != ''){
		$alertClean = rtrim($value,"-");
		
		
		$AlarmQuery = "SELECT * FROM History where server_id='$ip' AND event_alarm = '$alertClean'";
		$AlarmResult = mysqli_query($link, $AlarmQuery);
		
		//Check to See if Alarm was in last month to display
		$AlarmMonthQuery = "SELECT * FROM History where server_id='$ip' AND event_alarm = '$alertClean' and event_time >= '$last_month'";		
		$AlarmMonthResult = mysqli_query($link, $AlarmMonthQuery);
		
		if (mysqli_num_rows($AlarmMonthResult) > 0){
		echo "<tr>";
		echo "<td>".rtrim($value,"-")."</td>";
		
		//Alarm Description Row
		//echo $alertClean;
		$DescriptionQuery = "SELECT * FROM Alarms WHERE alarm_name = '$alertClean'";
		$DescriptionResult = mysqli_query($link, $DescriptionQuery);
		$DescriptionRow = mysqli_fetch_array($DescriptionResult);		
		echo "<td>".$DescriptionRow['description']."</td>";
		//echo $DescriptionResult['description'];
		
		
		
			
		//Total Number of Alarms Row
		//echo "<td>".mysqli_num_rows($AlarmResult)."</td>";
		
		//Total for last 24
		echo "<td>";
		$AlarmLastDayQuery = "SELECT * FROM History where server_id='$ip' AND event_alarm = '$alertClean' and event_time >= '$last_day'";
		$AlarmLastDayResult = mysqli_query($link, $AlarmLastDayQuery);
		echo mysqli_num_rows($AlarmLastDayResult);
		echo "</td>";
		
		
		//Total For Last Week
		echo "<td>";
		$AlarmSevenQuery = "SELECT * FROM History where server_id='$ip' AND event_alarm = '$alertClean' and event_time >= '$last_week'";
		$AlarmSevenResult = mysqli_query($link, $AlarmSevenQuery);
		echo mysqli_num_rows($AlarmSevenResult);
		echo "</td>";
		echo "<td>";
		
		//Total for Last Month

		//echo $AlarmMonthQuery;
		echo mysqli_num_rows($AlarmMonthResult);
		echo "</td>";
		
		echo "</tr>";
			}
		
		}
    }	
?>
	   
  </tbody>
</table>
</body>
</html>  

