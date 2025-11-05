<?php

$cargo = "";

$cargo .= <<<EOL
<table class='table'>           
	<tr>
		<td colspan = '3'>
			<div id="graph" style="height: 275px;"></div>
		</td>
	</tr>
	<tr>
    	<td class = "text-center">One Hour</td>
		<td class = "text-center">Thirty Minutes</td>
		<td class = "text-center">Ten Minutes</td>
	</tr>
	<tr>
		<td class = "text-center">
			<span id = "UDPAVG-hour"></span> ppm
		</td>
    	<td class = "text-center">
			<span id = "UDPAVG-thirty"></span> ppm
		</td>
    	<td class = "text-center">
			<span id = "UDPAVG-ten"></span> ppm
		</td>
	</tr>
	<tr>
    	<td colspan = "3" class = "text-center">Last Packet Time</td>
	</tr>
	<tr>
		<td colspan = "3" class = "text-center">
			<span id = "UDPAVG-lastTime"></span>
		</td>
	</tr>
</table>

EOL;

echo $cargo;
