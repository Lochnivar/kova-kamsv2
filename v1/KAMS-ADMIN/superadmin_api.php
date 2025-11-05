<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

/*
  Superadmin API for KAMS settings table (schema provided).
  Full CRUD for table:
    id (PK int auto_increment),
    name varchar(191) unique,
    value text,
    type enum(...),
    group_name varchar(100),
    sort_order int,
    description varchar(255),
    created_at timestamp,
    updated_at timestamp

  Hardcoded superadmin password: kovaADMIN
  IMPORTANT: replace credentials and hardcoded password before production use.
*/

$dbHost = '127.0.0.1';
$dbName = 'KAMS';
$dbUser = 'kams';
$dbPass = 'kams7906';
$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

/* CORS - restrict in production */
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$SUPERADMIN_PASSWORD = 'kovaADMIN';

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
        if ($value === '' || is_null($value)) return true;
        json_decode((string)$value);
        return json_last_error() === JSON_ERROR_NONE;
    }
    return true;
}

/* Auth endpoints */
if ($action === 'login' && $method === 'POST') {
    $pw = isset($input['password']) ? (string)$input['password'] : '';
    if ($pw === $SUPERADMIN_PASSWORD) {
        $_SESSION['superadmin'] = true;
        echo json_encode(['success' => true]);
        exit;
    }
    http_response_code(401);
    echo json_encode(['error' => 'Invalid password']);
    exit;
}

if ($action === 'logout' && $method === 'POST') {
    unset($_SESSION['superadmin']);
    session_regenerate_id(true);
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'check' && $method === 'GET' && isset($_GET['auth']) && $_GET['auth'] === 'check') {
    echo json_encode(['auth' => !empty($_SESSION['superadmin'])]);
    exit;
}

/* Require superadmin for other operations */
if (empty($_SESSION['superadmin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Superadmin authentication required']);
    exit;
}

try {
    if ($method === 'GET') {
        $sql = "SELECT id, name, value, type, description, group_name, sort_order, created_at, updated_at
                FROM settings
                ORDER BY COALESCE(group_name,'default'), COALESCE(sort_order,100), name";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll();
        echo json_encode($rows);
        exit;
    }

    if ($method === 'POST') {
        $name = isset($input['name']) ? trim((string)$input['name']) : '';
        $value = isset($input['value']) ? (string)$input['value'] : '';
        $type = isset($input['type']) ? (string)$input['type'] : 'string';
        $desc = isset($input['description']) ? (string)$input['description'] : null;
        $group = isset($input['group_name']) ? (string)$input['group_name'] : 'default';
        $order = isset($input['sort_order']) ? (int)$input['sort_order'] : 100;

        if ($name === '') bad('Invalid name');
        if (!validateType($value, $type)) bad('Value does not match type');

        $sql = 'INSERT INTO settings (name, value, type, description, group_name, sort_order) VALUES (:name, :value, :type, :desc, :group, :order)';
        $stmt = $pdo->prepare($sql);
        try {
            $stmt->execute([':name'=>$name, ':value'=>$value, ':type'=>$type, ':desc'=>$desc, ':group'=>$group, ':order'=>$order]);
            http_response_code(201);
            echo json_encode(['id' => (int)$pdo->lastInsertId()]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') bad('Name already exists', 409);
            throw $e;
        }
        exit;
    }

    if ($method === 'PUT') {
        if (!$id) bad('Missing id', 400);

        $allowed = ['name','value','type','description','group_name','sort_order'];
        $updates = [];
        $params = [':id' => $id];

        foreach ($allowed as $k) {
            if (array_key_exists($k, $input)) {
                $updates[] = "`$k` = :$k";
                $params[":$k"] = $input[$k];
            }
        }

        if (empty($updates)) bad('No updatable fields provided', 400);

        if (array_key_exists('type', $input) && array_key_exists('value', $input)) {
            if (!validateType($input['value'], $input['type'])) bad('Value does not match type');
        } elseif (array_key_exists('type', $input)) {
            $stmt = $pdo->prepare('SELECT value FROM settings WHERE id = :id LIMIT 1');
            $stmt->execute([':id'=>$id]);
            $cur = $stmt->fetch();
            $curValue = $cur['value'] ?? '';
            if (!validateType($curValue, $input['type'])) bad('Current value does not match new type');
        } elseif (array_key_exists('value', $input)) {
            $stmt = $pdo->prepare('SELECT type FROM settings WHERE id = :id LIMIT 1');
            $stmt->execute([':id'=>$id]);
            $cur = $stmt->fetch();
            $curType = $cur['type'] ?? 'string';
            if (!validateType($input['value'], $curType)) bad('Value does not match current type');
        }

        if (array_key_exists('name', $input)) {
            $stmt = $pdo->prepare('SELECT id FROM settings WHERE name = :name AND id <> :id LIMIT 1');
            $stmt->execute([':name'=>$input['name'], ':id'=>$id]);
            if ($stmt->fetch()) bad('Name already exists', 409);
        }

        $sql = 'UPDATE settings SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        http_response_code(204);
        exit;
    }

    if ($method === 'DELETE') {
        if (!$id) bad('Missing id', 400);
        $stmt = $pdo->prepare('DELETE FROM settings WHERE id = :id');
        $stmt->execute([':id'=>$id]);
        if ($stmt->rowCount() === 0) bad('Not found', 404);
        http_response_code(204);
        exit;
    }

    bad('Unsupported method', 405);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error', 'detail' => $e->getMessage()]);
    exit;
}
