<?php

include '/usr/src/KAMS-Setting-file.php';

require_once("ZabbixAPI/ZabbixApi.php");

use IntelliTrend\Zabbix\ZabbixApi;

$verCmds = [

        "mysql" => "mysql -e 'SELECT VERSION();'",
        "php" => "php -r 'echo phpversion();'",
        "ubuntu" => "lsb_release -d"


];

$versions = [];

foreach ($verCmds as $k => $v){

$result = system($v, $version);

$versions[$k] = $result;

}

$zbx = new ZabbixApi();

try {
        $zbx->login('http://127.0.0.1/zabbix', 'Admin', 'zabbix');
        $result = $zbx->call('apiinfo.version');
} catch (Exception $e) {
        print "==== Exception ===\n";
        print 'Errorcode: '.$e->getCode()."\n";
        print 'ErrorMessage: '.$e->getMessage()."\n";
        exit;
}


$versions['zabbix'] = $result;

$resArray = [];

 $cmd = "dpkg -l | awk '{ print $0 $2 $3 }'";

exec($cmd, $resArray);

foreach($resArray as $row){

$row = explode(" ", $row);

if($row[0] == "ii"){

$row = array_filter($row);

//var_dump($row);

$tempArray = [];
$tempRow = [];

foreach($row as $tempRow){

$tempArray[] = $tempRow;
}


$versions['packages'][$tempArray[1]] = $tempArray[2];

}

}
//var_dump($versions);

$payload = json_encode($versions);
file_put_contents(__DIR__ . "/versions.txt", $payload);

$cmd = "scp versions.txt  ".$sshName."@40.143.128.194:/home/" . $sshName . "/versions.txt";

echo $cmd . PHP_EOL;

exec($cmd, $response);
