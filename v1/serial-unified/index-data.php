<?php

include '/usr/src/KAMS-Setting-file.php';

sleep(5);

$mainDataArray = [];

$ipArray = getIPArray();

$timenow = time();

//file_put_contents("/tmp/Croncheck.log", Date("Y-m-d h:i:s", $timenow) . " Starting Cron" . PHP_EOL, FILE_APPEND);

foreach ($ipArray as $ip) {


//file_put_contents("/tmp/Croncheck.log", Date("Y-m-d h:i:s", $timenow) . " Processing IP: " . $ip['ip'] . PHP_EOL, FILE_APPEND);
	$db = "Network_Mon";
	$sql = "select * from settings";

	$thisIP = $ip['ip'];

	$mainDataArray[$thisIP]['settings'] = fetchData($thisIP, $db, $sql);

	$db = "Serial_Users";

	foreach ($ip['serials'] as $v) { //serials loop

		if (isset($_GET['SerialSizeRowCount'])) {
//			$NewSerialSizeCount = $_GET['SerialSizeRowCount'];
//			$PageSettingsInsert = "update settings SET graph_time = $NewSerialSizeCount";

//			fetchData($thisIP, $db, $PageSettingsInsert);
//
//			$SerialSizeRowCountWanted = $_GET['SerialSizeRowCount'];
		} else {
			$SerialSizeRowCountWanted = $mainDataArray[$thisIP]['settings']['graph_time'];
		}
		// change back if webpage time is wrong
		date_default_timezone_set("America/New_York");
		$time = Date('D M d - H:i:s');

		foreach ($ip['serials'] as $k => $v) {

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
			$WarningHidden = 'hidden=true';

			//echo " table is ".$v['table']."\r\n";

			$sql = "select size from " . $v['table'] . " ORDER by id DESC LIMIT 18";

			$mainDataArray[$thisIP][$k]["threeHours"] = fetchData($thisIP, $db, $sql, 1);

			$sql = "select size from " . $v['table'] . " ORDER by id DESC LIMIT 12";

			$mainDataArray[$thisIP][$k]["twoHours"] = fetchData($thisIP, $db, $sql, 1);

			$sql = "select size from " . $v['table'] . " ORDER by id DESC LIMIT 6";

			$mainDataArray[$thisIP][$k]["oneHour"] = fetchData($thisIP, $db, $sql, 1);

$SerialSizeRowCountWanted = 45;

			//Serial Data for Chart
				$sql = "select * from " . $v['table'] . " ORDER by id DESC LIMIT " . $SerialSizeRowCountWanted;
			$mainDataArray[$thisIP][$k]['rowCount'] = fetchData($thisIP, $db, $sql, 2);
		};
		$data = '';
		$totalSerial = '';
	};
}

file_put_contents("/tmp/Croncheck.log", Date("Y-m-d h:i:s", $timenow) . " Have Data - Tracker 1" . PHP_EOL, FILE_APPEND);

$html = getHTMLHead();

foreach ($ipArray as $ip) {
	$thisIP = $ip['ip'];
	$totalSerial = 0;
	foreach ($ip['serials'] as $name => $serial) {
		$html .= "<tr><td colspan = 3 align='center'>";
		$html .= "<strong><span class='tablehead'>";
		$html .= "<a href='" . $serial['link'] . "'>" . $ip['servName'] . " : " . $thisIP . " " . $name . "</a></span></strong></td></tr>";

		$data = "";
		foreach ($mainDataArray[$thisIP][$name]['rowCount']['id'] as $k => $v) {
			$dataPoint = number_format($v / 1024, 2);

			$data .= "{x: '" . date('Y-m-d H:i:s', $k) . "', y:" . $dataPoint . "},";
			$totalSerial += $v;
			$totalSerial = number_format($totalSerial / 1024, 2);
		}

		$oneHourArray = array_filter($mainDataArray[$thisIP][$name]['oneHour']['size']);
		if(count($oneHourArray) > 0){		
$oneHourAvg = array_sum($oneHourArray) / count($oneHourArray);
}
else{
$oneHourAvag = 0;
	}

$twoHourArray = array_filter($mainDataArray[$thisIP][$name]['twoHours']['size']);

if(count($twoHourArray) > 0){  
		$twoHourAvg = array_sum($twoHourArray) / count($twoHourArray);

}
else{ 
$twoHourAvag = 0;
        }





		$threeHourArray = array_filter($mainDataArray[$thisIP][$name]['threeHours']['size']);


if(count($threeHourArray) > 0){ 
		$threeHourAvg = array_sum($threeHourArray) / count($threeHourArray);
}
else{ 
$threeHourAvag = 0;
        }

		file_put_contents("/tmp/Croncheck.log", Date("Y-m-d h:i:s", $timenow) . " Building HTML String" . PHP_EOL, FILE_APPEND);
$html .= "<tr align='center'><td colspan = 3> Total Serial Througput: " . $totalSerial . " KB</td></tr>";
		$html .= "<tr align='center'><td>One Hour Average: " . $oneHourAvg . " KB</td>";
		$html .= "<td>Two Hour Average: " . $twoHourAvg . " KB</td>";
		$html .= "<td>Three Hour Average: " . $threeHourAvg . " KB</td></tr>";
		$html .= "<tr align = 'center'><td colspan = 3><div id='graph-" . $thisIP . "-" . $name . "'></div>";
//file_put_contents("/tmp/Croncheck.log", Date("Y-m-d h:i:s", $timenow) . " Building Graphs" . PHP_EOL, FILE_APPEND);
		$html .= buildGraph($thisIP, $name, $data);
//file_put_contents("/tmp/Croncheck.log", Date("Y-m-d h:i:s", $timenow) . " Done with Graphs" . PHP_EOL, FILE_APPEND);
		$html .= "</td></tr>";
//file_put_contents("/tmp/Croncheck.log", Date("Y-m-d h:i:s", $timenow) . " Tracker 4" . PHP_EOL, FILE_APPEND);
	};
//file_put_contents("/tmp/Croncheck.log", Date("Y-m-d h:i:s", $timenow) . " Tracker 4.5" . PHP_EOL, FILE_APPEND);
};
//file_put_contents("/tmp/Croncheck.log", Date("Y-m-d h:i:s", $timenow) . " Tracker 5" . PHP_EOL, FILE_APPEND);

$html .= "</body></html>";

//file_put_contents("/tmp/Croncheck.log", Date("Y-m-d h:i:s", $timenow) . " Finished HTML, About to echo" . PHP_EOL, FILE_APPEND);


echo $html;

//file_put_contents("/tmp/Croncheck.log", Date("Y-m-d h:i:s", $timenow) . " HTML" . PHP_EOL . $html . PHP_EOL, FILE_APPEND);
file_put_contents("/var/www/html/serial-unified/index.html", $html);


function errHandler($alertArray, $issueNumber = null){

		$x = 1;

		foreach($alertArray as $line){
		$arg[$x] = $line;
}

include("/usr/src/colo-send-message.php");


}

function getIPArray()
{

	$ipArray = [
		"CSR" => [
			"ip" => "10.100.1.37",
			"servName" => "CSR-KAMS",
			"serials" => [
				"CSR1" => [
					"table" => "traffic_mon",
					"thresh" => 90000,
					"link" => "http://10.100.1.37/serial/index.php",
					"second" => "yes"
					],
				"CSR2" => [
					"table" => "traffic_mon2",
					"thresh" => 90000,
					"link" => "http://10.100.1.37/serial2/index.php"
					]
				]
			]
		];


	return $ipArray;
}



function fetchData($ip, $db, $sql, $multi = 0)
{
	unset($respoonse, $cmd);

	$cmd = cmdBuilder($ip, $db, $sql);
	exec($cmd, $response);
	$data = formatTable($response, $multi);

	return $data;
}

function cmdBuilder($ip, $db, $sql)
{
	return "ssh -o ConnectTimeout=5 -t root@" . $ip . " \"/usr/bin/mysql " . $db .  " -e '" . $sql . "'\" ";
}

function formatTable($data, $multi = 0)
{
	unset($finalArray, $headArray, $newData, $multiArray);

	$newData = [];

	foreach ($data as  $line) {


	$line = str_replace(array("+", "-"), "", $line);


				$newData[] = $line;


	
}		
	$newData = array_filter($newData);
	$aheads = array_shift($newData);



 	$aheads = preg_replace("/\s+/", "|", $aheads);

	$headArray = array_map("trim", explode("|", $aheads));

	$finalArray = [];

	foreach ($newData as $data) {
		$x = 0;

	$line = preg_replace("/\s+/", "|", $data);
	
	$dline = array_map("trim", explode("|", $line));
		foreach ($dline as $d) {
			if ($multi == 0) {
				$finalArray[$headArray[$x]] = $d;
			} else if ($multi == 1) {
				$finalArray[$headArray[$x]][] = $d;
			} else if ($multi == 2) {
				$multiArray[$headArray[$x]][] = $d;
			}

			$x++;
		}
		unset($dline);
	}
	if ($multi == 2) {
		$y = 0;

		foreach ($multiArray['id'] as $v) {

			$finalArray['id'][$multiArray['epoch'][$y]] = $multiArray['size'][$y];
			$y++;
		}
		
	}

	return $finalArray;
}

function buildGraph($ip, $serial, $data)
{
	var_dump("Data", $data);
	$graph = "";

	$graph .= "<script type='text/javascript'>


		Morris.Area({

	element: 'graph-" . $ip . "-" . $serial . "',

	data: [" . $data . "],

	lineColors: ['green'],

	xkey: 'x',

	ykeys: ['y'],

	labels: ['Input'],

	postUnits: [' KB'],

	fillOpacity: '.7',

}).on('click', function(i, row) {

	console.log(i, row);

});
</script>";
//	var_dump("Graph", $graph);
	return $graph;
}

function getHTMLHead()
{

	$html = <<<END
		<!DOCTYPE html>
	
	
	<html>
	  <head>
		<meta
		  http-equiv="refresh"
		  content="600; URL=http://10.100.1.37/serial-unified/index.html"
		/>
		<meta http-equiv="Cache-control" content="no-cache">
		<meta http-equiv="Expires" content="-1">
		<link rel="stylesheet" href="css/morris.css" />
	
		<script src="js/jquery.min.js"></script>
	
		<script src="js/raphael-min.js"></script>
	
		<script src="js/morris.min.js"></script>
	
		<script src="bootstrap/js/bootstrap.min.js"></script>
	
		<script>
			$(function() {
			setInterval(function(){

			var d = new Date();
			var s = d.getSeconds();
  			var m = d.getMinutes();
  			var h = d.getHours();
  		 $(".clock").html(("0" + h).substr(-2) + ":" + ("0" + m).substr(-2) + ":" + ("0" + s).substr(-2));
		}, 1000);

});
</script>

		<link rel="stylesheet" href="bootstrap/css/bootstrap.css" />
	
		<style>
		.clock {
			position: absolute;
 			top: 25px;
  			right: 150px;
 			width: 100px;
  			height: 50px;
			font-size: 50px;
		}

		  #channelLabel {
			margin-left: 5px;
		  }
	
		  #logo {
			position: relative;
			height: 87px;
			width: 347px;
			padding: 3px;
			margin-bottom: 1px;
			background-color: #883330;
			margin-top: 1px;
			margin-left: 10px;
			border-radius: 1%;
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
			from {
			  color: red;
			}
			to {
			  color: black;
			}
		  }
	
		  #warning {
			float: right;
			margin-right: 20px;
			text-decoration: underline;
		  }
	
		  .averages {
			margin-left: 140px;
			float: left;
			border: 2px solid black;
			padding: 4px;
			border-radius: 3%;
		  }
	
		  #siteName {
			margin-left: 50px;
		  }
	
		  .clear {
			clear: both;
		  }

		.tablehead {
			font-size: 24px;
			color: #0059FF;
		}
		</style>
	
		
	  </head>
	
	  <body style="padding: 5px 5px 5px 5px; background-color: #dcdcdc">
	  <p><img src="kova-logo.png" id="logo"></img></p>
	 
		<span class = "clock" id="jclock"></span>
 <table id = "graph_table" cols = 3 border = 1 width = '100%'>
	  
END;

	return $html;
}
