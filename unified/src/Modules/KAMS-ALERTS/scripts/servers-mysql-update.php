<?php
include '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');
$epoch = time();
$readable_time = date("Y-m-d h:i:s a",$epoch);


$serverName = $_POST["server_name"];
$serverDescription = $_POST["server_ip"];
$serverid = $_POST["id"];
$delete = $_POST["delete"];
$SilenceServer = $_POST["silence"];
$Emails = $_POST["email"];
$server_system = $_POST["system_type"];

//Deal with the Server Type
$TypeQuery = "UPDATE Servers SET system_type='$server_system' WHERE id=$serverid";
$TypeResult = mysqli_query($link, $TypeQuery);


//Deal with silencing Alarms
if ($SilenceServer == 1){
	$SilenceQuery = "UPDATE Servers SET silenced = '$readable_time', silenced_all = '1' WHERE id=$serverid";
	$SilenceResult = mysqli_query($link, $SilenceQuery);
	} 
	


//POST initial Alarms Monitored
$AlarmQuery = "Select monitored_alarms,silenced_alarms,silenced from Servers WHERE id=$serverid";
$AlarmResult = mysqli_query($link, $AlarmQuery);
$AlarmRow = mysqli_fetch_array($AlarmResult);

//echo "Here ".$AlarmRow['monitored_alarms']."<br>";

foreach ($_POST as $key => $value) {
	$key = $key."-";
	//echo $key." - ".$value;
	//echo "<br>";
	//Array of POST keys to ignore
if( !in_array($key, ['server_ip','id','delete','server_name','silence-'], true )){
	if($value == "1"){
		if(preg_match("/$key/", $AlarmRow['monitored_alarms'])){
		//There and should be nothing to do 
		}
		if(preg_match("/$key/", $AlarmRow['silenced_alarms'])){
		//There and should Not Be in SIlenced
		$DeleteAlarmQuery = "UPDATE Servers SET silenced_alarms = REPLACE(silenced_alarms,'$key,','') WHERE id = '$serverid'";
		$DeleteResult = mysqli_query($link, $DeleteAlarmQuery);
		//echo $DeleteAlarmQuery;
		
		$AlarmQuery = "Select silenced_alarms from Servers WHERE id=$serverid";
		$AlarmResult = mysqli_query($link, $AlarmQuery);
		$AlarmRow = mysqli_fetch_array($AlarmResult);
			if(!preg_match('/[A-Za-z]/',$AlarmRow['silenced_alarms'])){
			$DeleteAlarmQuery = "UPDATE Servers SET silenced_alarms = '' WHERE id = '$serverid'";
			$DeleteResult = mysqli_query($link, $DeleteAlarmQuery);
			//echo "No Letters Here";
			}
		
		} 
		$AlarmQuery = "Select monitored_alarms,silenced_alarms,silenced from Servers WHERE id=$serverid";
		$AlarmResult = mysqli_query($link, $AlarmQuery);
		$AlarmRow = mysqli_fetch_array($AlarmResult);
		if(!preg_match("/$key/", $AlarmRow['monitored_alarms'])){
		//Not there and should be 	
		$AddQuery = "Update Servers SET monitored_alarms = CONCAT_WS('','$key,',monitored_alarms) WHERE id=$serverid";
		$AddResult = mysqli_query($link, $AddQuery);
		$AddRow = mysqli_fetch_array($AddResult);
		//echo $AddQuery."<br>";

		//echo $key;
		//echo "1";
		}
		
		if(preg_match("/$key/", $AlarmRow['silenced_alarms'])){
		//There and should Not Be in Silenced
		$DeleteAlarmQuery = "UPDATE Servers SET silenced_alarms = REPLACE(silenced_alarms,'$key,','') WHERE id = '$serverid'";
		$DeleteResult = mysqli_query($link, $DeleteAlarmQuery);
		//echo $DeleteAlarmQuery;
		
		$AlarmQuery = "Select silenced_alarms from Servers WHERE id=$serverid";
		$AlarmResult = mysqli_query($link, $AlarmQuery);
		$AlarmRow = mysqli_fetch_array($AlarmResult);
			if(!preg_match('/[A-Za-z]/',$AlarmRow['silenced_alarms'])){
			$DeleteAlarmQuery = "UPDATE Servers SET silenced_alarms = '' WHERE id = '$serverid'";
			$DeleteResult = mysqli_query($link, $DeleteAlarmQuery);
			//echo "No Letters Here";
			}
		
		}
		
	
  }
	//echo "Is ".$key." in ".$AlarmRow['monitored_alarms'];
	//Deal with POSTting a NO 
	if($value == '0' && preg_match("/$key/", $AlarmRow['monitored_alarms'])){
		//There and should Not Be
		$DeleteAlarmQuery = "UPDATE Servers SET monitored_alarms = REPLACE(monitored_alarms,'$key,','') WHERE id = '$serverid'";
		$DeleteResult = mysqli_query($link, $DeleteAlarmQuery);
		//echo $DeleteAlarmQuery;
		
		$AlarmQuery = "Select monitored_alarms from Servers WHERE id=$serverid";
		$AlarmResult = mysqli_query($link, $AlarmQuery);
		$AlarmRow = mysqli_fetch_array($AlarmResult);
			if(!preg_match('/[A-Za-z]/',$AlarmRow['monitored_alarms'])){
			$DeleteAlarmQuery = "UPDATE Servers SET monitored_alarms = '' WHERE id = '$serverid'";
			$DeleteResult = mysqli_query($link, $DeleteAlarmQuery);
			//echo "No Letters Here";
			}
		//There and should Not Be
		$DeleteSilAlarmQuery = "UPDATE Servers SET silenced_alarms = REPLACE(silenced_alarms,'$key,','') WHERE id = '$serverid'";
		$DeleteSilResult = mysqli_query($link, $DeleteSilAlarmQuery);
		//echo $DeleteSilAlarmQuery;
		
		$AlarmNewQuery = "Select silenced_alarms from Servers WHERE id=$serverid";
		$AlarmNewResult = mysqli_query($link, $AlarmNewQuery);
		$AlarmNewRow = mysqli_fetch_array($AlarmNewResult);
			if(!preg_match('/[A-Za-z]/',$AlarmNewRow['silenced_alarms'])){
			$DeleteNewSilAlarmQuery = "UPDATE Servers SET silenced_alarms = '' WHERE id = '$serverid'";
			$DeleteNewSilResult = mysqli_query($link, $DeleteNewSilAlarmQuery);
			//echo "No Letters Here";
			}
		}
	
//Deal with going from Silence to No by mistake without a yes. 	
	if($value == '0' && preg_match("/$key/", $AlarmRow['silenced_alarms'])){
		$DeleteSilAlarmQuery = "UPDATE Servers SET silenced_alarms = REPLACE(silenced_alarms,'$key,','') WHERE id = '$serverid'";
		$DeleteSilResult = mysqli_query($link, $DeleteSilAlarmQuery);
		//echo $DeleteSilAlarmQuery;
		
		$AlarmNewQuery = "Select silenced_alarms from Servers WHERE id=$serverid";
		$AlarmNewResult = mysqli_query($link, $AlarmNewQuery);
		$AlarmNewRow = mysqli_fetch_array($AlarmNewResult);
			if(!preg_match('/[A-Za-z]/',$AlarmNewRow['silenced_alarms'])){
			$DeleteNewSilAlarmQuery = "UPDATE Servers SET silenced_alarms = '' WHERE id = '$serverid'";
			$DeleteNewSilResult = mysqli_query($link, $DeleteNewSilAlarmQuery);
			//echo "No Letters Here";
			}
	}		
  
	if($value == '2'){
		if(preg_match("/$key/", $AlarmRow['silenced_alarms'])){
			
		} else {
		//SilAdd to Silence
		$SilAddQuery = "Update Servers SET silenced_alarms = CONCAT_WS('','$key,',silenced_alarms) WHERE id=$serverid";
		$SilAddResult = mysqli_query($link, $SilAddQuery);
		$SilAddRow = mysqli_fetch_array($SilAddResult);
		//echo $SilAddQuery."<br>";
		if($AlarmRow['silenced'] == '0'){
		$SilenceQuery = "UPDATE Servers SET silenced = '$readable_time' WHERE id=$serverid";
		$SilenceResult = mysqli_query($link, $SilenceQuery);
		}
		}
  }
 }
}


if ($delete != 'on'){
$query = "UPDATE Servers SET name='$serverName',server_ip='$serverDescription' WHERE id=$serverid";
//echo $query;
$result = mysqli_query($link, $query);

if (mysqli_error($link) != ''){
	echo "<h1><center>".mysqli_error($link)."</center></h1>";
}else{
echo "<h1><center>Editing server</center><h1>";
}

} 

if ($delete == 'on') {
$query = "DELETE from Servers WHERE id=$serverid";
//echo $query;
$result = mysqli_query($link, $query);	

if (mysqli_error($link) != ''){
	echo "<h1><center>".mysqli_error($link)."</center></h1>";
}else{
echo "<h1><center>Deleting server</center><h1>";
}
}

$AlarmQuery = "Select silenced_alarms,silenced_all from Servers WHERE id=$serverid";
$AlarmResult = mysqli_query($link, $AlarmQuery);
$AlarmRow = mysqli_fetch_array($AlarmResult);

if($AlarmRow['silenced_alarms'] == "" && $AlarmRow['silenced_all'] == '0'){
	$query = "UPDATE Servers SET silenced='0' WHERE id=$serverid";
	$result = mysqli_query($link, $query);
}

header ("Refresh:1;url=../index.php");


?>