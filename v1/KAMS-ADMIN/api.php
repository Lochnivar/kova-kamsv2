<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

/*
  Lightweight settings API for the user admin page.
  - Schema expected: id,name,value,type,group_name,sort_order,description,created_at,updated_at
  - Admin password (hardcoded): kovaADMIN
  - GET returns all settings (full rows)
  - PUT updates name/value/type/description for a given id (group/order are not updated here)
  - POST and DELETE are intentionally disabled for the user admin (to keep the restrictions)
*/

$dbHost = '127.0.0.1';
$dbName = 'KAMS';
$dbUser = 'kams';
$dbPass = 'kams7906';
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$HARDCODED_PASSWORD = 'kovaADMIN';

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$action = isset($_GET['action']) ? (string)$_GET['action'] : null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

function bad($msg, $code = 400) { http_response_code($code); echo json_encode(['error' => $msg]); exit; }
function validateType($value, $type): bool {
    if ($type === 'int') return preg_match('/^-?\d+$/', (string)$value) === 1;
    if ($type === 'float') return preg_match('/^-?\d+(\.\d+)?$/', (string)$value) === 1;
    if ($type === 'bool') return in_array((string)$value, ['0','1','true','false',''], true);
    if (in_array($type, ['json','list','object'], true)) {
        if ($value === '' || is_null($value)) return false;
        json_decode((string)$value);
        return json_last_error() === JSON_ERROR_NONE;
    }
    return true;
}

/* Auth endpoints */
if ($action === 'login' && $method === 'POST') {
    $pw = isset($input['password']) ? (string)$input['password'] : '';
    if ($pw === $HARDCODED_PASSWORD) {
        $_SESSION['authenticated'] = true;
        echo json_encode(['success' => true]);
        exit;
    }
    http_response_code(401);
    echo json_encode(['error' => 'Invalid password']);
    exit;
}

if ($action === 'logout' && $method === 'POST') {
    unset($_SESSION['authenticated']);
    session_regenerate_id(true);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'check' && $method === 'GET' && isset($_GET['auth']) && $_GET['auth'] === 'check') {
    echo json_encode(['auth' => !empty($_SESSION['authenticated'])]);
    exit;
}

/* Require authentication for other operations */
if (empty($_SESSION['authenticated'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

try {
    if ($method === 'GET') {
        $sql = "SELECT id, name, value, type, description, COALESCE(group_name, 'default') AS group_name, COALESCE(sort_order, 100) AS sort_order, updated_at
                FROM settings
                ORDER BY COALESCE(group_name,'default'), COALESCE(sort_order,100), name";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll();
        echo json_encode($rows);
        exit;
    }

    if ($method === 'PUT') {
        if (!$id) bad('Missing id', 400);
        $name = isset($input['name']) ? trim((string)$input['name']) : '';
        $value = isset($input['value']) ? (string)$input['value'] : '';
        $type = isset($input['type']) ? (string)$input['type'] : 'string';
        $desc = isset($input['description']) ? (string)$input['description'] : null;

        if ($name === '') bad('Invalid name');
        if (!validateType($value, $type)) bad('Value does not match type');

        $stmt = $pdo->prepare('SELECT id FROM settings WHERE name = :name AND id <> :id LIMIT 1');
        $stmt->execute([':name'=>$name, ':id'=>$id]);
        if ($stmt->fetch()) bad('Name already exists', 409);

        $sql = 'UPDATE settings SET name=:name, value=:value, type=:type, description=:desc WHERE id=:id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':name'=>$name, ':value'=>$value, ':type'=>$type, ':desc'=>$desc, ':id'=>$id]);
        if ($stmt->rowCount() === 0) {
            // allow 204 even if unchanged
        }
        http_response_code(204);
        exit;
    }

    // POST / DELETE are not allowed from this user admin API
    if ($method === 'POST') {
        bad('Creation disabled', 403);
    }

    if ($method === 'DELETE') {
        bad('Deletion disabled', 403);
    }

    bad('Unsupported method', 405);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
    exit;
}
