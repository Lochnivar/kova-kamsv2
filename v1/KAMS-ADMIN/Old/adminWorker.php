<?php

$dbuser = 'kams';
$dbpass = 'kams7906';
$dbhost = 'localhost';
$dbname = 'KAMS';

$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);

foreach ($_POST as $k => $v) {

    if (is_array($v)) {
        $v = implode("~", $v);
    }

$sql = "UPDATE settings SET setvalue  = '". $v . "' where setname = '" . $k . "'";


$mysqli->query($sql);

}

echo "Settings Changed.";
