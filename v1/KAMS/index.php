<?php
include '/usr/src/KAMS-Setting-file.php';

if($MotorolaPage == "yes"){
header ("Location: ../channels/index.php");	
} else if($UDPMonitorPage == "yes"){
header ("Location: ../netmon/index.php");	
} else if($SerialMonitorPage == "yes"){
header ("Location: ../serial/index.php");	
} else if($ZabbixPage == "yes"){
header ("Location: ../Monitor/index.php");
}




?>
<!-- TOP NAV BAR FOR PAGE NAVIGATION --!>
	<div class="topnav">
	<?php if($MotorolaPage == "yes"){ ?>
	  <button style="background-color:yellow;border-radius: 6px;color:black"><a style="color:black;" class="active" href="../channels/index.php">Motorola-Channels</a></button>
	<?php } ?>
	<?php if($UDPMonitorPage == "yes"){ ?>
	  <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../netmon/index.php">UDP-Monitor</a></button>
	 <?php } ?>
	 <?php if($SerialMonitorPage == "yes"){ ?>
	  <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../serial/index.php">Serial-Monitor</a></button>
	 <?php } ?>
	<?php if($ZabbixPage == "yes"){ ?>
          <button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="../serial/index.php">Server-Monitor</a></button>
         <?php } ?>
	</div>
	<hr>
