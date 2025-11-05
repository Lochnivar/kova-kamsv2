<?php
include '/usr/src/KAMS-Setting-file.php';
date_default_timezone_set($TimeZone);
include  '/var/www/html/channels/php-mysql/connection-channels.php';

$channelsGet = "SELECT * from channel_data ORDER BY channel_name";
$result = mysqli_query($link, $channelsGet);


?>
<!doctype html>
<html lang="en">

<head>
	<!-- Required meta tags -->
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="./css/bootstrap-4.0.0-dist/css/bootstrap.min.css">
	<script type="text/javascript" src="./css/jquery-3.3.1.min.js"></script>

	<script>
	</script>
	<title>Channel Status</title>
</head>

<body>
	<h2>
		<center><a href="index.php">Live View Status Page</a></center>
	</h2>
	<div id="content">
	</div>

	<table class="table table-striped">
		<thead>
			<tr>
				<th scope="col">Channel ID</th>
				<th scope="col">Channel Name</th>
				<th scope="col">Last Alert</th>
				<th scope="col">Inactivity Time</th>
				<th scope="col">Inactivity Value</th>
				<th scope="col">Submit</th>
			</tr>
		</thead>
		<tbody>


			<?php
			while ($rowOne = mysqli_fetch_array($result)) {
			?>
				<form action="./scripts/inactivity-update.php" method="get">
					<tr>
						<td><?php echo $rowOne['channel_id']; ?> <input type="hidden" id="name" name="name" value="<?php echo $rowOne['channel_name']; ?>"></td>
						<td><?php echo $rowOne['channel_name']; ?></td>
						<?php if ($rowOne[3] != '') { ?>
							<td><?php echo date('Y-m-d h:i:s', $rowOne[3]); ?></td>
						<?php } else { ?>
							<td>No Activity</td>
						<?php } ?>
						<td>
							<label for="quantity">0 to 60:</label>
							<input type="number" id="quantity" name="quantity" min="0" max="60" value='<?php echo $rowOne['timeout_number']; ?>'>
						</td>
						<td>
							<div class="form-check">
								<?php if ($rowOne['timeout_value'] == "Hours") { ?>
									<input class="form-check-input" type="radio" name="values" id="values" value="Hours" checked>
								<?php } else { ?>
									<input class="form-check-input" type="radio" name="values" id="values" value="Hours">
								<?php } ?>
								<label class="form-check-label" for="exampleRadios1">
									Hours
								</label>
							</div>
							<div class="form-check">
								<?php if ($rowOne['timeout_value'] == "Days") { ?>
									<input class="form-check-input" type="radio" name="values" id="values" value="Days" checked>
								<?php } else { ?>
									<input class="form-check-input" type="radio" name="values" id="values" value="Days">
								<?php } ?>
								<label class="form-check-label" for="exampleRadios2">
									Days
								</label>
							</div>
							<div class="form-check">
								<?php if ($rowOne['timeout_value'] == "Weeks") { ?>
									<input class="form-check-input" type="radio" name="values" id="values" value="Weeks" checked>
								<?php } else { ?>
									<input class="form-check-input" type="radio" name="values" id="values" value="Weeks">
								<?php } ?>
								<label class="form-check-label" for="exampleRadios3">
									Weeks
								</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="radio" name="values" id="values" value="Delete">
								<label class="form-check-label" for="exampleRadios4">
									Delete Channel
								</label>
							</div>
						</td>
						<td>
							<button type="submit" class="btn btn-primary">Update <?php echo $rowOne['channel_name']; ?> </button>

						</td>
					</tr>
				</form>
			<?php
			}
			?>

		</tbody>
	</table>
	<hr>
	<!-- Add channel -->

	<form style="border:2px solid black;padding:5px" action="./scripts/channels-add.php" method="POST">
		<div class="form-group">
			<label for="channel-name">
				<h3>Add New Channel</h3>
			</label>
			<br>
			NAME<input type="text" class="form-control" id="channel-name" name="channel-name" aria-describedby="channel-name" placeholder="Channel Name">
		</div>
		<div class="form-group">
			ID<input type="text" class="form-control" id="channel-id" name="channel-id" placeholder="Channel ID">
		</div>
		<div class="form-group">
			TIME<input type="text" class="form-control" id="channel-time" name="channel-time" placeholder="Time 0 - 60">
		</div>

		<div class="dropdown">
			<label for="system_type">Time Frame:</label>

			<select name="time_type" id="time_type">
				<option value="Hours">Hours</option>
				<option value="Days">Days</option>
				<option value="Weeks">Weeks</option>
			</select>
		</div>
		<br>

		</div>
		<button type="submit" class="btn btn-primary">Add</button>
	</form>
</body>

</html>
