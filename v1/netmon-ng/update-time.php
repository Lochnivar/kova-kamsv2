<?php
$link = mysqli_connect("localhost", "webpage", "K0v@W3b", "Network_Mon");   
        if (mysqli_connect_error()) { 
		echo mysqli_error();		
        die ("Database Connection Error");   
        }
		
	
	echo "Here ".$_GET['UDPRowCount'];
	$NewUDPCount = $_GET['UDPRowCount'];
	$PageSettingsInsert = "update settings SET graph_time = $NewUDPCount";
	$PageSettingsInsert = mysqli_query($link, $PageSettingsInsert);
	header('Location: ./index.php');
	
?>
