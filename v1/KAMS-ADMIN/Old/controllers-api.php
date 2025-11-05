<?php
// controllers-api.php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php'; // optional: include project bootstrap/autoloader if needed
require_once ("/usr/src/kamsdev/kams/KCM/vendor/autoload.php");

use Kova\Kcm\Modules\Common\Database as DB;
use Kova\Kcm\Modules\Crons\CronController;

// Very small auth gate - replace with your app auth
function require_admin() {
    // implement your auth. For now assume already authenticated.
}

header('Content-Type: application/json; charset=utf-8');

require_admin();

$db = new DB("kams"); // adapt constructor if different
$cron = new CronController(); // will read DB if you earlier wired loadControllersFromDB() in constructor

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? null;

if ($method === 'GET' && $action === 'list') {
    $rows = $db->dbQuery("SELECT id,name,type,unit,enabled,config,updated_at FROM cron_controllers ORDER BY name");
    echo json_encode($rows);
    exit;
}

if ($method === 'GET' && $action === 'get' && !empty($_GET['name'])) {
    $name = $_GET['name'];
    $rows = $db->dbQuery("SELECT * FROM cron_controllers WHERE name = ?", [$name]);
    if (empty($rows)) { echo json_encode(['error'=>'not_found']); exit; }
    echo json_encode(['row' => $rows[0]]);
    exit;
}

// POST actions expect JSON body
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$act = $input['action'] ?? $input['act'] ?? null;

if ($method === 'POST' && $act === 'upsert') {
    $name = trim($input['name'] ?? '');
    if ($name === '') { echo json_encode(['ok'=>false,'error'=>'name_required']); exit; }
    $cfg = $input['config'] ?? null;
    if (!is_array($cfg)) { echo json_encode(['ok'=>false,'error'=>'config_required']); exit; }
    $enabled = isset($input['enabled']) ? (int)$input['enabled'] : 1;
    $unit = $input['unit'] ?? null;
    // minimal server-side validation
    if (empty($cfg['parser'])) { echo json_encode(['ok'=>false,'error'=>'parser_required']); exit; }
    // upsert into DB
    $type = $cfg['type'] ?? (isset($cfg['device']) ? 'serial' : 'tshark');
    $json = json_encode($cfg, JSON_UNESCAPED_SLASHES);
    $exists = $db->dbQuery("SELECT id FROM cron_controllers WHERE name = ?", [$name]);
    if (!empty($exists)) {
        $db->dbQuery("UPDATE cron_controllers SET type=?, unit=?, enabled=?, config=?, updated_at=NOW(6) WHERE name = ?", [$type, $unit, $enabled, $json, $name]);
        $res = ['ok'=>true,'action'=>'updated'];
    } else {
        $db->dbQuery("INSERT INTO cron_controllers (name,type,unit,enabled,config) VALUES (?, ?, ?, ?, ?)", [$name, $type, $unit, $enabled, $json]);
        $res = ['ok'=>true,'action'=>'inserted'];
    }
    // apply change in-memory: reload controllers
    $cron->reloadControllersFromDB();
    echo json_encode($res);
    exit;
}

if ($method === 'POST' && $act === 'delete') {
    $name = trim($input['name'] ?? '');
    if ($name === '') { echo json_encode(['ok'=>false,'error'=>'name_required']); exit; }
    $db->dbQuery("DELETE FROM cron_controllers WHERE name = ?", [$name]);
    $cron->unregisterController($name);
    echo json_encode(['ok'=>true,'deleted'=>$name]);
    exit;
}

// control actions: start/stop/restart
if ($method === 'POST' && in_array($act, ['start','stop','restart'])) {
    $name = trim($input['name'] ?? '');
    if ($name === '') { echo json_encode(['ok'=>false,'error'=>'name_required']); exit; }
    // ensure controllers loaded
    $cron->reloadControllersFromDB();
    if ($act === 'start') $out = $cron->startController($name);
    if ($act === 'stop') $out = $cron->stopController($name);
    if ($act === 'restart') $out = $cron->restartController($name);
    echo json_encode(['ok'=>true,'result'=>$out]);
    exit;
}

http_response_code(400);
echo json_encode(['error'=>'unknown_action']);
exit;
