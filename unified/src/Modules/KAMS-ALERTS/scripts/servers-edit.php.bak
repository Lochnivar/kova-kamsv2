<?php
include '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');


$server_name = $_POST["server_name"];
$server_description = $_POST["description"];
$server_ip = $_POST["ip"];
$serverid = $_POST["id"];

$ServerQuery = "SELECT monitored_alarms,silenced,silenced_alarms,system_type FROM Servers WHERE id=$serverid";
$ServerResult = mysqli_query($link, $ServerQuery);
$ServerRow = mysqli_fetch_array($ServerResult);
$ServerSystemType = $ServerRow['system_type'];

header ("Refresh:300;url=../index.php");
?>
<html>
<head>
</head>
<body style="margin:5;padding:5;background-color:#F2F2F2;">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>

<div style="padding: 5px 0 0 5px;height:295px;width:100%;position:fixed;left:0;top:0;z-index:10;
    background:gray;"> 
<form style="border:2px solid black;padding:5px" action="servers-mysql-update.php" method="POST">
  <div class="form-group" >
    <label for="server-name"><h3>Edit server Info</h3></label>
    <input type="text" class="form-control" id="server-name" name="server_name" aria-describedby="server-name" value="<?php echo $server_name;?>">
	<input type="hidden" name="id" value="<?php echo $serverid; ?>" />
  </div>
  <div class="form-group">
    <input type="text" class="form-control" id="server-description" name="server_ip" value="<?php echo $server_ip;?>">
  </div>
  

<div class="dropdown">
  <label for="system_type">System:</label>

<select name="system_type" id="system">
<?php if($ServerRow['system_type'] == 'Audiolog'){ ?>
  <option value="Audiolog" selected="selected">Audiolog</option>
  <option value="Eventide">Eventide</option>
  <option value="V15">V15</option>
<?php
}
if($ServerRow['system_type'] == 'Eventide'){
	?>
  <option value="Audiolog">Audiolog</option>
  <option value="Eventide" selected="selected">Eventide</option>
  <option value="V15">V15</option>
<?php
}
if($ServerRow['system_type'] == 'V15'){
	?>
  <option value="Audiolog" >Audiolog</option>
  <option value="Eventide">Eventide</option>
  <option value="V15" selected="selected">V15</option>
<?php
}
?>
</select>
</div>
  
  <div class="custom-control custom-checkbox">
<input type="checkbox" class="custom-control-input" id="checkDelete" name="delete">
    <label class="custom-control-label" for="checkDelete">Delete</label>
	</div>
	<br>

	<?php if($ServerRow['silenced'] == 0){
	?>
    <button type="submit" class="btn btn-danger" name="silence" value="1">Silence All Alarms</button>
	<?php
	} 
	?>
  <button type="submit" class="btn btn-primary">Update Server Info and Alarms</button>

</div>


<hr>

<div style="padding: 260px 0 0 5px; z-index:5 ;overflow:auto;">


  <div class="form-group" >
    <label for="server-name"><h3>Edit Alarms</h3></label>
   <div>
<!-- Table  -->
<table class="table table-bordered">
  <!-- Table head -->
  <thead>
    <tr>
     
      <th>Alarm Name</th>
	  <th>Description</th>
	  <th>OID</>
      <th>Monitored</th>
      <!-- <th>Silence</th> -->
    </tr>
  </thead>
  <!-- Table head -->

  <!-- Table body -->
  <tbody>
  
 <?php  
		$monitoredKey = explode('-,',$ServerRow['monitored_alarms']);
		$silencedKey = explode('-,',$ServerRow['silenced_alarms']);
		$ignoreKey = array('server_ip','id','delete','server_name','silence-');
		array_push($ignoreKey,$value);
				
		//Silenced Rows
		foreach($silencedKey as $value){
		array_push($ignoreKey,$value);
		$SilQuery = "SELECT * FROM Alarms where alarm_name = '$value'";
		$SilResult = mysqli_query($link, $SilQuery);
		$row = mysqli_fetch_array($SilResult);
		if($row != ""){
		?>
			<tr>
			 <td style="background-color:red"><?php echo $row['alarm_name']; ?></td>
			  <td style="background-color:red"><?php echo $row['description']; ?></td>
			  <td style="background-color:red"><?php echo $row['OID']; ?></td>
			  <td style="background-color:red">
			  <!-- Default unchecked -->
					<div class="custom-control custom-radio">
					  <input type="radio" class="custom-control-input" id="<?php echo $row['alarm_id']."Yes"; ?>" name="<?php echo $row['alarm_name']; ?>" value="1">
					  <label class="custom-control-label" for="<?php echo $row['alarm_id']."Yes"; ?>">Yes</label>
					</div>

					<!-- Default checked -->
					<div class="custom-control custom-radio">
					  <input type="radio" class="custom-control-input" id="<?php echo $row['alarm_id']."No"; ?>" name="<?php echo $row['alarm_name']; ?>" value="0">
					  <label class="custom-control-label" for="<?php echo $row['alarm_id']."No"; ?>">No</label>
					</div>
						<div class="custom-control custom-radio">
					  <input type="radio" class="custom-control-input" id="<?php echo $row['alarm_id']."Silence"; ?>" name="<?php echo $row['alarm_name']; ?>" value="2" checked>
					  <label class="custom-control-label" for="<?php echo $row['alarm_id']."Silence"; ?>">Silenced</label>
					</div>
					</span>
		</tr>
		<?php
		
		 }
		}
		
		//Monitored Rows
		foreach($monitoredKey as $value){
		array_push($ignoreKey,$value);
		$query = "SELECT * FROM Alarms where alarm_name = '$value'";
		$Queryresult = mysqli_query($link, $query);
		$result = mysqli_fetch_array($Queryresult);
		if($result != "" && !in_array($value,$silencedKey)){
		?>
		<tr>
			  <td style="background-color:yellow"><?php echo $result['alarm_name']; ?></td>
			  <td style="background-color:yellow"><?php echo $result['description']; ?></td>
			  <td style="background-color:yellow"><?php echo $result['OID']; ?></td>
			  <td style="background-color:yellow">
					<!-- Default unchecked -->
					<div class="custom-control custom-radio">
					  <input type="radio" class="custom-control-input" id="<?php echo $result['alarm_id']."Yes"; ?>" name="<?php echo $result['alarm_name']; ?>" value="1" checked>
					  <label class="custom-control-label" for="<?php echo $result['alarm_id']."Yes"; ?>">Yes</label>
					</div>

					<!-- Default checked -->
					<div class="custom-control custom-radio">
					  <input type="radio" class="custom-control-input" id="<?php echo $result['alarm_id']."No"; ?>" name="<?php echo $result['alarm_name']; ?>" value="0">
					  <label class="custom-control-label" for="<?php echo $result['alarm_id']."No"; ?>">No</label>
					</div>
					
					<div class="custom-control custom-radio">
					  <input type="radio" class="custom-control-input" id="<?php echo $result['alarm_id']."Silence"; ?>" name="<?php echo $result['alarm_name']; ?>" value="2">
					  <label class="custom-control-label" for="<?php echo $result['alarm_id']."Silence"; ?>">Silence</label>
					</div>
					</span>
		</tr>
		<?php
		 }
		}
		
		//print_r($ignoreKey);
		
		

 
		$query = "SELECT * FROM Alarms WHERE system_type= '$ServerSystemType'";
		$result = mysqli_query($link, $query);
			
		while($row = mysqli_fetch_array($result)) {
			$AlarmName = $row['alarm_name'];

			//echo $AlarmName." is it in ".$ServerRow['monitored_alarms']."<br>";
						
			
	
			if(!in_array($AlarmName,$ignoreKey)){
			?>
				<tr>
					<td><?php echo $row['alarm_name']; ?></td>
					 <td><?php echo $row['description']; ?></td>
					 <td><?php echo $row['OID']; ?></td>
					 <td>
				  <!-- Default unchecked -->
					<div class="custom-control custom-radio">
					  <input type="radio" class="custom-control-input" id="<?php echo $row['alarm_id']."Yes"; ?>" name="<?php echo $row['alarm_name']; ?>" value="1">
					  <label class="custom-control-label" for="<?php echo $row['alarm_id']."Yes"; ?>">Yes</label>
					</div>

					<!-- Default checked -->
					<div class="custom-control custom-radio">
					  <input type="radio" class="custom-control-input" id="<?php echo $row['alarm_id']."No"; ?>" name="<?php echo $row['alarm_name']; ?>" value="0" checked>
					  <label class="custom-control-label" for="<?php echo $row['alarm_id']."No"; ?>">No</label>
					</div>
					
					<div class="custom-control custom-radio">
					  <input type="radio" class="custom-control-input" id="<?php echo $row['alarm_id']."Silence"; ?>" name="<?php echo $row['alarm_name']; ?>" value="2">
					  <label class="custom-control-label" for="<?php echo $row['alarm_id']."Silence"; ?>">Silence</label>
					</div>
				 </td>

			</tr>
				<?php
				}
				
				 ?>
					
			

	<?php 	}

?>
</tbody>
</div>
</form>
</html>

