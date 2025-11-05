# Systemd Service Architecture Improvements

## Current Architecture Analysis

### Current Setup
```
systemd service (kcm-cron.service)
  └── CronService.php (long-running PHP script)
      ├── Monitors processes (while loop)
      ├── Checks PIDs every 5 seconds
      └── Exits if processes die → systemd restarts
```

### Issues with Current Approach
1. **Redundant monitoring**: CronService monitors processes, but systemd can do this natively
2. **PHP overhead**: Long-running PHP script consuming memory
3. **Complex restart logic**: Manual PID checking and process group verification
4. **Limited granularity**: One service for all modules - can't restart individual modules
5. **Logging**: Mixed file-based logging instead of systemd journal

---

## Recommended Approaches

### Option 1: Simplify CronService (Recommended for Quick Migration)

**Approach**: Keep CronService but simplify it - let systemd handle monitoring

**Benefits**:
- ✅ Minimal code changes
- ✅ Leverages systemd's built-in monitoring
- ✅ Better logging via systemd journal
- ✅ Automatic restarts handled by systemd

**Changes**:
```php
// CronService.php - Simplified version
$controller = new Controller;
$controller->StopCrons();
$pids = $controller->StartCrons();

// Exit immediately - systemd will restart if needed
// Systemd's Restart=always will handle monitoring
exit(0);
```

**Systemd Service**:
```ini
[Unit]
Description=KCM Cron Services - Data Collection
After=network.target mysql.service
Requires=mysql.service

[Service]
Type=oneshot
ExecStart=/usr/bin/php /srv/kova/kcm/CronService.php
RemainAfterExit=yes
Restart=on-failure
RestartSec=10
StandardOutput=journal
StandardError=journal

# Resource limits
LimitNOFILE=65536
LimitNPROC=4096

[Install]
WantedBy=multi-user.target
```

**Pros**:
- ✅ Simple migration
- ✅ Systemd handles monitoring
- ✅ Better resource management
- ✅ Systemd journal integration

**Cons**:
- ⚠️ Still one service for all modules
- ⚠️ Can't restart individual modules

---

### Option 2: Systemd Template Units (Best Practice)

**Approach**: Create individual systemd services for each module/interface using template units

**Benefits**:
- ✅ Granular control per module
- ✅ Individual restart/stop per module
- ✅ Better monitoring per service
- ✅ Follows systemd best practices

**Implementation**:

#### 1. Create Template Unit: `kcm-cron@.service`
```ini
[Unit]
Description=KCM Cron Service - %i Module
After=network.target mysql.service
Requires=mysql.service

[Service]
Type=forking
ExecStart=/usr/bin/php /srv/kova/kcm/src/Modules/Crons/CronModuleStarter.php %i
PIDFile=/run/kcm-cron-%i.pid
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal

# Resource limits
LimitNOFILE=65536

[Install]
WantedBy=multi-user.target
```

#### 2. Create Module Starter Script: `CronModuleStarter.php`
```php
#!/usr/bin/env php
<?php
require(__DIR__ . '/../../vendor/autoload.php');

use Kova\Kams\Kcm\Modules\Crons\CronController;

$module = $argv[1] ?? null;
if (empty($module)) {
    fwrite(STDERR, "Error: Module name required\n");
    exit(1);
}

$controller = new CronController();
$controller->StopCronsForModule($module);
$pids = $controller->StartCronsForModule($module);

// Write PID file for systemd
if (!empty($pids)) {
    file_put_contents("/run/kcm-cron-{$module}.pid", $pids[0]);
}

exit(0);
```

#### 3. Enable Services:
```bash
systemctl enable kcm-cron@moto.service
systemctl enable kcm-cron@udp.service
systemctl enable kcm-cron@serial.service
systemctl enable kcm-cron@zabbix.service
```

**Pros**:
- ✅ Best practice systemd architecture
- ✅ Individual module control
- ✅ Better isolation
- ✅ Easier troubleshooting

**Cons**:
- ⚠️ Requires more code changes
- ⚠️ More service files to manage

---

### Option 3: Systemd Timer + Simple Service (Alternative)

**Approach**: Use systemd timer for periodic processing, simple service for data collection

**Benefits**:
- ✅ Native systemd scheduling
- ✅ No crontab needed
- ✅ Better logging and monitoring

**Implementation**:

#### Timer: `kcm-process.timer`
```ini
[Unit]
Description=KCM Cron Processing Timer

[Timer]
OnCalendar=*:0/1  # Every minute
Persistent=true

[Install]
WantedBy=timers.target
```

#### Service: `kcm-process.service`
```ini
[Unit]
Description=KCM Cron Processing Service

[Service]
Type=oneshot
ExecStart=/usr/bin/php /srv/kova/kcm/CronMain.php
StandardOutput=journal
StandardError=journal
```

**Pros**:
- ✅ Native systemd scheduling
- ✅ No crontab dependency
- ✅ Better integration

**Cons**:
- ⚠️ Still need data collection service (CronService)

---

## Recommended Solution: Hybrid Approach

### Best of Both Worlds

**For Data Collection (Long-Running)**:
- Use **Option 1** (Simplified CronService with systemd)
- Systemd handles monitoring and restart
- Simple, effective, minimal changes

**For Data Processing (Periodic)**:
- Use **Option 3** (Systemd timer)
- Replace crontab with systemd timer
- Better integration and logging

---

## Implementation Plan

### Step 1: Simplify CronService

Create `kcm/CronService.simple.php`:
```php
<?php
require(__DIR__ . '/../vendor/autoload.php');

use Kova\Kams\Kcm\Modules\Crons\CronController as Controller;

$controller = new Controller;

// Stop any existing crons
$controller->StopCrons();

// Start enabled crons
$pids = $controller->StartCrons();

// Log to systemd journal
error_log("KCM Cron Service: Started " . count($pids) . " processes");

// Exit - systemd will restart if processes die
// Systemd monitors the service, not individual processes
exit(0);
```

**Key Change**: Remove the monitoring loop - systemd handles that

### Step 2: Update Systemd Service

Create `kcm/src/Modules/Systemd/kcm-cron.service` (improved):
```ini
[Unit]
Description=KCM Cron Services - Data Collection
Documentation=https://github.com/your-repo/kova-kams
After=network.target mysql.service
Requires=mysql.service

[Service]
Type=oneshot
ExecStart=/usr/bin/php /srv/kova/kcm/CronService.php
RemainAfterExit=yes
Restart=on-failure
RestartSec=30
RestartPreventExitStatus=0

# Working directory
WorkingDirectory=/srv/kova/kcm

# User/Group (adjust as needed)
# User=kcm
# Group=kcm

# Resource limits
LimitNOFILE=65536
LimitNPROC=4096

# Security
NoNewPrivileges=true
PrivateTmp=true

# Logging
StandardOutput=journal
StandardError=journal
SyslogIdentifier=kcm-cron

[Install]
WantedBy=multi-user.target
```

### Step 3: Create Systemd Timer for Processing

Create `kcm/src/Modules/Systemd/kcm-process.timer`:
```ini
[Unit]
Description=KCM Cron Processing Timer
Documentation=https://github.com/your-repo/kova-kams

[Timer]
OnCalendar=*:0/1  # Every minute
Persistent=true
AccuracySec=1s

[Install]
WantedBy=timers.target
```

Create `kcm/src/Modules/Systemd/kcm-process.service`:
```ini
[Unit]
Description=KCM Cron Processing Service
Documentation=https://github.com/your-repo/kova-kams
After=network.target mysql.service kcm-cron.service

[Service]
Type=oneshot
ExecStart=/usr/bin/php /srv/kova/kcm/CronMain.php
WorkingDirectory=/srv/kova/kcm
StandardOutput=journal
StandardError=journal
SyslogIdentifier=kcm-process

[Install]
WantedBy=multi-user.target
```

---

## Comparison: Current vs Recommended

| Aspect | Current | Recommended |
|--------|---------|-------------|
| **Monitoring** | PHP while loop | Systemd native |
| **Restart** | Manual detection | Systemd automatic |
| **Logging** | File-based | Systemd journal |
| **Resource Limits** | None | Systemd limits |
| **Scheduling** | Crontab | Systemd timer |
| **CPU Usage** | 5s polling loop | Event-driven |
| **Memory** | Long-running PHP | On-demand |
| **Troubleshooting** | Check log files | `journalctl -u kcm-cron` |

---

## Migration Steps

### 1. Update CronService.php
```bash
# Backup current
cp kcm/CronService.php kcm/CronService.php.backup

# Replace with simplified version (remove monitoring loop)
```

### 2. Update Systemd Service File
```bash
# Copy improved service file
sudo cp kcm/src/Modules/Systemd/kcm-cron.service /etc/systemd/system/

# Reload systemd
sudo systemctl daemon-reload

# Restart service
sudo systemctl restart kcm-cron.service
```

### 3. Create Timer for Processing
```bash
# Copy timer and service files
sudo cp kcm/src/Modules/Systemd/kcm-process.* /etc/systemd/system/

# Enable timer (replaces crontab)
sudo systemctl enable kcm-process.timer
sudo systemctl start kcm-process.timer

# Verify
systemctl list-timers kcm-process.timer
```

### 4. Remove Crontab Entry
```bash
# Remove old crontab entry
crontab -e  # Remove the CronMain.php line
```

---

## Benefits of Recommended Approach

### 1. Native Systemd Integration
- ✅ Systemd handles all monitoring
- ✅ Automatic restarts
- ✅ Better process management
- ✅ Resource limits enforced

### 2. Better Logging
- ✅ All logs in systemd journal
- ✅ Easy querying: `journalctl -u kcm-cron -f`
- ✅ Structured logging
- ✅ Log rotation handled by systemd

### 3. Reduced Complexity
- ✅ No manual PID monitoring
- ✅ No polling loops
- ✅ Simpler code
- ✅ Less CPU usage

### 4. Better Observability
```bash
# Check service status
systemctl status kcm-cron.service

# View logs
journalctl -u kcm-cron -f

# Check timer
systemctl list-timers kcm-process.timer

# View processing logs
journalctl -u kcm-process -f
```

### 5. Resource Management
- ✅ Systemd enforces limits
- ✅ Prevents resource exhaustion
- ✅ Better isolation
- ✅ Security hardening options

---

## Advanced: Process Health Checks

If you need more sophisticated monitoring, add systemd health checks:

```ini
[Service]
# Health check script
ExecStartPost=/usr/bin/php /srv/kova/kcm/src/Modules/Crons/HealthCheck.php

# Watchdog (if process supports it)
WatchdogSec=60
```

---

## Summary

**Recommended**: **Option 1 (Simplified) + Option 3 (Timer)**

1. **Simplify CronService**: Remove monitoring loop, let systemd handle it
2. **Use systemd timer**: Replace crontab with systemd timer for processing
3. **Improve service file**: Add resource limits, better logging, security options

**Benefits**:
- ✅ Less code complexity
- ✅ Better system integration
- ✅ Native monitoring and restart
- ✅ Better logging and observability
- ✅ Follows systemd best practices

This approach leverages systemd's native capabilities while maintaining your current architecture with minimal changes.

