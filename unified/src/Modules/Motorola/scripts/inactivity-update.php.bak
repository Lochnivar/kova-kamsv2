<?php
include '/var/www/html/channels/php-mysql/connection-channels.php';
date_default_timezone_set('America/New_York');


$quantity = $_GET["quantity"];
$values = $_GET["values"];
$name = $_GET["name"];

if ($values == "Delete") {
	$sql = "DELETE FROM channel_data WHERE channel_name = '" . $name . "'";
	$result = mysqli_query($link, $sql);
} else {
	//Deal with Emails -- make sure Table contains id 1 or it wont update
	$EmailQuery = "UPDATE channel_data SET timeout_number = '$quantity',timeout_value = '$values' WHERE channel_name = '$name'";
	$EmailResult = mysqli_query($link, $EmailQuery);
}
//echo $EmailQuery;
//

if (mysqli_error($link) != '') {
	echo "<h1><center>" . mysqli_error($link) . "</center></h1>";
} else {
	echo "<h1><center>Updating Timeout</center><h1>";
}

header("Refresh:1;url=../admin.php");
