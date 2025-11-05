<?php
include '/var/www/html/mysql-connections/connection.php';
date_default_timezone_set('America/New_York');


$Emails = $_POST["email"];
$relay = $_POST["relay"];
$from = $_POST["from"];
$subject = $_POST["subject"];


//Deal with Emails -- make sure Table contains id 1 or it wont update
$EmailQuery = "UPDATE emails SET addresses = '$Emails',smtpServer = '$relay',fromAddress = '$from',emailSubject = '$subject' WHERE id = '1'";
$EmailResult = mysqli_query($link, $EmailQuery);

//echo $EmailQuery;

if (mysqli_error($link) != ''){
	echo "<h1><center>".mysqli_error($link)."</center></h1>";
}else{
echo "<h1><center>Editing Emails</center><h1>";
}

header ("Refresh:1;url=../index.php");
?>
