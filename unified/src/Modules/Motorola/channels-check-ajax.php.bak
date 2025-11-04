<?php
include '/usr/src/KAMS-Setting-file.php';
date_default_timezone_set($TimeZone);
include  '/var/www/html/channels/php-mysql/connection-channels.php';
//$timeMinus12 = time() - 43200;

//Variables CHange extension length here
$showActiveCallName = "AARF";


//Button Styles
$ActiveCallButton = "style='margin:5px; width:200px; height:75px; font-size: 100%;line-height: 1.2;'>";
$tierOneButton = "style='margin:5px; width:155px; height:75px; font-size: 90%;line-height: 1.2;'>";
$tierTwoButton = "style='margin:5px; width:155px; height:65px; font-size: 65%;line-height: 1.2;'>";
$tierThreeButton = "style='margin:5px; width:145px; height:55px; font-size: 70%;line-height: 1.2;'>";


		echo "<hr>";
		echo "<h3><center style='color:red;'>Warnings</center></h3>";
		echo "<hr>";


$channelsGet = "SELECT * from channel_data WHERE timeout_number <> '0' ORDER BY last_activity DESC ,channel_id";
$result = mysqli_query($link, $channelsGet);
$issues = 0;
while ($rowOne = mysqli_fetch_array($result)) {
	
	if($rowOne[4] == '1'){
	$TimeValue = substr_replace($rowOne[5] ,"",-1);
	} else {
	$TimeValue = $rowOne[5];	
	}
	
	if($rowOne[5] == "Hours"){
	$timeCheck = time() - ($rowOne[4] * 3600);
	}
	if($rowOne[5] == "Days"){
	$timeCheck = time() - ($rowOne[4] * 86400);
	}
	if($rowOne[5] == "Weeks"){
	$timeCheck = time() - ($rowOne[4] * 604800);
	}
					if ($rowOne[3] < $timeCheck && $rowOne[3] != ""){ 
					echo "<button type='button' class='btn btn-warning btn-lg' ".$tierOneButton.$rowOne[2]."<br>".date('Y-m-d h:i:s', $rowOne[3])."<br>Timeout ".$rowOne[4]." ".$TimeValue."<br></button>";
					$issues++;
					

					}
					if ($rowOne[3] == ""){ 
					echo "<button type='button' class='btn btn-danger btn-lg' ".$tierOneButton.$rowOne[2]."<br>Timeout ".$rowOne[4]." ".$TimeValue."<br>NO ACTIVITY</button>";
					$issues++;
					}		
				
}

if($issues == '0'){
	echo "<h3 style='color:green;'><center>No Issues System is OK</center></h3>";
}



echo "<hr>";
echo "<center>Active Channels</center></h2>";
echo "<hr>";
$channelsGet = "SELECT * from channel_data WHERE timeout_number <> '0' ORDER BY last_activity DESC ,channel_id";
$result = mysqli_query($link, $channelsGet);
while ($rowOne = mysqli_fetch_array($result)) {
	
	if($rowOne[4] == '1'){
	$TimeValue = substr_replace($rowOne[5] ,"",-1);
	} else {
	$TimeValue = $rowOne[5];	
	}
	
	if($rowOne[5] == "Hours"){
	$timeCheck = time() - ($rowOne[4] * 3600);
	}
	if($rowOne[5] == "Days"){
	$timeCheck = time() - ($rowOne[4] * 86400);
	}
	if($rowOne[5] == "Weeks"){
	$timeCheck = time() - ($rowOne[4] * 604800);
	}
	
					if ($rowOne[3] > $timeCheck){ 
					echo "<button type='button' class='btn btn-success btn-lg' ".$tierOneButton.$rowOne[2]."<br>".date('Y-m-d h:i:s', $rowOne[3])."<br>Timeout ".$rowOne[4]." ".$TimeValue."<br></button>";
					}
					
					
}

echo "<hr>";
echo "<center>Unmonitored Channels</center></h2>";
echo "<hr>";
$channelsGet = "SELECT * from channel_data WHERE timeout_number = '0' ORDER BY last_activity DESC ,channel_id";
$result = mysqli_query($link, $channelsGet);
while ($rowOne = mysqli_fetch_array($result)) { 
					echo "<button type='button' class='btn btn-secondary btn-lg' ".$tierOneButton.$rowOne[2]."<br>".date('Y-m-d h:i:s', $rowOne[3])."</button>";
}

?>


