#!/usr/bin/env php
<?php
/**
 * avtec_persistent_raw_tcp_sniffer.php
 * - Persistent, port-agnostic Avtec TCP metadata sniffer for multiple source IPs.
 * - Intended to be launched once (systemd, @reboot cron, or supervisor).
 *
 * Configure the variables in the CONFIGURATION section below.
 *
 * Run: sudo php /path/to/avtec_persistent_raw_tcp_sniffer.php
 */

declare(ticks=1);
set_time_limit(0);
error_reporting(E_ALL);

// ====================== CONFIGURATION ======================
$iface           = 'enp6s18';                         // optional, informational only
$watch_ips       = ['10.0.0.133'];      // list of Avtec server IPs to accept
$match_as_source = true;                              // true => packet.src in watch_ips; false => packet.dst in watch_ips
$signature       = "PM" . chr(0x01) . chr(0x00);      // Avtec metadata signature
$buflen          = 65535;
$logFile         = '/var/log/avtec_sniffer.log';
$pidFile         = '/var/run/avtec_sniffer.pid';
$maxBackoff      = 300;                               // max seconds to wait between restarts
// ============================================================

// === Helpers ===
function logmsg($msg)
{
    global $logFile;
    $line = "[" . date('Y-m-d H:i:s') . "] " . $msg . PHP_EOL;
    // try to append to log, ignore failures
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    echo $line;
}

function hexdump($data, $limit = 256)
{
    $data = substr($data, 0, $limit);
    $hex = strtoupper(bin2hex($data));
    $out = '';
    for ($i = 0; $i < strlen($hex); $i += 2) {
        $out .= $hex[$i] . $hex[$i + 1];
        if ((($i / 2 + 1) % 16) === 0) $out .= PHP_EOL;
        else $out .= ' ';
    }
    return trim($out);
}

function ip_to_str($raw)
{
    return implode('.', array_map('ord', str_split($raw)));
}

function extract_identifier_from_payload($payload)
{
    // long-format attempt (observed layout)
    if (
        strlen($payload) >= 14 && strlen($payload) >= 12 &&
        ord($payload[8]) === 0x00 && ord($payload[9]) === 0x00 &&
        ord($payload[10]) === 0x00 && ord($payload[11]) === 0x08
    ) {
        $raw = substr($payload, 14, 60);
        $raw = preg_replace("/\x00+/", "", $raw);
        if ($raw !== '') return $raw;
    }
    // compact TLV attempt at offsets 12 and 14
    foreach ([12, 14] as $start) {
        if (strlen($payload) > $start + 2) {
            $l = ord($payload[$start + 1]);
            if ($start + 2 + $l <= strlen($payload)) {
                $val = substr($payload, $start + 2, $l);
                if ($val !== '' && preg_match('/^[\x20-\x7E]+$/', $val)) return $val;
            }
        }
    }
    return null;
}

// === PID management ===
function write_pid($pidFile)
{
    if (file_exists($pidFile)) {
        $old = (int)@file_get_contents($pidFile);
        if ($old > 0 && posix_kill($old, 0)) {
            throw new Exception("PID file exists and process {$old} is running. Refusing to start.");
        }
    }
    $pid = getmypid();
    @file_put_contents($pidFile, (string)$pid, LOCK_EX);
    return $pid;
}

function remove_pid($pidFile)
{
    if (file_exists($pidFile)) @unlink($pidFile);
}

// === Signal handling ===
$terminate = false;
pcntl_signal(SIGTERM, function () use (&$terminate) {
    $terminate = true;
});
pcntl_signal(SIGINT,  function () use (&$terminate) {
    $terminate = true;
});
pcntl_signal(SIGHUP,  function () { /* ignore or reload later */
});

// === Interface IP helper (informational only) ===
function iface_ip($ifname)
{
    $cmd = "ip -4 -o addr show dev " . escapeshellarg($ifname) . " 2>/dev/null";
    $out = [];
    exec($cmd, $out);
    if (count($out) === 0) return '0.0.0.0';
    if (preg_match('/inet\s+([0-9\.]+)\/\d+/', $out[0], $m)) return $m[1];
    return '0.0.0.0';
}

// === Startup ===
try {
    $pid = write_pid($pidFile);
    logmsg("Starting Avtec sniffer pid={$pid} iface={$iface} watching=" . implode(',', $watch_ips));
} catch (Exception $e) {
    logmsg("Startup error: " . $e->getMessage());
    exit(1);
}

// === Main loop with retry/backoff using AF_INET raw socket (IPPROTO_TCP) ===
$backoff = 1;
$local_ip = iface_ip($iface);
if ($local_ip === '0.0.0.0') {
    logmsg("Warning: could not determine IP for interface {$iface}; binding may be generic");
}
logmsg("Using local IP (informational): {$local_ip}");

while (!$terminate) {
    try {
        if (!extension_loaded('sockets')) {
            throw new Exception("PHP sockets extension is required");
        }
        if (!extension_loaded('pcntl')) {
            throw new Exception("PHP pcntl extension is required");
        }

        // Create AF_INET raw socket for TCP (IPPROTO_TCP)
        $proto = getprotobyname('tcp');
        $sock = @socket_create(AF_INET, SOCK_RAW, $proto);
        if ($sock === false) {
            $err = socket_last_error();
            throw new Exception("socket_create(AF_INET,SOCK_RAW,IPPROTO_TCP) failed: " . socket_strerror($err) . " (errno={$err})");
        }

        // Optional: bind to local IP so kernel delivers packets for that address
        if (!@socket_bind($sock, $local_ip, 0)) {
            $err = socket_last_error($sock);
            logmsg("Warning: socket_bind failed: " . socket_strerror($err) . " - continuing without bind");
        }

        logmsg("Socket created successfully; entering capture loop");
        $backoff = 1; // reset backoff on successful socket

        // Capture loop
        while (!$terminate) {
            $buf = '';
            $ret = @socket_recv($sock, $buf, $buflen, 0);
            if ($ret === false) {
                $err = socket_last_error($sock);
                logmsg("socket_recv error: " . socket_strerror($err) . " (errno={$err})");
                break; // recreate socket after backoff
            }
            if ($ret === 0) {
                usleep(100000);
                continue;
            }

            if (strlen($buf) < 20) continue;
            $ip_ver_ihl = ord($buf[0]);
            $ihl = ($ip_ver_ihl & 0x0f) * 4;
            if ($ihl < 20 || strlen($buf) < $ihl) continue;
            $protocol = ord($buf[9]);
            if ($protocol !== 6) continue; // not TCP
            $src_ip = ip_to_str(substr($buf, 12, 4));
            $dst_ip = ip_to_str(substr($buf, 16, 4));

            // TCP header
            if (strlen($buf) < $ihl + 20) continue;
            $tcp_hdr = substr($buf, $ihl, 20);
            $ports = unpack('nsrcport/ndstport', $tcp_hdr);
            $src_port = $ports['srcport'];
            $dst_port = $ports['dstport'];
            $data_offset_byte = ord($tcp_hdr[12]);
            $tcp_header_len = (($data_offset_byte >> 4) & 0x0f) * 4;
            $payload_offset = $ihl + $tcp_header_len;
            if (strlen($buf) <= $payload_offset) continue;
            $payload = substr($buf, $payload_offset);

            // IP filter
            $ip_check = $match_as_source ? $src_ip : $dst_ip;
            if (!in_array($ip_check, $watch_ips, true)) continue;

            // Signature search
            $pos = strpos($payload, $signature);
            if ($pos === false) continue;

            // Matched message: log summary, identifier if found, and small hexdump

            /*
            $ident = extract_identifier_from_payload($payload);
            $info = sprintf("Match %s:%d -> %s:%d payload=%d sig_offset=%d", $src_ip, $src_port, $dst_ip, $dst_port, strlen($payload), $pos);
            logmsg($info);
            if ($ident !== null) logmsg("Identifier: " . preg_replace('/[^[:print:]]/', '?', $ident));
            logmsg("Payload (hex, first 256 bytes):\n" . hexdump(substr($payload, 0, 256)));
  */
            // Matched message: prepare info and log
            $ident = extract_identifier_from_payload($payload);
            $info = sprintf("Match %s:%d -> %s:%d payload=%d sig_offset=%d", $src_ip, $src_port, $dst_ip, $dst_port, strlen($payload), $pos);
            logmsg($info);
            if ($ident !== null) logmsg("Identifier: " . preg_replace('/[^[:print:]]/', '?', $ident));
            logmsg("Payload (hex, first 256 bytes):\n" . hexdump(substr($payload, 0, 256)));

            // ECHO to stdout immediately so tcpreplay / cron can observe activity
            $ts = date('Y-m-d H:i:s');
            $echoLine = sprintf(
                "[%s] RECEIVED packet %s:%d -> %s:%d sig_offset=%d payload=%d\n",
                $ts,
                $src_ip,
                $src_port,
                $dst_ip,
                $dst_port,
                $pos,
                strlen($payload)
            );
            echo $echoLine;
            if ($ident !== null) {
                echo "Identifier: " . preg_replace('/[^[:print:]]/', '?', $ident) . PHP_EOL;
            }
            // flush so output appears immediately
            @ob_flush();
            @flush();
        }

        @socket_close($sock);
        if ($terminate) break;

        // Unexpected exit from inner loop, backoff and retry
        logmsg("Capture loop exited unexpectedly; restarting after {$backoff}s");
        sleep($backoff);
        $backoff = min($backoff * 2, $maxBackoff);
    } catch (Exception $ex) {
        logmsg("Exception: " . $ex->getMessage());
        if (isset($sock) && is_resource($sock)) @socket_close($sock);
        logmsg("Retrying after {$backoff}s");
        sleep($backoff);
        $backoff = min($backoff * 2, $maxBackoff);
    }
}

// Shutdown
logmsg("Termination requested, cleaning up");
remove_pid($pidFile);
if (isset($sock) && is_resource($sock)) @socket_close($sock);
logmsg("Stopped");
exit(0);
?>