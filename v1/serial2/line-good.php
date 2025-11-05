<?php
//Variable Decliration
$ServerIP = '192.168.1.7';
$SiteName = 'Harris';
$RowCountWanted = '45';  //the amount of data points shown on the chart. 

date_default_timezone_set('America/New_York');
	$time = Date('D M d  -  H:i:s');
	 $WarningHidden = 'hidden=true';
	$link = mysqli_connect("localhost", "public_query", "public7906", "Network_Mon");   
        if (mysqli_connect_error()) { 
		echo mysqli_error();		
        die ("Database Connection Error");   
        }

	$InputTotal = 0;
	$OutputTotal = 0;
	$InputKB = 0;
	$OutputKB = 0;
	$InputTotallast10 = 0;
	$OutputTotallast10 = 0;
	$InputKBlast10 = 0;
	$OutputKBlast10 = 0;
	$InputTotallast30 = 0;
	$OutputTotallast30 = 0;
	$InputKBlast30 = 0;
	$OutputKBlast30 = 0;
	
	$link = mysqli_connect("localhost", "public_query", "public7906", "Network_Mon");   
        if (mysqli_connect_error()) { 
		echo mysqli_error();		
        die ("Database Connection Error");   
        }
	
	//echo mysqli_error($link);
	
	//UDP Data for Chart
	
	$CheckUDPData = "select * from data_UDP ORDER by time DESC  LIMIT $RowCountWanted";
	$CheckUDPQuery = mysqli_query($link, $CheckUDPData);
		$data = '';
		$totalUDP = '';
		for ($i=0; $i  < mysqli_num_rows($CheckUDPQuery); $i++) {
			$CurrentUDPRow =  mysqli_fetch_array($CheckUDPQuery);
			//print_r($CurrentUDPRow);
			$data .=  "{x: '".$CurrentUDPRow['time']."', y:".$CurrentUDPRow['idgm']."},";
			$UDProws = $i;
			$totalUDP += $CurrentUDPRow['idgm'];
		}

		if ($UDProws > $RowCountWanted) {
			echo "<h1 style='color:red;'>Please Wait While we collect data to create the Graph<br>The System will Dispaly the Graph  after 30 Minutes</h1>";
		}

		$UDPAvarage = ($totalUDP / 60);
				//Adjust Threshold for data Here  also send emails based of of this. Adjust threshold with loop 
			if ($UDPAvarage < '0.5') {
				$WarningHidden = '';
			}
	
		
	
	//Ethernet Data for INterface
	$CheckData = "select * from data ORDER by time DESC  LIMIT 60";
	$CheckQuery = mysqli_query($link, $CheckData);
	for ($i=0; $i  < mysqli_num_rows($CheckQuery); $i++) {
	$CurrentRow =  mysqli_fetch_array($CheckQuery);
	$InputTotal += $CurrentRow['eth_input_avg'];	
	$OutputTotal += $CurrentRow['eth_output_avg'];
	$InputKB += $CurrentRow['eth_input_kb_avg'];
	$OutputKB += $CurrentRow['eth_kb_output_avg'];
	$dataRows = $i;
	$ethdata .=  "{x: '".$CurrentRow['time']."', y:".$CurrentRow['eth_input_avg'].", z: ".$CurrentRow['eth_output_avg']."},";
}
	for ($j=0; $j  < 29; $j++){
		 $InputTotallast30 += $CurrentRow['eth_input_avg'];	
			$OutputTotallast30 += $CurrentRow['eth_output_avg'];
			$InputKBlast30 += $CurrentRow['eth_input_kb_avg'];
			$OutputKBlast30 += $CurrentRow['eth_kb_output_avg']; 
	}
	
	for ($k=0; $k  < 9; $k++){
				$InputTotallast10 += $CurrentRow['eth_input_avg'];	
				$OutputTotallast10 += $CurrentRow['eth_output_avg'];
				$InputKBlast10 += $CurrentRow['eth_input_kb_avg'];
				$OutputKBlast10 += $CurrentRow['eth_kb_output_avg']; 
			 }
	//Hide the avarages if we dont have enough Data Yet
		$thirtyMinuteHidden = '';
		$sixtyMinuteHidden = '';
		
	if ($dataRows < 30) {
		$thirtyMinuteHidden = 'hidden=true';
	}
	if ($dataRows < 59) {
		$sixtyMinuteHidden = 'hidden=true';
	}
?>
<!DOCTYPE html>
<html>
    <head>
	<meta http-equiv="refresh" content="60; URL=http://<?php echo $ServerIP; ?>/morris/line.php">
        <link rel="stylesheet" href="css/morris.css">
		<script src="js/jquery.min.js"></script>
		<script src="js/raphael-min.js"></script>
		<script src="js/morris.min.js"></script>

        <script>
            var nextTime = (function() {
                var currentTime = parseInt(new Date().getTime() / 1000);
                return function() { return currentTime++; }
            })();
        </script>
		<style>
		body {
			background-color:#dcdcdc;
			margin:0 auto;
		}
		#channelLabel {
			margin-left:30px;
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
		</style>
    </head>
    <body>
	
	
	
	<!-- Good under Here -->
	<h1 id="warning" class="flash" <?php echo $WarningHidden ?>>WARNING! No Data For the Last 10 Minutes</h1>
	 <p><img src="kova-logo.png" id="logo"></img></p>
	 <h2 id="siteName"><?php echo $SiteName; ?> Traffic Monitor<br><span style="color:blue;" ><?php echo $time; ?></span></h2>
	 <hr>
	<h2 style="text-align:center;">UDP Traffic - Last <?php echo $RowCountWanted; ?> Mins</h2>
	<div id="graph" style="height: 275px;"></div>
	<br>
	<hr>
	<h2 style="text-align:center;">Ehternet Traffic - Last 60 Mins</h2>
	<br>
	<div id="ethgraph" style="height: 275px;"></div>
	<br>
		<p class="avarages">
		<span style="font-size:120%;">10 Min Interface Averages</span>
		<br>
		<?php
		echo round(($InputTotallast10 / 10),2)."<span style='color:blue;'> Input </span><br>";
		echo round(($OutputTotallast10 / 10), 2)."<span style='color:orange;'>  Output </span><br>";
		echo round(($InputKBlast10 / 10), 2)."<span style='color:red;'>  KB/s IN </span><br>";
		echo round(($OutputKBlast10 / 10), 2)."<span style='color:green;'>  KB/s OUT </span><br>";
		?>
		</p>
		<p class="avarages" <?php echo $thirtyMinuteHidden; ?>>
		<span style="font-size:120%;">30 Min Interface Averages</span>
		<br>
		<?php
		echo round(($InputTotallast30 / 30),2)."<span style='color:blue;'> Input </span><br>";
		echo round(($OutputTotallast30 / 30), 2)."<span style='color:orange;'>  Output </span><br>";
		echo round(($InputKBlast30 / 30), 2)."<span style='color:red;'>  KB/s IN  </span><br>";
		echo round(($OutputKBlast30 / 30), 2)."<span style='color:green;'>  KB/s OUT </span><br>";
		?>
		</p>
		<p class="avarages" <?php echo $sixtyMinuteHidden; ?>>
		<span style="font-size:120%;">60 Min Interface Averages</span>
		<br>
		<?php
		echo round(($InputTotal / 60),2)."<span style='color:blue;'> Input </span><br>";
		echo round(($OutputTotal / 60), 2)."<span style='color:orange;'>  Output </span><br>";
		echo round(($InputKB / 60), 2)."<span style='color:red;'>  KB/s IN  </span><br>";
		echo round(($OutputKB / 60), 2)."<span style='color:green;'>  KB/s OUT </span><br>";
		?>
		
		</p>
	<script>

	Morris.Area({
  element: 'graph',
  data : [
		<?php echo $data ?>	
  ],
  lineColors: ['blue'],
  xkey: 'x',
  ykeys: ['y'],
  labels: ['Input'],
  postUnits: [' Pkts'],
  fillOpacity: '.7',
}).on('click', function(i, row){
  console.log(i, row);
});

	Morris.Area({
  element: 'ethgraph',
  data : [
		<?php echo $ethdata ?>	
  ],
  lineColors: ['red','green'],
  xkey: 'x',
  ykeys: ['y','z'],
  labels: ['Input','Output'],
  postUnits: [' Pkts'],
  fillOpacity: '.7',
}).on('click', function(i, row){
  console.log(i, row);
});


	
	</script>
    </body>
</html>
