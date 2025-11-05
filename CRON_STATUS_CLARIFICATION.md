# Cron Module Status - Clarification

## Three Separate Concepts

### 1. Database Enablement (Module Configuration)
- **Setting**: `MotorolaPage`, `SerialPage`, `UdpPage`, `ZabbixPage` in `settings` table
- **Values**: `yes`, `1`, `true`, `on` = enabled
- **Purpose**: Determines if the module should be active in the system
- **Checked by**: `ModuleStatusService::isDatabaseEnabled()`
- **Used by**: CronController to decide if module should start

### 2. Service Running State (Active/Inactive)
- **Systemd State**: `active` or `inactive`
- **Purpose**: Is the service currently running?
- **Controlled by**: `systemctl start/stop` (or Pause/Resume in UI)
- **Checked by**: `systemctl is-active`
- **Independent of**: Database enablement and boot enablement

### 3. Boot Enablement (Start on Boot)
- **Systemd State**: `enabled` or `disabled`
- **Purpose**: Will the service start automatically on system boot?
- **Controlled by**: `systemctl enable/disable` (or Enable Boot/Disable Boot in UI)
- **Checked by**: `systemctl is-enabled`
- **Independent of**: Database enablement and running state

---

## Status Matrix

| Database Enabled | Service Running | Boot Enabled | Operational Status | Alarm Needed? |
|-----------------|-----------------|--------------|-------------------|---------------|
| ✅ Yes | ✅ Yes | ✅ Yes | `operational` | ❌ No |
| ✅ Yes | ✅ Yes | ❌ No | `operational` | ❌ No |
| ✅ Yes | ❌ No | ✅ Yes | `enabled_but_stopped` | ✅ **Yes** |
| ✅ Yes | ❌ No | ❌ No | `enabled_but_stopped` | ✅ **Yes** |
| ❌ No | ✅ Yes | ✅ Yes | `disabled` | ❌ No |
| ❌ No | ✅ Yes | ❌ No | `disabled` | ❌ No |
| ❌ No | ❌ No | ✅ Yes | `disabled` | ❌ No |
| ❌ No | ❌ No | ❌ No | `disabled` | ❌ No |

---

## Common Scenarios

### Scenario 1: Normal Operation
```
Database: MotorolaPage = "yes"
Service: kcm-cron@moto active
Boot: enabled
Result: operational_status = "operational"
Alarm: No
```

### Scenario 2: Paused (Temporary Stop)
```
Database: MotorolaPage = "yes"
Service: kcm-cron@moto inactive (paused)
Boot: enabled
Result: operational_status = "enabled_but_stopped"
Alarm: YES - Module is enabled but not running
```

### Scenario 3: Disabled Module
```
Database: MotorolaPage = "no"
Service: kcm-cron@moto inactive
Boot: enabled (but won't matter, database disabled)
Result: operational_status = "disabled"
Alarm: No - Intentionally disabled
```

### Scenario 4: Service Not Installed
```
Database: MotorolaPage = "yes"
Service: kcm-cron@moto not-installed
Boot: N/A
Result: operational_status = "failed"
Alarm: YES - Critical: Module enabled but service missing
```

---

## Pause vs Disable

### Pause (Stop Service)
- **Action**: `systemctl stop kcm-cron@moto`
- **Effect**: Service stops running
- **Database**: Unchanged (still enabled)
- **Boot**: Unchanged (still enabled if it was)
- **Alarm**: YES - Should be raised (enabled but stopped)

### Disable (Remove from Boot)
- **Action**: `systemctl disable kcm-cron@moto`
- **Effect**: Service won't start on boot
- **Database**: Unchanged
- **Running**: Unchanged (doesn't stop if running)
- **Alarm**: Depends on running state

---

## Using ModuleStatusService

### Check Status
```php
use Kova\Kams\Core\ModuleStatusService;

$statusService = new ModuleStatusService();
$status = $statusService->getModuleStatus('moto');

// Check if enabled in database
if ($status['database_enabled']) {
    // Module should be running
}

// Check if actually running
if ($status['service_running']) {
    // Service is active
}

// Check operational status
if ($status['operational_status'] === 'enabled_but_stopped') {
    // Raise alarm - module enabled but not running
}
```

### Get Alarms
```php
$alarms = $statusService->getModulesNeedingAlarms();

// Returns modules that need alarms:
// - database_enabled = true, service_running = false
// - database_enabled = true, operational_status = 'failed'
```

---

## Integration Points

### 1. CronController
- Checks `database_enabled` before starting modules
- Only starts modules where `MotorolaPage` = "yes"

### 2. Alarm Handler
- Uses `getModulesNeedingAlarms()` to find issues
- Raises alarms for `enabled_but_stopped` and `failed`

### 3. Admin Module
- Shows all three states in UI
- Allows pause/resume without changing database enablement
- Allows enable/disable boot without changing database enablement

### 4. Monitoring/Health Checks
- Periodically checks `getAllModuleStatus()`
- Alerts on `enabled_but_stopped` or `failed` status

---

## Summary

**Key Points:**
1. **Database Enabled** = Module configuration (should it be active?)
2. **Service Running** = Current state (is it running now?)
3. **Boot Enabled** = Startup behavior (will it start on boot?)

**Alarm Logic:**
- If `database_enabled = true` AND `service_running = false` → **Raise Alarm**
- If `database_enabled = true` AND `operational_status = 'failed'` → **Raise Alarm**
- If `database_enabled = false` → **No Alarm** (intentionally disabled)

**Pause vs Disable:**
- **Pause** = Temporary stop (service inactive, database still enabled) → **Triggers Alarm**
- **Disable** = Remove from boot (doesn't affect current running state) → **May or may not trigger alarm depending on running state**

