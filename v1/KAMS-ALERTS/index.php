<?php
include '../mysql-connections/connection.php';
date_default_timezone_set('America/New_York');
$epoch = time();
$readable_time = date("Y-m-d h:i:s a",$epoch);				

?>
<html>
<head>
<meta http-equiv="refresh" content="600">
</head>
<body style="margin:5;padding:5;background-color:#F2F2F2;">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
  <style>
      .blink {
        animation: blinker 1.5s linear infinite;
        color: #FFFFFF;
        font-size: 20px;
        font-weight: bold;
        font-family: sans-serif;
      }
      @keyframes blinker {
        70% {
          opacity: .30;
        }
      }
      .blink-one {
        animation: blinker-one 1s linear infinite;
      }
      @keyframes blinker-one {
        0% {
          opacity: 0;
        }
      }
      .blink-two {
        animation: blinker-two 1.4s linear infinite;
      }
      @keyframes blinker-two {
        100% {
          opacity: 0;
        }
      }
    </style>
<div>
<center><img src="KAMS.jpeg" style="width:90%;height:20%;"></center>
<hr>   
<center><h2>Last Refresh Time: <?php echo $readable_time; ?></center>
</div>


<div>
<!-- Table  -->
<table class="table table-bordered">
  <!-- Table head -->
  <thead>
    <tr>
     
      <th>Server Name</th>
	  <th>Type</th>
      <th>IP</th>
      <th>Last Alarm - Click for History</th>
	  <th>Acknowledged</th>
	  <th>Silenced Since -- CLICK TO ENABLE</th>
    </tr>
  </thead>
  <!-- Table head -->

  <!-- Table body -->
  <tbody>
  
 <?php  $query = "SELECT * FROM Servers ORDER BY name ASC";
		$result = mysqli_query($link, $query);
		while($row = mysqli_fetch_array($result)) {
			$TimeLimit = (time() - 129600);
			$MajorQuery = "SELECT * FROM History WHERE severity = 'major' and event_time >= '$TimeLimit' and server_id = '$row[server_ip]' and ackTime IS NULL ORDER BY id DESC LIMIT 1;";
			//echo $MajorQuery;
			$MajorMYSQL = mysqli_query($link, $MajorQuery);
			$MajorResult = mysqli_fetch_array($MajorMYSQL);
			//print_r($MajorResult);
			
			if($MajorResult['event_time'] != ''){
			$AlarmName = $MajorResult['description'];
			$AlarmSeverity = $MajorResult['severity'];
			} else {
			$AlarmName = $row['last_alarm_id'];
			$AlarmSeverity = $row['severity'];
			}
			
			
	?>
			<tr>
			  <td>
			  <form action="./scripts/servers-edit.php" method="POST">
					<button class="btn btn-primary" type="submit" name="server_name" value="<?php echo $row['name']; ?>"><?php echo $row['name']; ?></button>
					<input type="hidden" name="id" value="<?php echo $row['id']; ?>" />
					<input type="hidden" name="ip" value="<?php echo $row['server_ip']; ?>" />
			</form>
			</td>
			  <td><?php echo $row['system_type']; ?></td>
			  <td><?php echo $row['server_ip']; ?></td>
			 <td>
			  <!-- Last Alarm Field -->
			 <form action="server-history.php" method="POST">
					<input type="hidden" name="ip" value="<?php echo $row['server_ip']; ?>" />
					<?php 
					if ($row['last_alarm_time'] != ''){
						$epoch = $row['last_alarm_time'];
						$readable_time = date("Y-m-d h:i:s a",$epoch);
						
					if ($epoch >= (time() - 129600) && $row['acknowledge'] == '0' && $AlarmSeverity == 'major'){
					?><button class="btn btn-danger blink" type="submit" name="server_name" value="<?php echo $row['name']; ?>">
					<?php
						echo $readable_time." - ".$AlarmName; 
							}
					if ($epoch >=(time() - 129600) && $row['acknowledge'] != '0' && $AlarmSeverity == 'major'){
						?><button class="btn btn-danger" type="submit" name="server_name" value="<?php echo $row['name']; ?>">
						<?php
						echo $readable_time." - ".$AlarmName; 
						}
						
					if ($epoch >= (time() - 129600) && $row['acknowledge'] == '0' && $AlarmSeverity == 'minor'){
					?><button class="btn btn-warning blink" type="submit" name="server_name" value="<?php echo $row['name']; ?>">
					<?php
					echo $readable_time." - ".$AlarmName;}
					if ($epoch >= (time() - 129600) && $row['acknowledge'] != '0' && $AlarmSeverity == 'minor'){
						?><button class="btn btn-warning" type="submit" name="server_name" value="<?php $row['name']; ?>">
						<?php
						echo $readable_time." - ".$AlarmName; 
						}
					} 

					if ($epoch < (time() - 129600)){
					?>
					<button class="btn btn-info" type="submit" name="server_name" value="<?php echo $row['name']; ?>">
					<?php
						echo "No Recent Alarms"; 
					}					
					?></button>
					
			</form>
			</td>
			<!--Acknowledge Field -->
			<td>
			<form action="scripts/acknowledge-name.php" method="POST">
					<input type="hidden" name="ip" value="<?php echo $row['server_ip']; ?>" />
					<?php 			
					if ($row['acknowledge'] == '0' && $AlarmSeverity == 'major'){
					?><button class="btn btn-danger blink" type="submit" name="server_name" value="<?php echo $row['name']; ?>">
					<?php 
					echo "CLICK TO ACKNOWLEDGE"; 
							} 
					if ($row['ackName'] != '' && $row['acknowledge'] == '1'){
						echo date("Y-m-d h:i:s a",$row['ackTime'])." by ".$row['ackName']; 
						}
					if ($row['ackName'] == ''){
						echo ""; 	
					}
					if ($row['acknowledge'] == '0' && $AlarmSeverity == 'minor'){
					?><button class="btn btn-warning blink" type="submit" name="server_name" value="<?php echo $row['name']; ?>">
					<?php
						echo "CLICK TO ACKNOWLEDGE"; 
							} 
					/*if ($row['ackName'] != '' && $row['acknowledge'] == '1'){
						echo date("Y-m-d h:i:s a",$row['ackTime'])." by ".$row['ackName']; 
						}
					if ($row['ackName'] == ''){
						echo ""; 
						}
					*/
				
					?></button>
					
			</form>
			</td>
			
			<?php 
			//Monitoring Field
                   if($row['monitored_alarms'] != ""){	
			if($row['silenced'] == '0'){
				echo "<td style='background-color:green'>";
				echo "<center>CURRENTLY MONITORING</center>";
			} elseif($row['silenced_alarms'] == '') {
				echo "<td style='background-color:red'>";
				?>
				<center><form action="./scripts/disable-silence.php" method="POST">

				  <button type="submit" class="btn btn-warning  
				  "id="enable" name="enable" value="<?php echo $row['id']; ?>"><?php echo "<h4> ALL Notifications Silenced Since <br>".$row['silenced'] ?></h4></button>

				</form></center>
			<?php
			} else {
				echo "<td style='background-color:red'>";
				?>
				<center><form action="./scripts/servers-edit.php" method="POST">

				  <button type="submit" class="btn btn-warning  
				  "id="enable" name="enable" value="<?php echo $row['id']; ?>"><?php echo "<h4>".substr(str_replace("-","",$row['silenced_alarms']),0,-1)." silenced since <br>".$row['silenced'] ?></h4></button>
				 
				    <input type="hidden" name="server_name" value="<?php echo $row['name']; ?>" />
					<input type="hidden" name="id" value="<?php echo $row['id']; ?>" />
					<input type="hidden" name="ip" value="<?php echo $row['server_ip']; ?>" />
				</form></center>
			<?php
			}
	} else {
	echo "<td style='background-color:blue'><center>NOT MONITORING ANY ALARMS</center></td>";

	}
			?>
			</tr>

	<?php 	}
		
?>



   
  </tbody>
  <!-- Table body -->
</table>
<!-- Table  -->
</div>

<div> 
<h5>KEY<h5><button class="btn btn-danger">Major Alarm</button> <button class="btn btn-warning">Minor Alarm</button> <button  class="btn btn-info">No Alarms</button>


</div>

<!-- Add server -->

<form style="border:2px solid black;padding:5px" action="./scripts/servers-add.php" method="POST">
  <div class="form-group" >
    <label for="server-name"><h3>Add New Server</h3></label>
    <input type="text" class="form-control" id="server-name" name="server-name" aria-describedby="server-name" placeholder="Server Name">
  </div>
  <div class="form-group">
    <input type="text" class="form-control" id="server-ip" name="server-ip" placeholder="Server IP">
  </div>

  <div class="dropdown">
  <label for="system_type">System:</label>

<select name="system_type" id="system">
  <option value="Audiolog">Audiolog</option>
  <option value="Eventide">Eventide</option>
  <option value="Verint">Verint</option>
</select>
</div>
<br>
  
  </div>
  <button type="submit" class="btn btn-primary">Add</button>
</form>

<button type=“button”><a href='./alarms-admin.php'>Alarm Admin Page</a></button>

</body>
</html>

