<?php
require("/var/www/html/unified/vendor/autoload.php");
use IntelliTrend\Zabbix\ZabbixApi;

$zbx = new ZabbixApi();
$options = array('sslVerifyPeer' => false, 'sslVerifyHost' => false);

try {
	$zbx->login('http://localhost/zabbix', 'Admin', 'zabbix', $options);
	$result = $zbx->call('item.get', array("countOutput" => false, "search" => array('key_'=>'agent.ping'), 'output'=>array('hostid','lastvalue','hostname')));
	print "Number of hosts:$result\n";
} catch (Exception $e) {
	print "==== Exception ===\n";
	print 'Errorcode: '.$e->getCode()."\n";
	print 'ErrorMessage: '.$e->getMessage()."\n";
	exit;
}

var_dump($result);
//$payloadArray = array($sshName=> $result);

$payload = json_encode($result);
file_put_contents(__DIR__ . "/KCM.txt", $payload);
/*


//echo $payload . PHP_EOL;



$cmd = "scp KCM.txt  ".$sshName."@41.143.128.194:/home/" . $sshName . "/KCM.txt'";

echo $cmd . PHP_EOL;

//$outputBK = system ("ssh -t ".$sshName."@40.143.128.194 '/usr/bin/echo " . $payload . ">> /home/" . $sshName . "/KCM.txt'", $sshResp);
*/
