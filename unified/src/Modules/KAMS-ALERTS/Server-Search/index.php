<?php
include  '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');
$ip = $_POST["ip"];

?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
	<META HTTP-EQUIV="refresh" CONTENT="300">
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
	$( function() {
    $( "#datepicker" ).datepicker();
	} );
	
	</script>
    <title>KEANS Logs</title>
  </head>
  <body style="background-color:#D8D8D8;">
  <div>
  <ul class="nav nav-pills nav-justified">
	<li class="active"><a href="../index.php"><h3 style="margin:10px;background-color:#372E76;color:white;border-style:solid;padding:5px;border-radius:7px;font-family:Bedrock">MAIN PAGE<h3></a></li>
  </ul>
</div>
	<hr>
	<form action="./searched.php" method="POST">
	<div class="form-group oneThird" style="width:250px;margin:25px">
		 <label >Start Date</label>
		 <input type="date" name="startday" max="3000-12-31" 
				min="1000-01-01" class="form-control">
	</div>
	<div class="form-group oneThird" style="width:250px;margin:25px">
		 <label >End Date</label>
		 <input type="date" name="endday" min="1000-01-01"
				max="3000-12-31" class="form-control">
				<br>
		<center><input type="submit" value="Submit Search Dates"></center>
	</div>
	<input type="hidden" name="ip" value="<?php echo $ip; ?>" />
	</form>
	<div class="form-group oneThird" style="width:450px;margin:25px">
		<img src="../KAMS.jpeg" style="width:400px;height:200px;float:left">
	</div>
	

	<div>
	<table class="table table-hover table-bordered">
	  <thead>
	    <thead class="thead-dark">
		<tr>
		  <th scope="col">Alarm ID</th>
		  <th scope="col">Description</th>
		  <th scope="col">Last 25 Alarms</th>
		  <th scope="col">Acknowledged BY</th>
		  <th scope="col">Acknowledged Time</th>
		</tr>
	  </thead>
	  <tbody>
	<?php
	$lastLogs = "select * from History WHERE server_id='$ip' ORDER BY id DESC LIMIT 25 ";
	$UpdatelastLogs = mysqli_query($link, $lastLogs);
	while ($row = mysqli_fetch_array($UpdatelastLogs))  
	{		
		echo "<tr>";
		  echo "<th scope='row'>".$row['event_alarm']."</th>";
		  echo "<th scope='row'>".$row['description']."</th>";
		  echo "<th scope='row'>".date("Y-m-d h:i:s a",$row['event_time'])."</th>";
		  echo "<th scope='row'>".$row['ackName']."</th>";
	          if ($row['ackTime'] != ""){
		  echo "<th scope='row'>".date("Y-m-d h:i:s a",$row['ackTime'])."</th>";
		  } else {
		  echo "<th scope='row'></th>";
		  }
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
