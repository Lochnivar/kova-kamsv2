<?php
include '/usr/src/KAMS-Setting-file.php';
$localIP = $_SERVER['SERVER_ADDR'];

//Variable Decliration
$ServerIP = $localIP . '/netmon/index.php';
$SiteName = $netmonSiteHeader;

$skip = '0';  //Deal with no data

$iface = $_GET['iface'];
$alias = $_GET['alias'];

$udplink = mysqli_connect("localhost", "webpage", "K0v@W3b", "Network_Mon");
if (mysqli_connect_error()) {
	echo mysqli_error($udplink);
	die("Database Connection Error");
}

//GET Page settings stored in Database
$PageSettings = "select * from settings";
$PageSettingsQuery = mysqli_query($udplink, $PageSettings);
$PageSettingsResult = mysqli_fetch_assoc($PageSettingsQuery);

$TCPGraphEnabled = $PageSettingsResult['show_eth'];
if ($TCPGraphEnabled = 1) {
	$TCPGraphDisabled = '';
} else {
	$TCPGraphDisabled = 'hidden=true';
}

if (isset($_GET['enableTCP'])) {
	if ($_GET['enableTCP'] == 'on') {
		$TCPGraphInsert = "update settings SET show_eth = '1'";
		$TCPGraphInsertQuery = mysqli_query($udplink, $TCPGraphInsert);
		$TCPGraphResult = mysqli_fetch_assoc($TCPGraphInsertQuery);
		$TCPGraphEnabled = 1;
		$TCPGraphDisabled = '';
	}
}
if (isset($_GET['disableTCP'])) {
	if ($_GET['disableTCP'] == 'on') {
		$TCPGraphInsert = "update settings SET show_eth = '0'";
		$TCPGraphInsertQuery = mysqli_query($udplink, $TCPGraphInsert);
		$TCPGraphResult = mysqli_fetch_assoc($TCPGraphInsertQuery);
		$TCPGraphEnabled = 0;
		$TCPGraphDisabled = 'hidden=true';
	}
}
$TCPGraphDisabled = 'hidden=true'; //just disable the ethernet Chart

$UDPRowCountWanted = $PageSettingsResult['graph_time'];

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
$InputTotallast30 = 0;
$OutputTotallast30 = 0;
$InputKBlast30 = 0;
$OutputKBlast30 = 0;
$noDataWarning = 'hidden=true';


//echo mysqli_error($udplink);
//UDP Data for Hour Avrages
$CheckUDPDataHour = "select udp_packets from data_unified WHERE iface = '" . $iface . "'  ORDER BY epoch DESC LIMIT 60";
$CheckUDPQueryHour = mysqli_query($udplink, $CheckUDPDataHour);
for ($i = 0; $i  < mysqli_num_rows($CheckUDPQueryHour); $i++) {
	$CurrentUDPRowHour =  mysqli_fetch_array($CheckUDPQueryHour);
	$totalUDPHour += $CurrentUDPRowHour['udp_packets'];
	$TotalRows = $i;
}
$CheckUDPDatathirty = "select udp_packets from data_unified WHERE iface = '" . $iface . "'  ORDER BY epoch DESC  LIMIT 30";
$CheckUDPQuerythirty = mysqli_query($udplink, $CheckUDPDatathirty);
for ($i = 0; $i  < mysqli_num_rows($CheckUDPQuerythirty); $i++) {
	$CurrentUDPRowthirty =  mysqli_fetch_array($CheckUDPQuerythirty);
	$totalUDPthirty += $CurrentUDPRowthirty['udp_packets'];
}
$CheckUDPDataten = "select udp_packets from data_unified WHERE iface = '" . $iface . "'  ORDER BY epoch DESC  LIMIT 10";
$CheckUDPQueryten = mysqli_query($udplink, $CheckUDPDataten);
for ($i = 0; $i  < mysqli_num_rows($CheckUDPQueryten); $i++) {
	$CurrentUDPRowten =  mysqli_fetch_array($CheckUDPQueryten);
	$totalUDPten += $CurrentUDPRowten['udp_packets'];
}

//UDP Data for Chart 
$CheckALLData = "select * from data_unified WHERE iface = '" . $iface . "'  ORDER BY epoch DESC  LIMIT $UDPRowCountWanted";
$CheckUDPQuery = mysqli_query($udplink, $CheckALLData);
$data = '';
$totalUDP = '0';
for ($i = 0; $i  < mysqli_num_rows($CheckUDPQuery); $i++) {
	$CurrentUDPRow =  mysqli_fetch_array($CheckUDPQuery);
	//echo "here is ".date('Y-m-d H:i:s',$CurrentUDPRow['epoch']);
	//print_r($CurrentUDPRow);
	$data .=  "{x: '" . date('Y-m-d H:i:s', $CurrentUDPRow['epoch']) . "', y:" . $CurrentUDPRow['udp_packets'] . "},";
	$UDProws = $i + 1;
	$totalUDP = $totalUDP + $CurrentUDPRow['udp_packets'];
}

?>
<!DOCTYPE html>
<html>

<head>
	<link rel="stylesheet" href="css/morris.css">
	<script src="js/jquery.min.js"></script>
	<script src="js/raphael-min.js"></script>
	<script src="js/morris.min.js"></script>
	<script src="js/js-cookie.js"></script>
	<script src="bootstrap/js/bootstrap.min.js"></script>
	<link rel="stylesheet" href="bootstrap/css/bootstrap.css">
	<script>
		var nextTime = (function() {
			var currentTime = parseInt(new Date().getTime() / 1000);
			return function() {
				return currentTime++;
			}
		})();
	</script>
	<script type="text/javascript">
		$(document).ready(function() {

			var iface = '<?php echo $iface ?>';
			var alias = '<?php echo $alias ?>'

			setInterval(function() {
				location.href = "../netmon/index-iface.php?iface=<?php echo $iface ?>&alias=<?php echo $alias ?>"
			}, 60000);
			$('.dropdown-toggle').click(function() {
				$(this).next('.dropdown').slideToggle();
			});

			$(document).click(function(e) {
				var target = e.target;
				if (!$(target).is('.dropdown-toggle') && !$(target).parents().is('.dropdown-toggle'))
				//{ $('.dropdown').hide(); }
				{
					$('.dropdown').slideUp();
				}
			});

		});
	</script>
	<style>
		body {
			background-color: #dcdcdc;
		}

		#channelLabel {
			margin-left: 30px;
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

		.avarages {
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


		div#nav {
			position: relative;
		}

		div#nav a {
			padding: 5px 15px 5px;
		}

		.dropdown-toggle {
			background-color: yellow;
			border-radius: 6px;
			color: black;
		}

		ul.dropdown {
			display: none;
			position: absolute;
			top: 100%;
			margin-top: 5px;
			padding: 5px 5px 0 0;
			background: #777;
		}

		ul.dropdown li {
			list-style-type: none;
			position: relative;
		}

		ul.dropdown li a {
			text-decoration: none;
			padding: 0em 1em;
			display: block;
			position: relative;
		}
	</style>
</head>

<body style="padding: 5px 5px 5px 5px;">

	<p><img src="kova-logo.png" id="logo"></img></p>

	<!-- TOP NAV BAR FOR PAGE NAVIGATION -->
	<div class="topnav">
		<table>
			<tr>
				<?php if ($MotorolaPage == "yes" && ($UDPMonitorPage == "yes" || $SerialMonitorPage == "yes" || $ZabbixPage == "yes")) { ?>
					<td><button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" class="active" onClick="showUDPOpts();">Motorola-Channels</a></button></td>
				<?php } ?>
				<?php if ($UDPMonitorPage == "yes") { ?>
					<td>
						<div id="nav">
							<a class="dropdown-toggle" href="#">UDP Monitor <?php echo $alias; ?></a>
							<ul class="dropdown">

								<li><a style="color:black;" href="../netmon/index-unified.php">UDP-Monitor Unified</a></li>
								<?php
								foreach ($UDPInterfaceName as $iface) {
									$iArray = explode("|", $iface);
									echo "<li><a style='color:black;' href='../netmon/index-iface.php?iface=" . $iArray[0] . "&alias=" . $iArray[1] . "'>" . $iArray[1] . "</a></li>";
								}
								?>
							</ul>
						</div>

					</td>

				<?php } ?>
				<?php if ($SerialMonitorPage == "yes") { ?>
					<td>
						<button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../serial/index.php">Serial-Monitor</a></button>
					<?php } ?>
					<?php if ($ZabbixPage == "yes") { ?>
						<button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../Monitor/index.php">Server-Monitor</a></button>
					<?php } ?>

	</div>
	</td>
	</tr>
	</table>

	<hr>

	<div>
		<div>
			<h2 id="siteName"><?php echo $SiteName; ?> Traffic Monitor <?php echo $alias; ?><br><span style="color:blue;"><?php echo $time; ?></span></h2>
			<hr>
			<h2 style="text-align:center;">UDP Traffic - Last <?php echo $UDPRowCountWanted; ?> Mins</h2>
			<div id="graph" style="height: 275px;"></div>
		</div>

		<hr>
		<p class="avarages">
			<span style="font-size:120%;">10 Min UDP Averages</span>
			<br>
			<?php
			echo round(($totalUDPten / 10), 2) . "<span style='color:blue;'> Input </span><br>";
			?>
		</p>
		<p class="avarages" <?php echo $thirtyMinuteHidden; ?>>
			<span style="font-size:120%;">30 Min UDP Averages</span>
			<br>
			<?php
			echo round(($totalUDPthirty / 30), 2) . "<span style='color:blue;'> Input </span><br>";
			?>
		</p>
		<p class="avarages" <?php echo $sixtyMinuteHidden; ?>>
			<span style="font-size:120%;">60 Min UDP Averages</span>
			<br>
			<?php
			echo round(($totalUDPHour / 60), 2) . "<span style='color:blue;'> Input </span><br>";
			?>
		</p>
		<br>
		<hr class="clear">
		<!-- Ethernet Graph Below-->
		<h2 style="text-align:center;" <?php echo $TCPGraphDisabled; ?>>Ethernet Traffic - Last 60 Mins</h2>
		<br>
		<div id="ethgraph" style="height: 225px;" <?php echo $TCPGraphDisabled; ?>></div>
		<br>
		<p class="avarages" <?php echo $TCPGraphDisabled; ?>>
			<span style="font-size:120%;">10 Min Interface Averages</span>
			<br>
			<?php
			echo round(($InputTotallast10 / 10), 2) . "<span style='color:blue;'> Input Packets </span><br>";
			echo round(($OutputTotallast10 / 10), 2) . "<span style='color:orange;'>  Output Packets</span><br>";
			echo round(($InputKBlast10 / 10), 2) . "<span style='color:red;'>  KB/s IN </span><br>";
			echo round(($OutputKBlast10 / 10), 2) . "<span style='color:green;'>  KB/s OUT </span><br>";
			?>
		</p>
		<p class="avarages" <?php echo $thirtyMinuteHidden; ?> <?php echo $TCPGraphDisabled; ?>>
			<span style="font-size:120%;">30 Min Interface Averages</span>
			<br>
			<?php
			echo round(($InputTotallast30 / 30), 2) . "<span style='color:blue;'> Input Packets</span><br>";
			echo round(($OutputTotallast30 / 30), 2) . "<span style='color:orange;'>  Output Packets</span><br>";
			echo round(($InputKBlast30 / 30), 2) . "<span style='color:red;'>  KB/s IN  </span><br>";
			echo round(($OutputKBlast30 / 30), 2) . "<span style='color:green;'>  KB/s OUT </span><br>";
			?>
		</p>
		<p class="avarages" <?php echo $sixtyMinuteHidden; ?> <?php echo $TCPGraphDisabled; ?>>
			<span style="font-size:120%;">60 Min Interface Averages</span>
			<br>
			<?php
			echo round(($InputTotal / 60), 2) . "<span style='color:blue;'> Input Packets</span><br>";
			echo round(($OutputTotal / 60), 2) . "<span style='color:orange;'>  Output Packets</span><br>";
			echo round(($InputKB / 60), 2) . "<span style='color:red;'>  KB/s IN  </span><br>";
			echo round(($OutputKB / 60), 2) . "<span style='color:green;'>  KB/s OUT </span><br>";
			?>

		</p>

		<script>
			Morris.Area({
				element: 'graph',
				data: [
					<?php echo $data ?>
				],
				lineColors: ['blue'],
				xkey: 'x',
				ykeys: ['y'],
				labels: ['Input'],
				postUnits: [' Pkts'],
				fillOpacity: '.7',
			}).on('click', function(i, row) {
				console.log(i, row);
			});

			Morris.Area({
				element: 'ethgraph',
				data: [
					<?php echo $ethdata ?>
				],
				lineColors: ['red', 'green'],
				xkey: 'x',
				ykeys: ['y', 'z'],
				labels: ['Input', 'Output'],
				postUnits: [' KBs'],
				fillOpacity: '.7',
				behaveLikeLine: 'true',
			}).on('click', function(i, row) {
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
						<form method="GET" id="PageSettingsSelect" name="PageSettingsSelect" action="update-time.php">
							Select UDP Graph Minutes<select name='UDPRowCount' id='UDPRowCount'>
								<?php
								$i = 15;
								while ($i < 91) {
									if ($UDPRowCountWanted == $i) {
										echo "<option value='" . $i . "' selected='true'>" . $i . "</option>";
										$i++;
									} else {
										echo "<option value='" . $i . "'>" . $i . "</option>";
										$i++;
									}
								}
								?>
							</select>
							<br>
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
</body>

</html>