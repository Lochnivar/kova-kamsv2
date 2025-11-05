<?php 
//For UDP Traffic
$lines = file('/usr/src/udp-data.txt');
$i=0;
$UDPidgm = 0;
$UDPodgm = 0;
$UDPnoport = 0;
$UDPOutputKB = 0;
foreach ($lines as $line_num => $line) {
	$pieces = explode(" ", $line);
		if ($pieces['0'] = 'Averages'){
			$UDPReduced = array_values(array_filter($pieces));
				//print_r($Reduced);
				if ($UDPReduced['2'] = 'enp2s0'){
						if (array_key_exists(3, $UDPReduced)) {
								$filterNums = $UDPReduced['3'];
							}
						
						if (is_numeric($filterNums)) { 
						//print_r($UDPReduced);
							if (array_key_exists(3, $UDPReduced)) {
									$UDPidgm += $UDPReduced['3'];	
								}
								if (array_key_exists(4, $UDPReduced)) {
									$UDPodgm += $UDPReduced['4'];
								}
										if (array_key_exists(5, $UDPReduced)) {
									$UDPnoport += $UDPReduced['5'];
								}
						
												
				}
			}	
			
		//print_r($lines);
		}
		
		
		$i++;

}	



	
	 
	$link = mysqli_connect("localhost", "webpage", "K0v@W3b", "Network_Mon");   
        if (mysqli_connect_error()) { 
		echo mysqli_error();		
        die ("Database Connection Error");   
       }
	$insertData = " insert into data_UDP (idgm, odgm, noport) Values (($UDPidgm/51), ($UDPnoport/51), ($UDPodgm/51))";
	$insertQuery = mysqli_query($link, $insertData);	
	echo mysqli_error($link);

?>
