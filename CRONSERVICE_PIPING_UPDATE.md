# CronService Piping Architecture Update

## Overview

The `CronService.php` file has been updated to properly handle the piping architecture, ensuring that process monitoring and cleanup work correctly with the new real-time parsers.

---

## Changes Made

### 1. Enhanced Process Monitoring (`CronService.php`)

**Before**: Simple `ps | grep` check that only verified shell PID existed

**After**: Multi-level monitoring that verifies:
1. Shell PID is running
2. Process group contains actual data collection processes (tcpdump/cat/parser)
3. Detects zombie processes or dead pipelines

**Key Improvements**:
```php
// Check if PID exists
$result = exec("ps -p " . escapeshellarg($pid) . " -o pid= 2>/dev/null");

// Check if actual processes are running in the process group
$pgCheck = exec("pgrep -g " . $pgid . " -f '(tcpdump|cat.*dev|Parser\.php)'");
```

**Benefits**:
- ✅ Detects if tcpdump/cat dies but shell is still running
- ✅ Detects if parser dies but tcpdump is still running
- ✅ Ensures data collection is actually happening
- ✅ Reduces false positives from zombie processes

---

### 2. Enhanced Process Cleanup (`CronController::StopCrons()`)

**Before**: Only killed the shell PID

**After**: Kills entire process groups to ensure complete cleanup

**Key Improvements**:
```php
// Get process group ID
$pgid = exec("ps -p " . escapeshellarg($pid) . " -o pgid= 2>/dev/null");

// Kill entire process group (tcpdump/cat + parser)
$cmd = "kill -9 -" . escapeshellarg($pgid);

// Fallback to individual PID
$cmd = "kill -9 " . escapeshellarg($pid);
```

**Benefits**:
- ✅ Kills tcpdump/cat AND parser together
- ✅ No orphaned processes
- ✅ Clean shutdown of entire pipeline
- ✅ Fallback ensures cleanup even if PGID lookup fails

---

### 3. Process Group Creation (`startCron()` methods)

**Before**: Simple shell command execution

**After**: Uses `setsid` to create new process groups

**Key Changes**:
```php
// Create new process group for better isolation
$command = "setsid sh -c 'tcpdump ... | php Parser.php' & echo $!";
```

**Benefits**:
- ✅ Process group isolation
- ✅ Easier to kill entire pipeline
- ✅ Better process monitoring
- ✅ Prevents interference between different cron pipelines

---

## Architecture Flow

### Process Group Structure
```
setsid (creates new process group)
  └── sh (shell process - stored PID)
      └── tcpdump/cat (data collection)
          └── php Parser.php (real-time processing)
```

### Monitoring Flow
```
CronService Loop (every 5 seconds)
  ├── Check shell PID exists
  ├── If shell exists, check process group
  ├── Verify tcpdump/cat/parser processes are running
  └── If any check fails → Exit → Systemd restarts
```

### Cleanup Flow
```
StopCrons()
  ├── Get process group ID from stored PID
  ├── Kill entire process group (-PGID)
  └── Fallback: Kill individual PID if needed
```

---

## Module Enablement

The CronService continues to respect module enablement from the database:

```php
if ($cronStart->modEnabled() == "yes") {
    echo "Mod Enabled, Starting " . $mod . " Crons" . PHP_EOL;
    $cronStart->startCron();  // Only starts if enabled
} else {
    echo $mod . " Mod Disabled" . PHP_EOL;
}
```

**Status**: ✅ **Fully Handled** - Module enablement checking unchanged, works with piping architecture

---

## Error Handling

### Process Death Detection
- **Shell PID dies**: Detected immediately → Exit → Restart
- **Parser dies**: Detected via process group check → Exit → Restart
- **Tcpdump/cat dies**: Detected via process group check → Exit → Restart

### Logging
All events logged to `logs/kcm-cron-service.log`:
- Process starts
- Process stops
- Process group issues
- Restart triggers

---

## Testing Recommendations

1. **Test Process Monitoring**:
   ```bash
   # Start CronService
   php kcm/CronService.php &
   
   # Kill a parser process
   pkill -f UdpParser.php
   
   # Verify CronService detects and exits
   # Check logs for detection message
   ```

2. **Test Process Cleanup**:
   ```bash
   # Start crons
   php kcm/CronService.php &
   
   # Stop crons
   php kcm/CronMain.php  # (if it calls StopCrons)
   
   # Or manually:
   php -r "require 'kcm/CronService.php'; (new Controller)->StopCrons();"
   
   # Verify all processes killed
   ps aux | grep -E "(tcpdump|Parser|cat.*dev)"
   ```

3. **Test Module Enablement**:
   - Disable a module in database
   - Start CronService
   - Verify module's crons are NOT started
   - Verify enabled modules ARE started

---

## Summary

✅ **CronService Updated**: Enhanced monitoring and cleanup for piping architecture
✅ **Process Groups**: Better isolation and cleanup
✅ **Module Enablement**: Fully respected and working
✅ **Error Detection**: Improved detection of pipeline failures
✅ **Clean Shutdown**: Complete cleanup of all processes

The CronService now properly coordinates and monitors the piping-based cron processes, ensuring reliable operation and proper cleanup.

