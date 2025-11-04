<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Doctrine\DBAL\DriverManager;
use Kova\Kams\Common\Db;

// load your environment mapping (same file you use today)
$env = json_decode(file_get_contents(__DIR__ . '/../unified/src/configs/environment.json'), true);

// pick the database key you use in existing code
$dbKey = 'yourDbKey'; // replace with actual key

$params = [
    'dbname'   => $env[$dbKey]['database']['db'],
    'user'     => $env[$dbKey]['database']['username'],
    'password' => $env[$dbKey]['database']['password'],
    'host'     => $env[$dbKey]['database']['host'],
    'driver'   => 'pdo_mysql',
    'charset'  => 'utf8mb4',
];

$conn = DriverManager::getConnection($params);

// instantiate wrapper
$db = new Db($conn);

// example usage: insert a kamsAlarms row
$affected = $db->insert('kamsAlarms', [
    'active'    => 1,
    'timestamp' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
    'module'    => 'example',
    'iface'     => 'enp6s18',
    'msgline'   => 'test alarm',
], [
    'active' => \Doctrine\DBAL\ParameterType::BOOLEAN,
]);

echo "Inserted rows: " . $affected . PHP_EOL;
