<?php
include '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');


$alarm_name = $_POST["alarm_name"];
$alarm_description = $_POST["description"];
$alarm_id = $_POST["id"];
$alarm_oid = $_POST["alarm-oid"];
$alarm_severity = $_POST['alarm-severity'];




?>
<form style="border:2px solid black;padding:5px" action="alarm-mysql-update.php" method="POST">
  <div class="form-group" >
    <label for="alarm-name"><h3>Edit Alarm</h3></label>
    <input type="text" class="form-control" id="alarm-name" name="alarm_name" aria-describedby="alarm-name" value="<?php echo $alarm_name;?>">
	<input type="hidden" name="id" value="<?php echo $alarm_id; ?>" />
  </div>
  <div class="form-group">
    <input type="text" class="form-control" id="alarm-description" name="alarm_description" value="<?php echo $alarm_description;?>">
  </div>
  <div class="form-group">
    <input type="text" class="form-control" id="alarm-oid" name="alarm_oid" value="<?php echo $alarm_oid;?>">
  </div>
  <div class="form-group">
  <select name="alarm_severity" id="alarm-severity">
  <?php if($alarm_severity == 'major'){ ?>
  <option value="major">major</option>
  <option value="minor">minor</option>
  <?php } else { ?>
  <option value="minor">minor</option>
  <option value="major">major</option>  
  <?php } ?>
</select>
</div>
<input type="checkbox" class="custom-control-input" id="delete" name="delete">
    <label class="custom-control-label" for="defaultUnchecked">Delete</label>
	<br>
  </div>
  
  <button type="submit" class="btn btn-primary">Update</button>
</form>

		
