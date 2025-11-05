<?php
include '/usr/src/KAMS-Setting-file.php';
date_default_timezone_set($TimeZone);

$time = time();
$Last7Days = $time - 604800; 
  $link = mysqli_connect('localhost', "kams", "kams7906", "zabbix");
        if (mysqli_connect_error()) {
            die ("Database Connection Error");
        }
 $query = "select
  h.host,
  e.clock,
  t.description,
  severity
from
  hosts h
  join items i on h.hostid=i.hostid
  join functions f on i.itemid=f.itemid
  join triggers t on f.triggerid=t.triggerid
  join events e on t.triggerid=e.objectid
where 
  e.clock >  ".$Last7Days."
order by
  e.clock desc
limit 25 
";
 $result = mysqli_query($link, $query);



$i = 0;
while($i < count($servers)){
    ${'query.$i'} = "select
  h.host,
  e.clock,
  t.description,
  r.r_clock,
  p.eventid,
  s.severity
from
  hosts h
  join items i on h.hostid=i.hostid
  join functions f on i.itemid=f.itemid
  join triggers t on f.triggerid=t.triggerid
  join events e on t.triggerid=e.objectid
  join problem r on e.clock=r.clock
  join problem p on e.clock=p.clock
  join problem s on e.clock=s.clock
where
host = '".$servers[$i]."' AND r.r_clock = '0'
order by
  e.clock desc";
  
    ${'result.$i'} = mysqli_query($link, ${'query.$i'});
    ${'row.$i'} = mysqli_fetch_array(${'result.$i'});

   $TimeDiff = $time - ${'row.$i'}['clock'];
   //echo $TimeDiff."<br>";
   if(${'row.$i'}['description'] != ''){
	 if(${'row.$i'}['severity']  == '0'){
	 $buttonColor = "green";
	 $hide = 'YES';
	 }
	 if(${'row.$i'}['severity']  == '1'){
	 $buttonColor = "blue";
	 $hide = 'YES';
	 }
	 if(${'row.$i'}['severity']  == '2'){
	 $buttonColor = "#999900";
	 $hide = 'YES';
	 }
	 if(${'row.$i'}['severity']  == '3'){
	 $buttonColor = "#BA4A00";
	 $hide = 'YES';
	 }
	 if(${'row.$i'}['severity']  == '4'){
	 $buttonColor = "#ff0000";
	 $hide = 'NO';
	 }
	 if(${'row.$i'}['severity']  == '5'){
	 $buttonColor = "#8b0000";
	 $hide = 'NO';
	 }
		if($hide == 'YES'){
		 ?>
			<a href='./scripts/server-history.php?server="<?php echo $servers[$i]  ?>"' ><button type='button' class='btn btn-success btn-lg' float='left' style='margin:3px;width:300px;height:90px;font-size: 80%;line-height: 1.6;'><?php echo $servers[$i]  ?><br>Server is Okay<br></button></button></a>
		 <?php 
		} else { 
	 ?>
         <a href='./scripts/server-history.php?server="<?php echo $servers[$i]  ?>"' ><button type='button' class='btn  btn-lg' float='left' style='width:300px;height:90px;font-size: 80%;line-height: 1.6;margin:3px;background-color:<?php echo $buttonColor ?>'><?php echo $servers[$i]  ?><br>Severity - <?php echo ${'row.$i'}['severity'] ?><br><?php 
		  if(strlen(${'row.$i'}['description']) < 35){
		 echo ${'row.$i'}['description'];
		 } else {
		  echo substr(${'row.$i'}['description'],0,35).'...';
		 }
		 
		 ?></button></a>
   <?php
    } 

  } else {
	?>
	  <a href='./scripts/server-history.php?server="<?php echo $servers[$i]  ?>"' ><button type='button' class='btn btn-success btn-lg' float='left' style='width:300px;height:90px;font-size: 80%;line-height: 1.6;margin:3px;'><?php echo $servers[$i]  ?><br>Server is Okay<br></button></button></a>
	<?php   
  }
  $i++;
}
?>
<hr>
<h1><center>Last 7 Days</center><h1>
<table style="width:100%">
  <tr>
    <th><h3>Server</h3></th>
    <th><h3>Time Stamp</h3></th>
    <th><h3>Message Description</h3></th>
	<th><h3>Severity</h3></th>
  </tr>

<?php

 while($row = mysqli_fetch_array($result)) {
 $epoch = $row['clock'];
 $ERRTIME = date('Y-m-d H:i:s', $epoch);
 
 $Restored = "";
		if($row['severity'] == '0'){
		$color = 'green';
		$Restored = 'Restored - ';
		}
		if($row['severity'] == '1'){
		$color = 'blue';
		}
		if($row['severity'] == '2'){
		$color = '#999900';
		}
		if($row['severity'] == '3'){
		$color = '#BA4A00';
		}
		if($row['severity'] == '4'){
		$color = '#ff0000';
		}
		if($row['severity'] == '5'){
		$color = '#8b0000';
		}
		if($row['severity'] == ''){
		$color = 'grey';
		}

          echo "<tr>";
		     if ($row['host'] == 'Zabbix server'){
              echo "<td><b><span style='color:".$color."'>Monitor Server</span></b></td>";
			 } else {
			  echo "<td><b><span style='color:".$color."'>".$row['host']."</span></b></td>";
			 }
              echo "<td><b><span style='color:".$color."'>".$ERRTIME."</span></b></td>";
              echo "<td><b><span style='color:".$color."'>".$Restored."".$row['description']."</span></b></td>";
			  echo "<td><b><span style='color:".$color."'>".$row['severity']."</span></b></td>";
         echo "</tr>";

 };
?>


</table>

<hr>
	<p><center>
	<h3>MESSAGE SEVERITY LEGEND</h3>
	</center></p>
	<p><center>
	<a href='./scripts/severity.php?level=0' ><button type='button' class='btn  btn' float='left' style='width:160px;background-color:green'>Severity 0<br>RESOLVED</button></a>
	<a href='./scripts/severity.php?level=1' ><button type='button' class='btn  btn' float='left' style='width:160px;background-color:blue'>Severity 1<br>INFORMATIONAL</button></a>
	<a href='./scripts/severity.php?level=2' ><button type='button' class='btn  btn' float='left' style='width:160px;background-color:#999900'>Severity 2<br>WARNING</button></a>
	<a href='./scripts/severity.php?level=3' ><button type='button' class='btn  btn' float='left' style='width:160px;background-color:#BA4A00'>Severity 3<br>AVERAGE PROBLEM</button></a>
	<a href='./scripts/severity.php?level=4' ><button type='button' class='btn  btn' float='left' style='width:160px;background-color:#ff0000'>Severity 4<br>HIGH</button></a>
	<a href='./scripts/severity.php?level=5' ><button type='button' class='btn  btn' float='left' style='width:160px;background-color:#8b0000'>Severity 5<br>CRITICAL</button></a>
	</center></p>
