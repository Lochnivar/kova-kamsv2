<?php
$link = mysqli_connect("127.0.0.1", "serial", "serial7906", "Serial_Users");  
        if (mysqli_connect_error()) { 
		echo mysqli_error();		
        die ("Database Connection Error");   
        }

	$NewSerialSizeCount = $_GET['SerialSizeRowCount'];
	$PageSettingsInsert = "update settings SET graph_time = $NewSerialSizeCount";
	$PageSettingsInsert = mysqli_query($link, $PageSettingsInsert);
	header('Location: ./index.php');




?>

