#!/usr/bin/env php
<?php
/**
 * avtec-parser.php
 *
 * Reads tshark lines:
 *   ip.src|tcp.srcport|ip.dst|tcp.dstport|data
 * or
 *   frame.time_epoch|ip.src|tcp.srcport|ip.dst|tcp.dstport|data
 *
 * Detects signature "PM\x01\x00", extracts channel/identifier, and writes:
 *   - DB insert (channel, received_at (system time), src/dst) using DB params from JSON
 *   - Rotating debug log
 *   - Immediate stdout status line
 *
 * Database JSON config path (edit if needed):
 *   /usr/src/kamsdev/kams/KCM/src/Modules/Common/configs/environment.json
 */

declare(ticks = 1);

// ---------------- CONFIGURATION ----------------
$logFile = '/var/log/avtec_parser.log';
$maxLogBytes = 10 * 1024 * 1024;
$signature = "PM" . chr(0x01) . chr(0x00);
$hexSnippetBytes = 128;
$debugLevel = 1; // 0=none,1=summary,2=verbose

// JSON config path for DB credentials
$dbConfigPath = '/usr/src/kamsdev/kams/KCM/src/Modules/Common/configs/environment.json';

// Table name and create DDL
$tableName = 'avtec_data';
$tableCreateSql = "
CREATE TABLE IF NOT EXISTS `{$tableName}` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `channel` VARCHAR(255) NULL,
  `received_at` DATETIME(6) NOT NULL,
  `src_ip` VARCHAR(45) NULL,
  `src_port` INT NULL,
  `dst_ip` VARCHAR(45) NULL,
  `dst_port` INT NULL,
  `payload_hex_snippet` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_received_at` (`received_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
// ------------------------------------------------

// minimal runtime visibility
error_reporting(E_ALL);
@ini_set('display_errors', '0');

// ---------------- Helpers ----------------
function hex_to_bin_safe(string $hex): string {
    $hex = preg_replace('/[^0-9a-fA-F]/', '', $hex);
    if ($hex === '') return '';
    $bin = @hex2bin($hex);
    return $bin === false ? '' : $bin;
}

function hexdump_snippet(string $data, int $limitBytes = 128): string {
    $data = substr($data, 0, $limitBytes);
    $hex = strtoupper(bin2hex($data));
    return trim(chunk_split($hex, 2, ' '));
}

function rotate_log_if_needed(string $path, int $maxBytes) {
    if (!file_exists($path)) return;
    clearstatcache(true, $path);
    $sz = @filesize($path);
    if ($sz === false) return;
    if ($sz < $maxBytes) return;
    $ts = date('Ymd_His');
    $rot = $path . '.' . $ts;
    @rename($path, $rot);
    $cmd = sprintf('nohup gzip -f %s > /dev/null 2>&1 &', escapeshellarg($rot));
    @exec($cmd);
}

function logmsg(string $path, string $line) {
    $entry = "[" . date('Y-m-d H:i:s') . "] " . $line . PHP_EOL;
    @file_put_contents($path, $entry, FILE_APPEND | LOCK_EX);
}

function is_printable_ascii(string $s): bool {
    $len = strlen($s);
    if ($len === 0) return false;
    for ($i = 0; $i < $len; $i++) {
        $c = ord($s[$i]);
        if ($c < 32 || $c > 126) return false;
    }
    return true;
}

// Identifier extraction (simplified)
function extract_identifier_best_effort(string $payload): ?string {
    $len = strlen($payload);

    if ($len >= 14 && ord($payload[8]) === 0x00 && ord($payload[9]) === 0x00 && ord($payload[10]) === 0x00 && ord($payload[11]) === 0x08) {
        $raw = substr($payload, 14, 60);
        $clean = preg_replace("/\x00+/", "", $raw);
        if ($clean !== '') return $clean;
    }

    foreach ([12, 14] as $start) {
        if ($len > $start + 2) {
            $l = ord($payload[$start + 1]);
            if ($l > 0 && ($start + 2 + $l) <= $len) {
                $val = substr($payload, $start + 2, $l);
                if (is_printable_ascii($val)) return $val;
            }
        }
    }

    $scan = substr($payload, 0, min(120, $len));
    if (preg_match('/([A-Za-z0-9_.\-]{4,64})/', $scan, $m)) {
        return $m[1];
    }

    return null;
}

// ---------------- Ensure log exists ----------------
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    @mkdir($logDir, 0775, true);
}
if (!file_exists($logFile)) {
    @file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] LOG CREATED\n", FILE_APPEND | LOCK_EX);
}
logmsg($logFile, "avtec-parser starting (uid=" . (function_exists('posix_getuid') ? posix_getuid() : 'n/a') . ", pid=" . getmypid() . ")");

// ---------------- Load DB config from JSON ----------------
$pdo = null;
$insertStmt = null;
$dbConfig = null;

if (file_exists($dbConfigPath) && is_readable($dbConfigPath)) {
    $raw = @file_get_contents($dbConfigPath);
    $parsed = $raw !== false ? @json_decode($raw, true) : null;
    if (is_array($parsed) && !empty($parsed)) {
        $dbConfig = $parsed;
    } else {
        logmsg($logFile, "DB CONFIG: unable to parse JSON at {$dbConfigPath}");
    }
} else {
    logmsg($logFile, "DB CONFIG: missing or unreadable {$dbConfigPath}");
}

// dbConfig expected keys (example): { "dsn":"mysql:host=127.0.0.1;port=3306;dbname=avtec;charset=utf8mb4", "user":"avtec_user", "password":"secret", "options":{} }
if (is_array($dbConfig) && isset($dbConfig['dsn'], $dbConfig['user'])) {
    $dbDsn = $dbConfig['dsn'];
    $dbUser = $dbConfig['user'];
    $dbPass = isset($dbConfig['password']) ? $dbConfig['password'] : '';
    $dbOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    if (isset($dbConfig['options']) && is_array($dbConfig['options'])) {
        // allow overriding PDO constants by numeric key/value pairs if provided
        foreach ($dbConfig['options'] as $k => $v) {
            $dbOptions[$k] = $v;
        }
    }

    try {
        $pdo = new PDO($dbDsn, $dbUser, $dbPass, $dbOptions);
        $pdo->exec($tableCreateSql);
        $insertSql = "INSERT INTO `{$tableName}` (channel, received_at, src_ip, src_port, dst_ip, dst_port, payload_hex_snippet)
                      VALUES (:channel, :received_at, :src_ip, :src_port, :dst_ip, :dst_port, :payload_hex_snippet)";
        $insertStmt = $pdo->prepare($insertSql);
        logmsg($logFile, "DB: connected and table ensured");
    } catch (Exception $e) {
        logmsg($logFile, "DB ERROR: " . $e->getMessage());
        $pdo = null;
        $insertStmt = null;
    }
} else {
    logmsg($logFile, "DB: not configured, DB inserts disabled");
}

// Unbuffer stdout
@ob_implicit_flush(true);

// Open STDIN
$stdin = fopen('php://stdin', 'r');
if (!$stdin) {
    logmsg($logFile, "ERROR: unable to open STDIN");
    exit(1);
}

// Main loop: accept either 5-field (no epoch) or 6-field (with epoch)
while (!feof($stdin)) {
    $rawLine = fgets($stdin);
    if ($rawLine === false) { usleep(20000); continue; }
    $line = trim($rawLine);
    if ($line === '') continue;

    $parts = explode('|', $line, 6);

    if (count($parts) === 6) {
        list($maybe_epoch, $ip_src, $tcp_srcport, $ip_dst, $tcp_dstport, $data_hex) = $parts;
    } elseif (count($parts) === 5) {
        $maybe_epoch = null;
        list($ip_src, $tcp_srcport, $ip_dst, $tcp_dstport, $data_hex) = $parts;
    } else {
        rotate_log_if_needed($logFile, $maxLogBytes);
        logmsg($logFile, "MALFORMED_LINE(count=" . count($parts) . "): " . $line);
        continue;
    }

    $payload = hex_to_bin_safe($data_hex);
    if ($payload === '') {
        if ($debugLevel >= 2) {
            rotate_log_if_needed($logFile, $maxLogBytes);
            logmsg($logFile, "NO_PAYLOAD: {$ip_src}:{$tcp_srcport} -> {$ip_dst}:{$tcp_dstport}");
        }
        continue;
    }

    $pos = strpos($payload, $signature);
    if ($pos === false) continue;

    $channel = extract_identifier_best_effort(substr($payload, $pos));
    $channelSafe = $channel !== null ? preg_replace('/[^[:print:]]/', '?', $channel) : null;

    // system receipt timestamp with microseconds
    $microtime = microtime(true);
    $sec = (int)$microtime;
    $usec = (int)round(($microtime - $sec) * 1000000);
    $received_at = date('Y-m-d H:i:s', $sec) . '.' . str_pad($usec, 6, '0', STR_PAD_LEFT);

    // stdout immediate feedback
    $short = sprintf("[%s] RECEIVED %s:%s -> %s:%s payload=%d sig_offset=%d channel=%s",
        $received_at, $ip_src, $tcp_srcport, $ip_dst, $tcp_dstport, strlen($payload), $pos, $channelSafe ?? '(none)');
    echo $short . PHP_EOL;

    // DB insert if available
    if ($insertStmt) {
        try {
            $insertStmt->execute([
                ':channel' => $channelSafe,
                ':received_at' => $received_at,
                ':src_ip' => $ip_src,
                ':src_port' => $tcp_srcport !== '' ? (int)$tcp_srcport : null,
                ':dst_ip' => $ip_dst,
                ':dst_port' => $tcp_dstport !== '' ? (int)$tcp_dstport : null,
                ':payload_hex_snippet' => hexdump_snippet($payload, $hexSnippetBytes),
            ]);
        } catch (Exception $ex) {
            rotate_log_if_needed($logFile, $maxLogBytes);
            logmsg($logFile, "DB_INSERT_ERROR: " . $ex->getMessage() . " -- channel=" . ($channelSafe ?? '(none)'));
        }
    } else {
        rotate_log_if_needed($logFile, $maxLogBytes);
        logmsg($logFile, "DB_DISABLED_MATCH: {$ip_src}:{$tcp_srcport} -> {$ip_dst}:{$tcp_dstport} channel=" . ($channelSafe ?? '(none)'));
    }

    if ($debugLevel >= 1) {
        rotate_log_if_needed($logFile, $maxLogBytes);
        $dbg = "MATCH {$ip_src}:{$tcp_srcport} -> {$ip_dst}:{$tcp_dstport} channel=" . ($channelSafe ?? '(none)') .
               " received_at=" . $received_at . " payload_len=" . strlen($payload) . " sig_offset=" . $pos;
        if ($debugLevel >= 2) $dbg .= PHP_EOL . "PayloadHexSnippet: " . hexdump_snippet($payload, $hexSnippetBytes);
        logmsg($logFile, $dbg);
    }

    usleep(1000);
} // end loop

fclose($stdin);
exit(0);
?>
