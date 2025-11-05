#!/usr/bin/env php
<?php
/**
 * SerialParser.php
 * 
 * Real-time parser for serial device output.
 * Reads from stdin and inserts aggregated data into database.
 * 
 * Usage: cat /dev/ttyXXX | php SerialParser.php <iface>
 */

declare(strict_types=1);

require_once(__DIR__ . '/../../../../vendor/autoload.php');

use Kova\Kams\Common\Database;

// Get interface from command line
$iface = $argv[1] ?? null;
if (empty($iface)) {
    fwrite(STDERR, "Error: Interface name required\n");
    fwrite(STDERR, "Usage: php SerialParser.php <iface>\n");
    exit(1);
}

// Initialize database connection
$dbConn = new Database('kams');
$logFile = kova_path('logs/kcm-cron-service.log');

// Buffering configuration
$insertInterval = 10; // Insert every 10 seconds
$lastInsert = time();
$lineCount = 0;

// Open stdin
$stdin = fopen('php://stdin', 'r');
if (!$stdin) {
    fwrite(STDERR, "Error: Could not open stdin\n");
    exit(1);
}

// Log start
@file_put_contents($logFile, "Serial Parser started for interface: {$iface}\n", FILE_APPEND);

/**
 * Insert buffered data to database
 */
function insertData(Database $dbConn, string $iface, int $count): void
{
    global $logFile;
    
    $tnow = time();
    
    try {
        $dbConn->insert('serial_data', [
            'iface' => $iface,
            'size' => $count,
            'epoch' => $tnow
        ]);
        
        @file_put_contents($logFile, "Serial Parser: Inserted {$count} lines for {$iface}\n", FILE_APPEND);
    } catch (\Exception $e) {
        @file_put_contents($logFile, "Serial Parser Error: " . $e->getMessage() . "\n", FILE_APPEND);
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
    
    // Batch insert periodically
    $now = time();
    if (($now - $lastInsert) >= $insertInterval) {
        insertData($dbConn, $iface, $lineCount);
        $lineCount = 0;
        $lastInsert = $now;
    }
}

// Flush remaining buffer
if ($lineCount > 0) {
    insertData($dbConn, $iface, $lineCount);
}

@file_put_contents($logFile, "Serial Parser finished for interface: {$iface}\n", FILE_APPEND);
exit(0);

