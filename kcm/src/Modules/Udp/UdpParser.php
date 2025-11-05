#!/usr/bin/env php
<?php
/**
 * UdpParser.php
 * 
 * Real-time parser for UDP tcpdump output.
 * Reads from stdin and inserts aggregated data into database.
 * 
 * Usage: tcpdump ... | php UdpParser.php <iface>
 */

declare(strict_types=1);

require_once(__DIR__ . '/../../../../vendor/autoload.php');

use Kova\Kams\Common\Database;

// Get interface from command line
$iface = $argv[1] ?? null;
if (empty($iface)) {
    fwrite(STDERR, "Error: Interface name required\n");
    fwrite(STDERR, "Usage: php UdpParser.php <iface>\n");
    exit(1);
}

// Initialize database connection
$dbConn = new Database('kams');
$logFile = kova_path('logs/kcm-cron-service.log');

// Buffering configuration
$bufferSize = 100; // Insert every 100 lines
$insertInterval = 10; // Or every 10 seconds
$lastInsert = time();
$lineCount = 0;
$lastTimestamp = null;

// Open stdin
$stdin = fopen('php://stdin', 'r');
if (!$stdin) {
    fwrite(STDERR, "Error: Could not open stdin\n");
    exit(1);
}

// Log start
@file_put_contents($logFile, "UDP Parser started for interface: {$iface}\n", FILE_APPEND);

/**
 * Insert buffered data to database
 */
function insertData(Database $dbConn, string $iface, int $count, ?string $timestamp): void
{
    global $logFile;
    
    $tnow = time();
    $tstamp = $timestamp ?? (string)$tnow;
    
    try {
        $dbConn->insert('udp_data', [
            'iface' => $iface,
            'udp_packets' => $count,
            'epoch' => $tnow,
            'tstamp' => $tstamp
        ]);
        
        @file_put_contents($logFile, "UDP Parser: Inserted {$count} packets for {$iface}, timestamp: {$tstamp}\n", FILE_APPEND);
    } catch (\Exception $e) {
        @file_put_contents($logFile, "UDP Parser Error: " . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Main processing loop
while (!feof($stdin)) {
    $line = fgets($stdin);
    
    if ($line === false) {
        usleep(100000); // 100ms sleep when no data
        continue;
    }
    
    $line = trim($line);
    if (empty($line)) {
        continue;
    }
    
    $lineCount++;
    
    // Extract timestamp from tcpdump line format: "1234567890.123456 IP ..."
    // Look for lines starting with a timestamp (numeric with dot)
    if (preg_match('/^(\d+\.\d+)\s+IP/', $line, $matches)) {
        $lastTimestamp = $matches[1];
    }
    
    // Batch insert to reduce database overhead
    $now = time();
    if ($lineCount >= $bufferSize || ($now - $lastInsert) >= $insertInterval) {
        insertData($dbConn, $iface, $lineCount, $lastTimestamp);
        $lineCount = 0;
        $lastInsert = $now;
    }
}

// Flush remaining buffer
if ($lineCount > 0) {
    insertData($dbConn, $iface, $lineCount, $lastTimestamp);
}

@file_put_contents($logFile, "UDP Parser finished for interface: {$iface}\n", FILE_APPEND);
exit(0);

