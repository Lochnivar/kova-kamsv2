<?php
include '/usr/src/KAMS-Setting-file.php';
date_default_timezone_set($TimeZone);

$server =  $_GET['server'];

$time = time();
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
 join events
e on t.triggerid=e.objectid 
where host = ".$server." 
order by   e.clock desc 
limit 25;

";



 $result = mysqli_query($link, $query);
 $count=mysqli_num_rows($result);
if($count<1){
    echo "<br><br><h1><center><span style=color:green>No Errors Found for Server ".trim($server, '"')."</span></center></h1><p></p><p></p>"; 
}



//echo $query;
header( "refresh:600;url=../index.php" );


?>
<html>
<head>
<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

     <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../css/bootstrap-4.0.0-dist/css/bootstrap.min.css">
	<script type="text/javascript" src="../css/jquery-3.3.1.min.js"></script>
<style>
table, th, td {
  border: 1px solid black;
  border-collapse: collapse;
}
th {
  text-align: left;
}
</style>
</head>
 <body style="padding: 5px 5px 5px 5px;background-color:#dcdcdc;">
<hr>
<h1><center>Last 25 Messages - <?php echo trim($server, '"'); ?></center><h1>
<table style="width:100%">
  <tr>
    <th><h3>Server</h3></th>
    <th><h3>Time Stamp</h3></th>
    <th><h3>Message Description</h3></th>
	<th><h3>Severity</h3></th>
  </tr>
 <a href='../index.php' ><button type='button' class='btn btn'>Home Page</button></a>
<br>
<br>

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
		$color = '#BA4A00'; //orange
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
              echo "<td><b><span style='color:".$color."'>".$row['host']."</span></b></td>";
              echo "<td><b><span style='color:".$color."'>".$ERRTIME."</span></b></td>";
              echo "<td><b><span style='color:".$color."'>".$Restored."".$row['description']."</span></b></td>";
			  echo "<td><b><span style='color:".$color."'>".$row['severity']."</span></b></td>";
         echo "</tr>";

 };
?>
</table>
<!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <link rel="stylesheet" href="../css/bootstrap-4.0.0-dist/css/bootstrap.min.css">
  </body>
</html>
