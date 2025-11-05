#!/usr/bin/env php
<?php
/**
 * MotoParser.php
 * 
 * Real-time parser for Motorola tcpdump output.
 * Reads from stdin, parses XML packets, and updates channel activity.
 * 
 * Usage: tcpdump ... | php MotoParser.php
 */

declare(strict_types=1);

require_once(__DIR__ . '/../../../../vendor/autoload.php');

use Kova\Kams\Common\Database;

// Initialize database connection
$dbConn = new Database('kams');
$logFile = kova_path('logs/kcm-cron-service.log');

// Buffering configuration
$updateInterval = 5; // Update every 5 seconds
$lastUpdate = time();
$channelIDs = []; // Track channels seen in this batch

// Open stdin
$stdin = fopen('php://stdin', 'r');
if (!$stdin) {
    fwrite(STDERR, "Error: Could not open stdin\n");
    exit(1);
}

// Log start
@file_put_contents($logFile, "Motorola Parser started\n", FILE_APPEND);

/**
 * Update channel activity in database
 */
function updateChannels(Database $dbConn, array $channelIDs): void
{
    global $logFile;
    
    if (empty($channelIDs)) {
        return;
    }
    
    $timeNow = time();
    $uniqueIDs = array_unique($channelIDs);
    
    foreach ($uniqueIDs as $id) {
        if (empty($id) || trim($id) === '') {
            continue;
        }
        
        try {
            // Check if channel exists
            $qb = $dbConn->createQueryBuilder();
            $qb->select('channel_id')
               ->from('moto_channel_data')
               ->where('channel_id = :id')
               ->setParameter('id', $id);
            $result = $dbConn->executeQueryBuilder($qb);
            
            if (count($result) == 0) {
                // Insert new channel
                $dbConn->insert('moto_channel_data', ['channel_id' => $id]);
                @file_put_contents($logFile, "Motorola Parser: Inserted new channel {$id}\n", FILE_APPEND);
            }
            
            // Update last activity
            $dbConn->update('moto_channel_data', 
                ['last_activity' => $timeNow], 
                ['channel_id' => $id]
            );
        } catch (\Exception $e) {
            @file_put_contents($logFile, "Motorola Parser Error for channel {$id}: " . $e->getMessage() . "\n", FILE_APPEND);
        }
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
    
    // Parse Motorola XML packet
    // Look for lines containing DeviceID
    if (strpos($line, "<DeviceID>") !== false) {
        // Extract AstroEvent XML
        $stringpos = strpos($line, "<AstroEvent");
        if ($stringpos !== false) {
            $xmlLine = substr($line, $stringpos);
            
            // Suppress XML parsing errors
            libxml_use_internal_errors(true);
            $packet = @simplexml_load_string($xmlLine);
            libxml_clear_errors();
            
            if ($packet && isset($packet->CallStatusEventArgs->CallStatus->DeviceID)) {
                $channelID = (string) $packet->CallStatusEventArgs->CallStatus->DeviceID;
                if (!empty($channelID) && trim($channelID) !== '') {
                    $channelIDs[] = trim($channelID);
                }
            }
        }
    }
    
    // Batch update periodically
    $now = time();
    if (($now - $lastUpdate) >= $updateInterval) {
        updateChannels($dbConn, $channelIDs);
        $channelIDs = []; // Clear processed
        $lastUpdate = $now;
    }
}

// Flush remaining buffer
if (!empty($channelIDs)) {
    updateChannels($dbConn, $channelIDs);
}

@file_put_contents($logFile, "Motorola Parser finished\n", FILE_APPEND);
exit(0);

