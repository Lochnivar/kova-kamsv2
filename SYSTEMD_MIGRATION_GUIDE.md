# Systemd Migration Guide

## Overview

This guide helps you migrate from the current CronService monitoring approach to a more systemd-native architecture.

---

## Current vs Recommended Architecture

### Current (Monitoring Loop)
```
systemd service
  └── CronService.php (long-running)
      └── while(true) { check PIDs; sleep(5); }
```

### Recommended (Systemd Native)
```
systemd service (Type=oneshot)
  └── CronService.php (starts processes, exits)
      └── Systemd handles restart if needed

systemd timer
  └── kcm-process.service (runs CronMain.php)
```

---

## Migration Options

### Option A: Simplified (Recommended - Easy Migration)

**Keep current monitoring but improve systemd integration**

**Pros**:
- ✅ Minimal code changes
- ✅ Keeps existing monitoring logic
- ✅ Better systemd integration
- ✅ Easy rollback

**Steps**:
1. Use `kcm-cron-monitored.service` (keeps monitoring loop)
2. Add resource limits and better logging
3. Replace crontab with `kcm-process.timer`

**Service File**: Use `kcm-cron-monitored.service`

---

### Option B: Systemd Native (Best Practice)

**Let systemd handle all monitoring**

**Pros**:
- ✅ No PHP monitoring overhead
- ✅ Native systemd monitoring
- ✅ Better resource management
- ✅ Simpler code

**Steps**:
1. Replace CronService.php with `CronService.simple.php`
2. Use `kcm-cron.service` (Type=oneshot)
3. Systemd restarts service if processes die
4. Replace crontab with `kcm-process.timer`

**Service File**: Use `kcm-cron.service`

---

## Step-by-Step Migration

### Step 1: Choose Your Approach

**Option A (Simplified)**: Keep monitoring loop, improve systemd service
**Option B (Native)**: Remove monitoring loop, use systemd exclusively

---

### Step 2: Update Systemd Service

**For Option A (Keep Monitoring)**:
```bash
sudo cp kcm/src/Modules/Systemd/kcm-cron-monitored.service /etc/systemd/system/kcm-cron.service
sudo systemctl daemon-reload
sudo systemctl restart kcm-cron.service
```

**For Option B (Systemd Native)**:
```bash
# Backup current
sudo cp /etc/systemd/system/kcm-cron.service /etc/systemd/system/kcm-cron.service.backup

# Use simplified version
sudo cp kcm/src/Modules/Systemd/kcm-cron.service /etc/systemd/system/kcm-cron.service

# Optionally replace CronService.php
# cp kcm/CronService.php kcm/CronService.php.backup
# cp kcm/CronService.simple.php kcm/CronService.php

sudo systemctl daemon-reload
sudo systemctl restart kcm-cron.service
```

---

### Step 3: Replace Crontab with Systemd Timer

```bash
# Install timer and service
sudo cp kcm/src/Modules/Systemd/kcm-process.service /etc/systemd/system/
sudo cp kcm/src/Modules/Systemd/kcm-process.timer /etc/systemd/system/

# Enable and start timer
sudo systemctl daemon-reload
sudo systemctl enable kcm-process.timer
sudo systemctl start kcm-process.timer

# Verify timer is active
systemctl list-timers kcm-process.timer

# Remove crontab entry
crontab -e  # Remove the CronMain.php line
```

---

### Step 4: Verify Everything Works

```bash
# Check data collection service
systemctl status kcm-cron.service

# Check processing timer
systemctl status kcm-process.timer
systemctl list-timers

# View logs
journalctl -u kcm-cron -f
journalctl -u kcm-process -f

# Check processes are running
ps aux | grep -E "(tcpdump|Parser|cat.*dev)"

# Check database for data
mysql -e "SELECT * FROM udp_data ORDER BY epoch DESC LIMIT 5;"
```

---

## Service File Comparison

### Current Service File
```ini
[Service]
ExecStart=/usr/bin/php /usr/src/KCM/CronService.php >> /tmp/KCM-cron.log
Restart=always
```
**Issues**:
- Hard-coded path
- File logging instead of journal
- No resource limits
- No security options

### Improved Service File (Monitored)
```ini
[Service]
Type=simple
ExecStart=/usr/bin/php /srv/kova/kcm/CronService.php
Restart=always
RestartSec=10
StandardOutput=journal
StandardError=journal
LimitNOFILE=65536
LimitNPROC=4096
```
**Improvements**:
- ✅ Correct path
- ✅ Systemd journal logging
- ✅ Resource limits
- ✅ Better restart control

### Simplified Service File (Native)
```ini
[Service]
Type=oneshot
ExecStart=/usr/bin/php /srv/kova/kcm/CronService.php
RemainAfterExit=yes
Restart=on-failure
RestartSec=30
StandardOutput=journal
StandardError=journal
```
**Improvements**:
- ✅ No long-running PHP process
- ✅ Systemd handles monitoring
- ✅ Event-driven restarts
- ✅ Lower resource usage

---

## Monitoring and Logging

### View Service Status
```bash
systemctl status kcm-cron.service
systemctl status kcm-process.timer
```

### View Logs
```bash
# Data collection logs
journalctl -u kcm-cron -f

# Processing logs
journalctl -u kcm-process -f

# All KCM logs
journalctl -u kcm-cron -u kcm-process -f

# Last 100 lines
journalctl -u kcm-cron -n 100

# Since boot
journalctl -u kcm-cron --since boot
```

### Check Process Status
```bash
# Check if processes are running
ps aux | grep -E "(tcpdump|Parser|cat.*dev)"

# Check process groups
ps -eo pid,pgid,comm | grep -E "(tcpdump|Parser|cat)"

# Check systemd service processes
systemctl status kcm-cron.service
```

---

## Troubleshooting

### Service Won't Start
```bash
# Check service status
systemctl status kcm-cron.service

# Check logs
journalctl -u kcm-cron -n 50

# Check for errors
journalctl -u kcm-cron -p err
```

### Processes Not Starting
```bash
# Check if modules are enabled in database
mysql -e "SELECT * FROM settings WHERE setname LIKE '%Page';"

# Check CronService logs
journalctl -u kcm-cron -f

# Manually test
php kcm/CronService.php
```

### Timer Not Firing
```bash
# Check timer status
systemctl status kcm-process.timer

# List all timers
systemctl list-timers

# Check timer logs
journalctl -u kcm-process.timer -f
```

---

## Rollback Plan

If you need to rollback:

```bash
# Stop new services
sudo systemctl stop kcm-cron.service
sudo systemctl stop kcm-process.timer

# Restore old service
sudo cp /etc/systemd/system/kcm-cron.service.backup /etc/systemd/system/kcm-cron.service

# Restore CronService
cp kcm/CronService.php.backup kcm/CronService.php

# Restore crontab
crontab -e  # Add back CronMain.php line

# Reload and restart
sudo systemctl daemon-reload
sudo systemctl restart kcm-cron.service
```

---

## Recommendation

**For your current setup, I recommend Option A (Simplified)**:

1. **Keep the monitoring loop** (it's working and provides good visibility)
2. **Improve the systemd service file** (better logging, resource limits)
3. **Replace crontab with systemd timer** (better integration)

This gives you:
- ✅ Minimal risk (keep existing monitoring)
- ✅ Better systemd integration
- ✅ Improved logging and resource management
- ✅ Easy rollback if needed

**Files to use**:
- `kcm-cron-monitored.service` - For the data collection service
- `kcm-process.timer` + `kcm-process.service` - For periodic processing

This approach improves your setup while maintaining the monitoring logic you already have.

