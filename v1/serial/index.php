<?php
include '/usr/src/KAMS-Setting-file.php';
$localIP = $_SERVER['SERVER_ADDR'];
//Variable Decliration
$ServerIP = $localIP.'/serial/index.php';
$SiteName = $SerialSiteHeader;

$skip = '0';  //Deal with no data



$link = mysqli_connect("127.0.0.1", "serial", "serial7906", "Serial_Users");  
        if (mysqli_connect_error()) { 
		echo mysqli_error();		
        die ("Database Connection Error");   
        }

//GET Page settings stored in Database
		$PageSettings = "select * from settings";
		$PageSettingsQuery = mysqli_query($link, $PageSettings);
		$PageSettingsResult = mysqli_fetch_assoc($PageSettingsQuery);
		

	$SerialSizeRowCountWanted = $PageSettingsResult['graph_time'];

//
date_default_timezone_set($TimeZone);
	$time = Date('D M d  -  H:i:s');
	 $WarningHidden = 'hidden=true';


	$InputTotal = 0;
	$OutputTotal = 0;
	$InputKB = 0;
	$OutputKB = 0;
	$InputTotallast10 = 0;
	$OutputTotallast10 = 0;
	$InputKBlast10 = 0;
	$OutputKBlast10 = 0;
	$InputTotallast5 = 0;
	$OutputTotallast5 = 0;
	$InputKBlast5 = 0;
	$OutputKBlast5 = 0;
	$noDataWarning = 'hidden=true';
	
	
	//echo mysqli_error($link);
//Serial Data for threeHours Avrages
	$CheckSerialDatathreeHours = "select size from traffic_mon ORDER by id DESC  LIMIT 18";
	$CheckSerialQuerythreeHours = mysqli_query($link, $CheckSerialDatathreeHours);
		for ($i=0; $i  < mysqli_num_rows($CheckSerialQuerythreeHours); $i++) {
			$CurrentSerialRowthreeHours =  mysqli_fetch_array($CheckSerialQuerythreeHours);
			$totalSerialthreeHours += $CurrentSerialRowthreeHours['size'];
			$TotalRows = $i;
		}
	$CheckSerialDatatwoHours = "select size from traffic_mon ORDER by id DESC  LIMIT 12";
	$CheckSerialQuerytwoHours = mysqli_query($link, $CheckSerialDatatwoHours);
		for ($i=0; $i  < mysqli_num_rows($CheckSerialQuerytwoHours); $i++) {
			$CurrentSerialRowtwotwoHourss =  mysqli_fetch_array($CheckSerialQuerytwoHours);
			$totalSerialtwoHours += $CurrentSerialRowtwotwoHourss['size'];

		}
	$CheckSerialDataHour = "select size from traffic_mon ORDER by id DESC  LIMIT 6";
	$CheckSerialQueryHour = mysqli_query($link, $CheckSerialDataHour);
		for ($i=0; $i  < mysqli_num_rows($CheckSerialQueryHour); $i++) {
			$CurrentSerialRowHour =  mysqli_fetch_array($CheckSerialQueryHour);
			$totalSerialHour += $CurrentSerialRowHour['size'];

		}
	
	//Serial Data for Chart 
	$CheckALLData = "select * from traffic_mon ORDER by id DESC  LIMIT $SerialSizeRowCountWanted";
	$CheckSerialQuery = mysqli_query($link, $CheckALLData);
		$data = '';
		$totalSerial = '0';
		for ($i=0; $i  < mysqli_num_rows($CheckSerialQuery); $i++) {
			$CurrentSerialRow =  mysqli_fetch_array($CheckSerialQuery);
			 //echo "here is ".date('Y-m-d H:i:s',$CurrentSerialRow['epoch']);
			//print_r($CurrentSerialRow);
			$dataPoint = number_format($CurrentSerialRow['size']/1024, 2);
			$data .=  "{x: '".date('Y-m-d H:i:s',$CurrentSerialRow['epoch'])."', y:".$dataPoint."},";
			$Serialrows = $i + 1;
			$totalSerial += $CurrentSerialRow['size'];
			$totalSerial = number_format($totalSerial / 1024, 2);
			
		}
		//echo "Serial ROws is ".$Serialrows." and wanted is ".$SerialSizeRowCountWanted."<br>";
		
	

	
	//Hide the avarages if we dont have enough Data Yet
	$twoHoursHidden = '';
	$HourHidden = '';
	if ($TotalRows < 6) {
	$noDataWarning = '';
	}
		
	if ($TotalRows < 12) {
		$twoHoursHidden = 'hidden=true';
	}
	if ($TotalRows < 17) {
		$threeHoursHidden = 'hidden=true';
		
	}
?>
<!DOCTYPE html>
<html>
    <head>
	<meta http-equiv="refresh" content="60; URL=http://<?php echo $ServerIP; ?>">
        <link rel="stylesheet" href="css/morris.css">
		<script src="js/jquery.min.js"></script>
		<script src="js/raphael-min.js"></script>
		<script src="js/morris.min.js"></script>
		<script src="bootstrap/js/bootstrap.min.js"></script>
		<link rel="stylesheet" href="bootstrap/css/bootstrap.css">
 
		<style>
		#channelLabel {
			margin-left:5px;
		}
		#logo {
				position:relative;
				height:87px;
				width:347px;
				padding:3px;
				margin-bottom:1px;
				background-color:#883330;
				margin-top:1px;
				margin-left:10px;
				border-radius:1%;
				}
		.flash {
			   animation-name: flash;
				animation-duration: 0.3s;
				animation-timing-function: linear;
				animation-iteration-count: infinite;
				animation-direction: alternate;
				animation-play-state: running;
			}

			@keyframes flash {
				from {color: red;}
				to {color: black;}
			}
			
			#warning {
				float:right;
				margin-right:20px;
				text-decoration: underline;
			}
			.avarages {
				margin-left:140px;
				float:left;
				border:2px solid black;
				padding:4px;
				border-radius:3%;
			}
			#siteName {
				margin-left:50px;
			}
			.clear {
				clear:both;
				}
		</style>
    </head>
    <body style="padding: 5px 5px 5px 5px;background-color:#dcdcdc;">
	
	<!-- TOP NAV BAR FOR PAGE NAVIGATION --!>
	<div class="topnav">
	<?php if($MotorolaPage == "yes"){ ?>
	  <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" class="active" href="../channels/index.php">Motorola-Channels</a></button>
	<?php } ?>
	<?php if($UDPMonitorPage == "yes"){ ?>
	  <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../netmon/index.php">UDP-Monitor</a></button>
	 <?php } ?>
	 <?php if($SerialMonitorPage == "yes"  && ($MotorolaPage == "yes" || $UDPMonitorPage == "yes" || $ZabbixPage == "yes")){ ?>
	  <button style="background-color:yellow;border-radius: 6px;color:black"><a style="color:black;" href="../serial/index.php">Serial-Monitor</a></button>
	 <?php } ?>


	<?php if($ZabbixPage == "yes"){ ?>
          <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../Monitor/index.php">Server-Monitor</a></button>
         <?php } ?>
	</div>
	<?php
	if ($Serialrows < $SerialSizeRowCountWanted) {
			echo "<h1 style='color:red;'>Please Wait While we collect data to create the Graph<br>The System will Display the Graph Shortly</h1>";
		}

	if($skip == '0'){
		$SerialAvarage = ($totalSerialHour / 6);
				//Adjust Threshold for data Here  also send emails based of of this. Adjust threshold with loop 
			if($SerialAvarage < $SerialTriggerLimit){
				$WarningHidden = '';
			}
	}
	?>
    <hr>
	
	<!-- Button trigger modal -->

	<!-- Good under Here -->
	<div>
		<div>
		   <?php  if($skip == '0'){ ?>
				<button type="button" class="btn btn-primary" data-toggle="modal" data-tarGET="#myModal" id="weatherModal" style="box-shadow: 10px 10px 5px #888888;float:left;font-size:175%;margin-right:30px;margin-top:15px;border-radius:5%;background-color:#883330;color:white;float:right;">
				 Page Settings
				</button>
		   <?php } ?>
		</div>
	<h1 id="warning" class="flash" <?php echo $WarningHidden ?>>WARNING! Serial Traffic Size Below Set Threshold</h1>
	<h1 id="warning" class="flash" <?php echo $noDataWarning ?>>Please Wait For The System To Gather Data for Avarages</h1>
	
		 <p><img src="kova-logo.png" id="logo"></img></p>
		 <h2 id="siteName"><?php echo $SiteName; ?> Serial Traffic Monitor<br><span style="color:blue;" ><?php echo $time; ?></span></h2>
		 <hr>
		 
	<h2 style="text-align:center;">Serial Traffic Total - Last <?php echo $SerialSizeRowCountWanted; ?>0 Minutes</h2>
	<div id="graph" style="height: 275px;"></div>
	</div>
	
	<hr>
		<p class="avarages">
		<span style="font-size:120%;">1 Hour Average</span>
		<br>
		<?php
		echo round(($totalSerialHour / 6)/1024,2)."<span style='color:blue;'> KB </span><br>";
		?>
		</p>	
		<p class="avarages" <?php echo $twoHoursHidden; ?>>
		<span style="font-size:120%;">2 Hours Average</span>
		<br>
		<?php
		echo round(($totalSerialtwoHours / 12)/1024,2)."<span style='color:blue;'> KB </span><br>";
		?>
		</p>
		<p class="avarages" <?php echo $threeHoursHidden; ?>>
		<span style="font-size:120%;">3 Hours Average</span> 
		<br>
		<?php
		echo round(($totalSerialthreeHours / 18)/1024,2)."<span style='color:blue;'> KB </span><br>";
		?>
		</p>
	<br>
	<hr class="clear">

	

	<script>

	Morris.Area({
  element: 'graph',
  data : [
		<?php echo $data ?>	
  ],
  lineColors: ['green'],
  xkey: 'x',
  ykeys: ['y'],
  labels: ['Input'],
  postUnits: [' KB'],
  fillOpacity: '.7',
}).on('click', function(i, row){
  console.log(i, row);
});



	
	</script>
	<!-- Settings Modal -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLabel">Page Settings</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form method="GET" id="PageSettingsSelect" name="PageSettingsSelect"action="update-time.php">
			Select Serial Graph Period (1 = 10 Min)<select  name='SerialSizeRowCount' id='SerialSizeRowCount'>
			<?php 
			$i = 5;
			while ($i < 44){
				if ($SerialSizeRowCountWanted == $i) {
					echo "<option value='".$i."' selected='true'>".$i."</option>";
					$i++;
				}else {
					echo "<option value='".$i."'>".$i."</option>";
					$i++;
				}
			}
			?>
			</select>
			<br>
			<a href="./files.php">Debug File List</a>
			<br>
			<br>
			<input type="submit" class="checkBoxes" id="preferences" name"preferences" value="Save Settings" autocomplete="off"></input>
		</form>
      </div>
      <div class="modal-footer">
	  
      </div>
    </div>
  </div>
</div>

<!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->

    </body>
</html>
	
