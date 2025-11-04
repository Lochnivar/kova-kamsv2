<?php
include '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');

$ServerQuery = "SELECT * FROM emails";
$ServerResult = mysqli_query($link, $ServerQuery);
$ServerRow = mysqli_fetch_array($ServerResult);

header ("Refresh:300;url=./index.php");		
?>

<html>
<head>
 <meta http-equiv="refresh" content="600">
</head>
<body style="margin:5;padding:5;background-color:#F2F2F2;">
<center><img src="KAMS.jpeg" style="width:90%;height:20%;"></center>
<hr>
<h2><center>Active Alarms Table</center></h2>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>

<hr>
<form style="border:2px solid black;padding:5px" action="./scripts/emails-mysql-update.php" method="POST">
<div class="form-group">
  <label for="email"><h3>Emails Settings</h3></label>
  <hr>
  Send TO -- Separate Emails with a Comma: [  ,  ]<input type="text" class="form-control" id="email" name="email" value="<?php echo $ServerRow['addresses']; ?>">
  SMTP Relay IP<input type="text" class="form-control" id="relay" name="relay" value="<?php echo $ServerRow['smtpServer']; ?>">
  From Address<input type="text" class="form-control" id="from" name="from" value="<?php echo $ServerRow['fromAddress']; ?>">
  Subject<input type="text" class="form-control" id="subject" name="subject" value="<?php echo $ServerRow['emailSubject']; ?>">
  <br>
  <button type="submit" class="btn btn-primary">Update Emails</button>
</div>
</form>
<hr>

<!-- Table  -->
<table class="table table-bordered">
  <!-- Table head -->
  <thead>
    <tr>
     
      <th>Alarm Name</th>
      <th>Description</th>
	  <th>System Type</th>
	  <th>OID</th>
	  <th>Severity</th>
    </tr>
  </thead>
  <!-- Table head -->

  <!-- Table body -->
  <tbody>
  
 <?php  $AlarmQuery = "SELECT * FROM Alarms";
		$AlarmResult = mysqli_query($link, $AlarmQuery);
		while($row = mysqli_fetch_array($AlarmResult)) {


			
			
	?>
			<tr>
			  <td>
			  <form action="./scripts/alarm-edit.php" method="POST">
					<button type="submit" class="btn btn-primary" name="alarm_name" value="<?php echo $row['alarm_name']; ?>"><?php echo $row['alarm_name']; ?></button>
					<input type="hidden" name="id" value="<?php echo $row['alarm_id']; ?>" />
					<input type="hidden" name="description" value="<?php echo $row['description']; ?>" />
					<input type="hidden" name="alarm-oid" value="<?php echo $row['OID']; ?>" />
					<input type="hidden" name="alarm-severity" value="<?php echo $row['severity']; ?>" />
			</form>
			</td>
			 
			  <td><?php echo $row['description']; ?></td>
			  <td><?php echo $row['system_type']; ?></td>
			  <td><?php echo $row['OID']; ?></td>
			  <td><?php echo $row['severity']; ?></td>
			  
			</tr>

	<?php 	}
		
?>
    
   
  </tbody>
  <!-- Table body -->
</table>
<!-- Table  -->

<hr>
<!-- Add Alarm -->

<form style="border:2px solid black;padding:5px" action="./scripts/alarm-add.php" method="POST">
  <div class="form-group" >
    <label for="alarm-name"><h3>Add New Alarm</h3></label>
    <input type="text" class="form-control" id="alarm-name" name="alarm-name" aria-describedby="alarm-name" placeholder="Alarm Name -- (No Spaces Allowed)">
  </div>
  <div class="form-group">
    <label for="alarm-description">Description</label>
    <input type="text" class="form-control" id="alarm-description" name="alarm-description" placeholder="Alarm Description">
	</div>
	 
  <div class="form-group">
    <label for="alarm-description">OID</label>
    <input type="text" class="form-control" id="alarm-oid" name="alarm-oid" placeholder="Alarm OID">
  </div>

<div class="dropdown">
  <label for="system_type">System:</label>

<select name="system_type" id="system">
  <option value="Audiolog">Audiolog</option>
  <option value="Eventide">Eventide</option>
  <option value="V15">V15</option>
</select>
</div>
<br>
  </div>
  <button type="submit" class="btn btn-primary">Add</button>
</form>

<button type=“button”><a href='./index.php'>Main Page</a></button>

</html>

