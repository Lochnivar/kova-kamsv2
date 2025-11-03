<?php 
$lines = file('/usr/src/data.txt');
$i=0;
$InputTotal = 0;
$OutputTotal = 0;
$InputKB = 0;
$OutputKB = 0;
foreach ($lines as $line_num => $line) {
	$pieces = explode(" ", $line);
		if ($pieces[0] = 'Averages'){
			$Reduced = array_values(array_filter($pieces));
				//print_r($Reduced);
				if ($Reduced[2] = 'enp2s0'){
						
							if (array_key_exists(3, $Reduced)) {
								$filterNums = $Reduced['3'];
							}
						
						if (is_numeric($filterNums)) { 
						//print_r($Reduced);
								if (array_key_exists(3, $Reduced)) {
									$InputTotal += $Reduced[3];	
								}
									if (array_key_exists(4, $Reduced)) {
									$OutputTotal += $Reduced[4];
								}
									if (array_key_exists(5, $Reduced)) {
									$InputKB += $Reduced[5];
								}
									if (array_key_exists(6, $Reduced)) {
									$OutputKB += $Reduced[6];
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
	$insertData = " insert into data (eth_input_avg, eth_input_kb_avg, eth_output_avg, eth_kb_output_avg) Values ($InputKB,$InputTotal, $OutputKB , $OutputTotal)";
	$insertQuery = mysqli_query($link, $insertData);
	

?>
