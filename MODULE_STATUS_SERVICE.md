# Module Status Service - Unified Status Management

## Overview

The `ModuleStatusService` provides a unified view of module status combining:
1. **Database Enablement** - Is the module enabled in settings (MotorolaPage, SerialPage, etc.)?
2. **Service Running** - Is the systemd service currently active?
3. **Boot Enablement** - Will the service start on boot?

This service is used throughout the application for status checking and alarm detection.

---

## Status States

### Operational Status Values

| Status | Description | Database Enabled | Service Running |
|--------|-------------|------------------|-----------------|
| `operational` | Module is enabled and running | ✅ Yes | ✅ Yes |
| `enabled_but_stopped` | Module is enabled but paused/stopped | ✅ Yes | ❌ No |
| `disabled` | Module is disabled in database | ❌ No | ❌ No |
| `failed` | Module enabled but service not installed | ✅ Yes | ❌ No (not-installed) |
| `unknown` | Status cannot be determined | ? | ? |

---

## Usage

### Get Status for Single Module

```php
use Kova\Kams\Core\ModuleStatusService;

$statusService = new ModuleStatusService();
$status = $statusService->getModuleStatus('moto');

// Returns:
[
    'module' => 'moto',
    'database_enabled' => true,
    'service_running' => true,
    'boot_enabled' => true,
    'operational_status' => 'operational',
    'service_name' => 'kcm-cron@moto',
    'service_status' => 'active',
    'processes' => [
        'count' => 2,
        'details' => [
            'tcpdump' => [...],
            'parser' => [...]
        ]
    ],
    'last_check' => 1234567890
]
```

### Get Status for All Modules

```php
$allStatus = $statusService->getAllModuleStatus();
// Returns array keyed by module name
```

### Check for Alarms

```php
$alarms = $statusService->getModulesNeedingAlarms();

// Returns modules that need alarms:
[
    'moto' => [
        'module' => 'moto',
        'reason' => 'enabled_but_stopped',
        'message' => 'Module moto is enabled but service is not running',
        'status' => [...]
    ]
]
```

### Check Database Enablement

```php
$isEnabled = $statusService->isDatabaseEnabled('moto');
// Returns: true/false
```

---

## Database Enablement Settings

Modules are enabled/disabled via settings table:

| Module | Setting Name | Values |
|--------|-------------|--------|
| Motorola | `MotorolaPage` | `yes`, `1`, `true`, `on` = enabled |
| Serial | `SerialPage` | `yes`, `1`, `true`, `on` = enabled |
| UDP | `UdpPage` | `yes`, `1`, `true`, `on` = enabled |
| Zabbix | `ZabbixPage` | `yes`, `1`, `true`, `on` = enabled |

---

## Integration with Alarms

### Example: Alarm Handler

```php
use Kova\Kams\Core\ModuleStatusService;

$statusService = new ModuleStatusService();
$alarms = $statusService->getModulesNeedingAlarms();

foreach ($alarms as $module => $alarm) {
    if ($alarm['reason'] === 'enabled_but_stopped') {
        // Raise alarm: Module enabled but not running
        raiseAlarm([
            'type' => 'module_stopped',
            'module' => $module,
            'message' => $alarm['message'],
            'severity' => 'warning'
        ]);
    } elseif ($alarm['reason'] === 'failed') {
        // Raise alarm: Module enabled but service not installed
        raiseAlarm([
            'type' => 'module_failed',
            'module' => $module,
            'message' => $alarm['message'],
            'severity' => 'critical'
        ]);
    }
}
```

### Example: Status Check in Cron Processing

```php
use Kova\Kams\Core\ModuleStatusService;

$statusService = new ModuleStatusService();

// Before processing, check if module should be running
$status = $statusService->getModuleStatus('moto');

if ($status['database_enabled'] && !$status['service_running']) {
    // Module is enabled but stopped - this is a problem
    logWarning("Module moto is enabled but service is not running");
    
    // Optionally restart service
    // systemctl start kcm-cron@moto
}
```

---

## Status Flow

### Normal Operation

```
Database: MotorolaPage = "yes"
Systemd: kcm-cron@moto active
Result: operational_status = "operational"
```

### Paused (Enabled but Stopped)

```
Database: MotorolaPage = "yes"
Systemd: kcm-cron@moto inactive (but enabled for boot)
Result: operational_status = "enabled_but_stopped"
Alarm: Should be raised
```

### Disabled

```
Database: MotorolaPage = "no"
Systemd: kcm-cron@moto inactive
Result: operational_status = "disabled"
Alarm: No alarm (intentionally disabled)
```

### Failed (Service Not Installed)

```
Database: MotorolaPage = "yes"
Systemd: kcm-cron@moto not-installed
Result: operational_status = "failed"
Alarm: Should be raised (critical)
```

---

## Admin Module Integration

The Admin module uses `SystemdServiceManager` which internally uses `ModuleStatusService` to provide unified status including database enablement.

**UI Display:**
- **Running**: Shows if service is active
- **Enabled for Boot**: Shows if service starts on boot
- **Database Enabled**: Can be added to show database enablement status
- **Operational Status**: Overall status for alarms

---

## Benefits

1. **Unified Status**: Single source of truth for module status
2. **Alarm Detection**: Easy to find modules that need attention
3. **Consistent**: Same status logic used everywhere
4. **Maintainable**: Changes to status logic in one place
5. **Testable**: Can be easily tested in isolation

---

## Future Enhancements

1. **Status History**: Track status changes over time
2. **Health Checks**: Add process health validation
3. **Auto-Recovery**: Automatically restart failed modules
4. **Status Caching**: Cache status for performance
5. **Webhooks**: Notify external systems of status changes

