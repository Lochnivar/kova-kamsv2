# Piping Architecture Analysis: Direct Piping vs File-Based Processing

## Executive Summary

**Recommendation**: ✅ **YES - Piping directly to parsers is a better architecture**

The current file-based approach has significant latency and overhead. Direct piping would provide:
- Real-time processing (vs 1-minute batch delay)
- Reduced disk I/O
- Lower disk usage
- More efficient resource utilization
- Better scalability

There's already a working example in the codebase (Avtec module) that demonstrates this pattern.

---

## 1. Current Architecture (File-Based)

### Data Flow
```
tcpdump/cat → File (append) → CronMain.php → Read File → Parse → Insert DB → Clear File
     ↓              ↓              ↓              ↓          ↓
  (6000s)      (accumulates)   (every 1min)   (batch)   (delete)
```

### Current Implementation

**UDP Module**:
```php
// startCron(): Starts data collection
$command = "timeout 6000 /usr/bin/tcpdump udp -vvvvv -tt -l -i " . $iArray[0] 
    . ">>" . $this->fileName . "-" . $iArray[0] . " & echo $!";

// ProcessCron(): Processes collected data
$rawFile = file($this->fileName . "-" . $iface['ifaceid']);
$this->RecordCron($rawFile, $iface);
file_put_contents($this->fileName . "-" . $iface['ifaceid'], ""); // Clear
```

**Serial Module**:
```php
// startCron(): Starts data collection
$command = "timeout 6000 cat /dev/" . $iArray[0] 
    . " >> " . $this->fileName . "-" . $iArray[0] . " & echo $!";

// ProcessCron(): Processes collected data
$rawFile = file($this->fileName . "-" . $iface['ifaceid']);
$this->RecordCron($rawFile, $iface);
file_put_contents($this->fileName . "-" . $iface['ifaceid'], ""); // Clear
```

**Motorola Module**:
```php
// startCron(): Starts data collection
$command = "timeout 6000 /usr/bin/tcpdump -vvvvv -tt -A -i " . $iArray[0] 
    . " dst port 50150 and greater 100 >>" . $this->fileName . "-" . $iArray[0] . " & echo $!";

// ProcessCron(): Processes collected data
$rawFile = file($this->fileName . "-" . $iface['ifaceid']);
$this->RecordCron($rawFile);
file_put_contents($this->fileName . "-" . $iface['ifaceid'], ""); // Clear
```

### Issues with Current Approach

1. **Latency**: Up to 1-minute delay before data is processed
2. **Disk I/O**: Constant file appending and reading
3. **Disk Usage**: Files grow to potentially large sizes (6000s of data)
4. **Memory**: Reading entire files into memory at once
5. **Complexity**: Two-phase process (collection + processing)
6. **Error Recovery**: If parser crashes, file keeps growing

---

## 2. Proposed Architecture (Direct Piping)

### Data Flow
```
tcpdump/cat → PHP Parser (stdin) → Parse Line-by-Line → Buffer → Batch Insert DB
     ↓              ↓                    ↓                ↓           ↓
  (stream)      (real-time)          (streaming)      (chunk)    (efficient)
```

### Example from Codebase: Avtec Module

**Location**: `kcm/src/Modules/Avtec/avtec-parser.php`

**Pattern**:
```php
// Reads from stdin in real-time
while (!feof($stdin)) {
    $rawLine = fgets($stdin);
    if ($rawLine === false) { 
        usleep(20000); 
        continue; 
    }
    
    // Process line immediately
    $parts = explode('|', $line, 6);
    // ... parsing logic ...
    
    // Insert to DB (with buffering/batching)
    if ($insertStmt) {
        try {
            $insertStmt->execute([...]);
        } catch (\Exception $e) {
            // Error handling
        }
    }
}
```

**Command Pattern**:
```bash
# Pipe directly to PHP parser
tcpdump ... | php /path/to/parser.php
```

---

## 3. Proposed Implementation

### 3.1 UDP Module Parser

**New File**: `kcm/src/Modules/Udp/UdpParser.php`

```php
<?php
namespace Kova\Kams\Kcm\Modules\Udp;

use Kova\Kams\Common\Database as DB;

// Read from stdin
$stdin = fopen('php://stdin', 'r');
$dbConn = new DB('kams');
$iface = $argv[1] ?? null; // Get interface from command line

$buffer = [];
$lastInsert = time();
$bufferSize = 100; // Insert every 100 lines or every 10 seconds

while (!feof($stdin)) {
    $line = fgets($stdin);
    if ($line === false) {
        usleep(100000); // 100ms
        continue;
    }
    
    $line = trim($line);
    if (empty($line)) continue;
    
    // Parse tcpdump line
    // Extract timestamp and packet info
    $buffer[] = [
        'iface' => $iface,
        'line' => $line,
        'timestamp' => time()
    ];
    
    // Batch insert to reduce DB overhead
    $now = time();
    if (count($buffer) >= $bufferSize || ($now - $lastInsert) >= 10) {
        $this->batchInsert($dbConn, $buffer, $iface);
        $buffer = [];
        $lastInsert = $now;
    }
}

// Flush remaining buffer
if (!empty($buffer)) {
    $this->batchInsert($dbConn, $buffer, $iface);
}

function batchInsert($dbConn, $buffer, $iface) {
    $count = count($buffer);
    $tnow = time();
    
    // Extract timestamp from last packet
    $lastLine = end($buffer)['line'];
    $tstamp = extractTimestamp($lastLine);
    
    $dbConn->insert('udp_data', [
        'iface' => $iface,
        'udp_packets' => $count,
        'epoch' => $tnow,
        'tstamp' => $tstamp
    ]);
}
```

**Updated startCron()**:
```php
public function startCron()
{
    $x = 0;
    $ifs = explode("~", $this->ifaces);

    foreach ($ifs as $iface) {
        $iArray = explode("|", $iface);
        
        // Pipe directly to parser
        $parserScript = __DIR__ . '/UdpParser.php';
        $command = "timeout 6000 /usr/bin/tcpdump udp -vvvvv -tt -l -i " . $iArray[0] 
            . " | /usr/bin/php " . $parserScript . " " . $iArray[0] . " & echo $!";
        
        exec($command, $output);
        $this->dbConn->putProcID($this->mod, $output[$x], $iArray[0]);
        $pids[] = $output[$x];
        $x++;
    }
    
    return $pids;
}
```

---

### 3.2 Serial Module Parser

**New File**: `kcm/src/Modules/Serial/SerialParser.php`

```php
<?php
namespace Kova\Kams\Kcm\Modules\Serial;

use Kova\Kams\Common\Database as DB;

$stdin = fopen('php://stdin', 'r');
$dbConn = new DB('kams');
$iface = $argv[1] ?? null;

$lineCount = 0;
$lastInsert = time();
$insertInterval = 10; // Insert every 10 seconds

while (!feof($stdin)) {
    $line = fgets($stdin);
    if ($line === false) {
        usleep(100000);
        continue;
    }
    
    $lineCount++;
    
    // Batch insert periodically
    $now = time();
    if (($now - $lastInsert) >= $insertInterval) {
        $dbConn->insert('serial_data', [
            'iface' => $iface,
            'size' => $lineCount,
            'epoch' => $now
        ]);
        
        $lineCount = 0;
        $lastInsert = $now;
    }
}

// Flush remaining
if ($lineCount > 0) {
    $dbConn->insert('serial_data', [
        'iface' => $iface,
        'size' => $lineCount,
        'epoch' => time()
    ]);
}
```

**Updated startCron()**:
```php
public function startCron()
{
    $x = 0;
    $ifs = explode("~", $this->ifaces);

    foreach ($ifs as $iface) {
        $iArray = explode("|", $iface);
        
        $parserScript = __DIR__ . '/SerialParser.php';
        $command = "timeout 6000 cat /dev/" . $iArray[0] 
            . " | /usr/bin/php " . $parserScript . " " . $iArray[0] . " & echo $!";
        
        exec($command, $output);
        $this->dbConn->putProcID($this->mod, $output[$x], $iArray[0]);
        $pids[] = $output[$x];
        $x++;
    }
    
    return $pids;
}
```

---

### 3.3 Motorola Module Parser

**New File**: `kcm/src/Modules/Motorola/MotoParser.php`

```php
<?php
namespace Kova\Kams\Kcm\Modules\Motorola;

use Kova\Kams\Common\Database as DB;

$stdin = fopen('php://stdin', 'r');
$dbConn = new DB('kams');

$channelIDs = [];
$lastUpdate = time();
$updateInterval = 5; // Update every 5 seconds

while (!feof($stdin)) {
    $line = fgets($stdin);
    if ($line === false) {
        usleep(100000);
        continue;
    }
    
    // Parse Motorola XML packet
    if (strpos($line, "<DeviceID>") !== false) {
        $stringpos = strpos($line, "<AstroEvent");
        $line = substr($line, $stringpos);
        
        $packet = @simplexml_load_string($line);
        if ($packet && isset($packet->CallStatusEventArgs->CallStatus->DeviceID)) {
            $channelID = (string) $packet->CallStatusEventArgs->CallStatus->DeviceID;
            if (!empty($channelID)) {
                $channelIDs[$channelID] = time();
            }
        }
    }
    
    // Batch update periodically
    $now = time();
    if (($now - $lastUpdate) >= $updateInterval) {
        foreach ($channelIDs as $id => $timestamp) {
            // Check if channel exists
            $qb = $dbConn->createQueryBuilder();
            $qb->select('channel_id')
               ->from('moto_channel_data')
               ->where('channel_id = :id')
               ->setParameter('id', $id);
            $result = $dbConn->executeQueryBuilder($qb);
            
            if (count($result) == 0) {
                $dbConn->insert('moto_channel_data', ['channel_id' => $id]);
            }
            
            $dbConn->update('moto_channel_data', 
                ['last_activity' => $timestamp], 
                ['channel_id' => $id]
            );
        }
        
        $channelIDs = []; // Clear processed
        $lastUpdate = $now;
    }
}
```

**Updated startCron()**:
```php
public function startCron()
{
    $x = 0;
    $ifs = explode("~", $this->ifaces);

    foreach ($ifs as $iface) {
        $iArray = explode("|", $iface);
        
        $parserScript = __DIR__ . '/MotoParser.php';
        $command = "timeout 6000 /usr/bin/tcpdump -vvvvv -tt -A -i " . $iArray[0] 
            . " dst port 50150 and greater 100 | /usr/bin/php " . $parserScript . " & echo $!";
        
        exec($command, $output);
        $this->dbConn->putProcID($this->mod, $output[$x], $iArray[0]);
        $pids[] = $output[$x];
        $x++;
    }
    
    return $pids;
}
```

---

## 4. Benefits of Piping Architecture

### 4.1 Performance
- ✅ **Real-time processing**: No 1-minute delay
- ✅ **Reduced disk I/O**: No file reading/writing
- ✅ **Lower memory usage**: Process line-by-line instead of loading entire files
- ✅ **Better resource utilization**: No intermediate file storage

### 4.2 Reliability
- ✅ **Automatic restart**: If parser dies, systemd can restart the whole pipeline
- ✅ **No file corruption**: No risk of partial files
- ✅ **Better error handling**: Can catch and handle errors per-line

### 4.3 Maintainability
- ✅ **Simpler architecture**: Single process instead of two-phase
- ✅ **Easier debugging**: Can add logging inline
- ✅ **Better monitoring**: Can track processing rate in real-time

### 4.4 Scalability
- ✅ **Lower disk usage**: No accumulation of data files
- ✅ **Better for high-volume**: Can handle burst traffic better
- ✅ **Easier to distribute**: Can pipe to remote parsers if needed

---

## 5. Considerations & Challenges

### 5.1 Process Management
**Challenge**: Need to track parser PID, not just tcpdump/cat PID

**Solution**: 
```php
// Get both PIDs
$tcpdumpPID = $output[0];
$parserPID = $output[1]; // Or use process group

// Store both in database
$this->dbConn->putProcID($this->mod, $parserPID, $iArray[0]);
```

### 5.2 Error Handling
**Challenge**: If parser crashes, tcpdump keeps running

**Solution**: Use process groups or ensure tcpdump dies when parser dies:
```bash
# Using process groups
tcpdump ... | php parser.php &
PARSER_PID=$!
echo $PARSER_PID
```

### 5.3 Database Batching
**Challenge**: Need to batch inserts for efficiency

**Solution**: Use buffering with time/count thresholds (as shown in examples)

### 5.4 Backward Compatibility
**Challenge**: `ProcessCron()` still needed for alerts/reports

**Solution**: Keep `ProcessCron()` but make it lightweight:
- No file reading (already in DB)
- Just query DB for alerts/reports
- Can run less frequently (every 5 minutes instead of 1 minute)

---

## 6. Migration Strategy

### Phase 1: Add Parser Classes
1. Create `UdpParser.php`, `SerialParser.php`, `MotoParser.php`
2. Test with manual piping: `tcpdump ... | php parser.php`
3. Verify data insertion works correctly

### Phase 2: Update startCron()
1. Modify `startCron()` to pipe to parsers
2. Keep old file-based code as fallback
3. Add feature flag to switch between modes

### Phase 3: Simplify ProcessCron()
1. Update `ProcessCron()` to query DB instead of reading files
2. Remove file reading logic
3. Update alert/report logic to work from DB

### Phase 4: Remove File-Based Code
1. Remove file-based collection code
2. Remove file cleanup logic
3. Update documentation

---

## 7. Comparison Summary

| Aspect | File-Based (Current) | Piping (Proposed) |
|--------|---------------------|-------------------|
| **Latency** | Up to 1 minute | Real-time |
| **Disk I/O** | High (read/write/delete) | None |
| **Disk Usage** | Potentially large files | None |
| **Memory** | Load entire file | Line-by-line |
| **Complexity** | Two-phase (collection + processing) | Single-phase |
| **Error Recovery** | Files can accumulate | Process restarts |
| **Scalability** | Limited by disk I/O | Better for high volume |
| **Monitoring** | Batch metrics | Real-time metrics |

---

## 8. Recommendation

**✅ Proceed with piping architecture**

The benefits significantly outweigh the challenges:
- Better performance
- Simpler architecture
- Real-time processing
- Lower resource usage
- Better scalability

The codebase already has a working example (Avtec module), so the pattern is proven.

**Next Steps**:
1. Create parser classes for each module
2. Update `startCron()` methods to use piping
3. Simplify `ProcessCron()` to work from DB
4. Test thoroughly with real data
5. Deploy and monitor

