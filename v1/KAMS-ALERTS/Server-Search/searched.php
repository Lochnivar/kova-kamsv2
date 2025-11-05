<?php
include  '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');
$TimeStart = strtotime($_POST["startday"]."00:00:00");
$TimeEnd = strtotime($_POST["endday"]."23:59:59");
$ip = $_POST["ip"];
?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
	<META HTTP-EQUIV="refresh" CONTENT="300;URL='index.php'">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<style>
	.oneThird {
		  width: 33%;
		  float: left;
		  }
		 .oneThird img {width: 100%;}
		 .oneThird p {padding: 3px;}
	</style>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="./css/bootstrap-4.0.0-dist/css/bootstrap.min.css">
	<script type="text/javascript" src="./css/jquery-3.3.1.min.js"></script>

	<script>

	</script>
    <title>KEANS Logs</title>
  </head>
  <body style="background-color:#D8D8D8;">
  <div>
  <ul class="nav nav-pills nav-justified">
	<li class="active"><a href="../index.php"><h3 style="margin:10px;background-color:#372E76;color:white;border-style:solid;padding:5px;border-radius:7px;font-family:Bedrock">MAIN PAGE<h3></a></li>
<form  action="./index.php" method="POST" >
		<h3><button type="submit" style="margin:10px;background-color:#372E76;color:white;border-style:solid;padding:5px;border-radius:7px;font-family:Bedrock">Search Page</button></h3>
		<input type="hidden" name="ip" value="<?php echo $ip; ?>" />
</form>
  </ul>
</div>
	<hr>
	<div class="form-group oneThird" style="width:300px;margin:50px">
	<br>
	<br>
	</div>
	<div class="form-group oneThird" style="width:400px;margin:50px">
		<img src="../KAMS.jpeg" style="width:400px;height:200px;float:left">
	</div>

	

	<div>
	<table class="table table-hover table-bordered">
	  <thead>
	    <thead class="thead-dark">
		<tr>
		  <th scope="col">Alarm ID</th>
		  <th scope="col">Description</th>
		  <th scope="col">Time</th>
		</tr>
	  </thead>
	  <tbody>
	<?php
	$lastLogs = "select event_time,event_alarm,description from History WHERE server_id='$ip' AND event_time BETWEEN ".$TimeStart." AND ".$TimeEnd." ORDER BY id DESC";
	$UpdatelastLogs = mysqli_query($link, $lastLogs);
	if(mysqli_num_rows($UpdatelastLogs)!=0){
	while ($row = mysqli_fetch_array($UpdatelastLogs))  
	{		
		echo "<tr>";
		  //echo "<th scope='row'>".$row['originator']."</th>";
		  echo "<td>".$row['event_alarm']."</td>";
		  echo "<td>".$row['description']."</td>";
		  echo "<th scope='row'>".date("Y-m-d h:i:s a",$row['event_time'])."</th>";
		echo "</tr>";
	}
	} else {
	echo "<tr>";
		  // "<th scope='row'></th>";
		  echo "<td></td>";
		  echo "<td></td>";
		  echo "<td>NO ALARMS FOR THAT TIME RANGE</td>";
		echo "</tr>";
	}
	?>
	</tbody>
	</table>
	</div>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <link rel="stylesheet" href="./css/bootstrap-4.0.0-dist/css/bootstrap.min.css">
	<script type="text/javascript" src="./css/jquery-3.3.1.min.js"></script>
  </body>
</html>
