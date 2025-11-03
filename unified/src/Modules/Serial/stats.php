<?php
//Variable Decliration
$ServerIP = '192.168.100.41/network-monitor/index.php';
$SiteName = 'Harris';


$link = mysqli_connect("localhost", "webpage", "K0v@W3b", "Network_Mon");   
        if (mysqli_connect_error()) { 
		echo mysqli_error();		
        die ("Database Connection Error");   
        }

	
date_default_timezone_set('America/New_York');
	$time = Date('D M d  -  H:i:s');
	 $WarningHidden = 'hidden=true';
	$link = mysqli_connect("localhost", "webpage", "K0v@W3b", "Network_Mon");   
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
	$totalETHDayOut = 0;
	$totalETHDayIn = 0;
	$totalUDPDayOut = 0;
	$totalUDPDayIn = 0;
	
	//echo mysqli_error($link);
	
	//UDP Data for Hour Avrages
	$mysqldate = date("Y-m-d", time() - 60 * 60 * 24);
	//$mysqldate = date('Y-m-d');
	//$mysqldate = '2017-04-23';
	
	for ($i = 0; $i < 24; $i++) {
		if ($i < 10) {
	$CheckUDPDataHour = "select * from data_UDP where time between '$mysqldate 0$i:00:00' and '$mysqldate 0$i:59:59' ORDER by time DESC  LIMIT 60";
		} else {
	$CheckUDPDataHour = "select * from data_UDP where time between '$mysqldate $i:00:00' and '$mysqldate $i:59:59' ORDER by time DESC  LIMIT 60";
		}
		$totalUDPHour = '';
		$totalUDPHourOut = '';
	$CheckUDPQueryHour = mysqli_query($link, $CheckUDPDataHour);
		for ($j=0; $j  < mysqli_num_rows($CheckUDPQueryHour); $j++) {
			$CurrentUDPRowHour =  mysqli_fetch_array($CheckUDPQueryHour);
			$totalUDPHour += $CurrentUDPRowHour['idgm'];
			$totalUDPHourOut += $CurrentUDPRowHour['odgm'];
			
		}
		${'udpAvrIn'.$i} = $totalUDPHour / 60;
		${'udpAvrOut'.$i} = $totalUDPHourOut / 60;
		if ($i < 10) {
		$InsertAvr = "insert into data_avarages (`udp_hourly_in`, `udp_hourly_out`, `time`) Values (${'udpAvrIn'.$i},${'udpAvrOut'.$i},'$mysqldate 0$i:00:00')";
		$InsertQuery = mysqli_query($link, $InsertAvr);
		} else {
		$InsertAvr = "insert into data_avarages (`udp_hourly_in`, `udp_hourly_out`, `time`) Values (${'udpAvrIn'.$i},${'udpAvrOut'.$i},'$mysqldate $i:00:00')";
		$InsertQuery = mysqli_query($link, $InsertAvr);
		}
	}
	
	//UDP Day Avarage
	$CheckUDPDataDay = "select * from data_UDP where time between '$mysqldate 00:00:00' and '$mysqldate 23:59:59' ORDER by time DESC  LIMIT 1440";
	$CheckUDPQueryDay = mysqli_query($link, $CheckUDPDataDay);	
	for ($i=0; $i  < mysqli_num_rows($CheckUDPQueryDay); $i++) {
			$CurrentUDPRowDay =  mysqli_fetch_array($CheckUDPQueryDay);
			$totalUDPDayIn += $CurrentUDPRowDay['idgm'];
			$totalUDPDayOut += $CurrentUDPRowDay['odgm'];
		}
	$UDPdayTotalIn = $totalUDPDayIn / 1440;
	$UDPdayTotalOut = $totalUDPDayOut / 1440;
	
	$InsertAvrDay = "insert into data_avarages_day (`udp_day_in`,`udp_day_out`,`id`) Values ($UDPdayTotalIn,$UDPdayTotalOut,'$mysqldate')";
	$InsertQueryDay = mysqli_query($link, $InsertAvrDay);
	
	//Ethernet Data
	for ($i = 0; $i < 24; $i++) {
		if ($i < 10) {
	$CheckethDataHour = "select * from data where time between '$mysqldate 0$i:00:00' and '$mysqldate 0$i:59:59' ORDER by time DESC  LIMIT 60";
		} else {
	$CheckethDataHour = "select * from data where time between '$mysqldate $i:00:00' and '$mysqldate $i:59:59' ORDER by time DESC  LIMIT 60";
		}
		$totalethHour = '';
		$totalethHourOut = '';
	$CheckethQueryHour = mysqli_query($link, $CheckethDataHour);
		for ($j=0; $j  < mysqli_num_rows($CheckethQueryHour); $j++) {
			$CurrentethRowHour =  mysqli_fetch_array($CheckethQueryHour);
			$totalethHour += $CurrentethRowHour['eth_input_kb_avg'];
			$totalethHourOut += $CurrentethRowHour['eth_kb_output_avg'];
		}
		${'ethAvrIn'.$i} = number_format($totalethHour / 60,2);
		${'ethAvrOut'.$i} = number_format($totalethHourOut / 60,2);

		if ($i < 10) {
		$InsertAvr = "update data_avarages set `eth_hourly_in`=${'ethAvrIn'.$i}, `eth_hourly_out`=${'ethAvrOut'.$i} where  `time`='$mysqldate 0$i:00:00'";
		$InsertQuery = mysqli_query($link, $InsertAvr);
		} else {
		$InsertAvr = "update data_avarages set `eth_hourly_in`=${'ethAvrIn'.$i}, `eth_hourly_out`=${'ethAvrOut'.$i} where `time`='$mysqldate $i:00:00'";
		$InsertQuery = mysqli_query($link, $InsertAvr);
		}
	}

	//Ethernet Day Avarages
	//UDP Day Avarage
	$CheckETHDataDay = "select * from data where time between '$mysqldate 00:00:00' and '$mysqldate 23:59:59' ORDER by time DESC  LIMIT 1440";
	$CheckETHQueryDay = mysqli_query($link, $CheckETHDataDay);	
	for ($i=0; $i  < mysqli_num_rows($CheckETHQueryDay); $i++) {
			$CurrentETHRowDay =  mysqli_fetch_array($CheckETHQueryDay);
			$totalETHDayIn += $CurrentETHRowDay['eth_input_kb_avg'];
			$totalETHDayOut += $CurrentETHRowDay['eth_kb_output_avg'];
		}
	$ETHdayTotalIn = $totalETHDayIn / 1440;
	$ETHdayTotalOut = $totalETHDayOut / 1440;
	
	$InsertAvrDay = "UPDATE data_avarages_day Set `eth_day_in`= $ETHdayTotalIn ,`eth_day_out` = $ETHdayTotalOut  where id='$mysqldate'";
	$InsertQueryDay = mysqli_query($link, $InsertAvrDay);

?>
