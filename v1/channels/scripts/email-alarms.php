<?php
//NOT USED in Crontab job, may use in future, requires systems that allow outbound SMTP 
include '/var/www/html/channels/php-mysql/connection-channels.php';
date_default_timezone_set('America/New_York');


$channelsGet = "SELECT * from channel_data WHERE timeout_number <> '0' ORDER BY last_activity DESC ,channel_id";
$result = mysqli_query($link, $channelsGet);
$ChannelIssues = '';
$issues = 0;
while ($rowOne = mysqli_fetch_array($result)) {
	
	if($rowOne[4] == '1'){
	$TimeValue = substr_replace($rowOne[5] ,"",-1);
	} else {
	$TimeValue = $rowOne[5];	
	}
	
	if($rowOne[5] == "Hours"){
	$timeCheck = time() - ($rowOne[4] * 3600);
	}
	if($rowOne[5] == "Days"){
	$timeCheck = time() - ($rowOne[4] * 86400);
	}
	if($rowOne[5] == "Weeks"){
	$timeCheck = time() - ($rowOne[4] * 604800);
	}
					if ($rowOne[3] < $timeCheck && $rowOne[3] != ""){ 
					$ChannelIssues .= $rowOne[2]."\n";
					$issues++;
					}
			
				
}

echo $ChannelIssues;

if ($ChannelIssues != ''){
require '/usr/share/php/libphp-phpmailer/src/PHPMailer.php';
require '/usr/share/php/libphp-phpmailer/src/SMTP.php';
$mail = new PHPMailer\PHPMailer\PHPMailer();
$mail->setFrom('KAMS@CAMDEN-audiolog.com');
$mail->addAddress('lcroce@kovacorp.com');
//$mail->addAddress('techsupport@kovacorp.com');
$mail->Subject = 'CAMDEN Motorola Channel Issues';
$mail->Body = $ChannelIssues;
$mail->IsSMTP();


if(!$mail->send()) {
  echo 'Email is not sent.';
  echo 'Email error: ' . $mail->ErrorInfo;
} else {
  echo 'Email has been sent.';
 }
}
?>

