# Systemd Service Management - Admin Integration

## ✅ Integration Complete

Systemd Template Units (Option 2) has been successfully integrated into the Admin module, allowing web-based management of individual cron services.

---

## 📁 Files Created

### Backend Components

1. **`unified/src/Modules/Admin/Services/SystemdServiceManager.php`**
   - Manages systemd services for cron modules
   - Provides: start, stop, restart, enable, disable, status, logs
   - Safely executes systemctl commands
   - Monitors process status

2. **`unified/src/Modules/Admin/Handlers/CronServiceHandler.php`**
   - Handles API requests for cron service management
   - Routes GET and POST requests to SystemdServiceManager

3. **`unified/src/Modules/Admin/AdminApi.php`**
   - Main API class with service management endpoints
   - Routes: `?action=cron-status` and `?action=cron-service`

4. **`unified/src/Modules/Admin/api.php`**
   - Entry point for Admin API
   - Handles all admin requests including service management

### Systemd Components

5. **`kcm/src/Modules/Systemd/kcm-cron@.service`**
   - Systemd template unit for individual module services
   - Usage: `systemctl start kcm-cron@moto`

6. **`kcm/src/Modules/Crons/CronModuleStarter.php`**
   - Script called by systemd to start individual modules
   - Checks module enablement
   - Starts processes and manages PID files

### CronController Updates

7. **`kcm/src/Modules/Crons/CronController.php`**
   - Added `StartCronsForModule(string $module): array`
   - Added `StopCronsForModule(string $module): void`
   - Enables per-module management

### Frontend

8. **`unified/src/Modules/Admin/admin-services.html`**
   - Web UI for managing cron services
   - Real-time status updates
   - Start/stop/restart/enable/disable controls
   - Process monitoring

---

## 🎯 API Endpoints

### Get Service Status

**GET** `api.php?action=cron-status`

**Optional**: `?action=cron-status&module=moto` (single module)

**Response**:
```json
{
  "success": true,
  "data": {
    "moto": {
      "module": "moto",
      "service": "kcm-cron@moto",
      "status": "active",
      "enabled": true,
      "active": true,
      "processes": {
        "count": 2,
        "details": {
          "tcpdump": ["12345 tcpdump ..."],
          "parser": ["12346 php MotoParser.php"]
        }
      }
    },
    "serial": { ... },
    "udp": { ... },
    "zabbix": { ... }
  }
}
```

### Manage Services

**POST** `api.php?action=cron-service`

**Body**:
```json
{
  "action": "start|stop|restart|enable|disable",
  "module": "moto|serial|udp|zabbix"
}
```

**Response**:
```json
{
  "success": true,
  "message": "Service kcm-cron@moto started successfully",
  "module": "moto"
}
```

---

## 🚀 Installation Steps

### 1. Install Systemd Template Unit

```bash
# Copy template unit file
sudo cp kcm/src/Modules/Systemd/kcm-cron@.service /etc/systemd/system/

# Reload systemd
sudo systemctl daemon-reload
```

### 2. Enable Services (Optional)

```bash
# Enable individual modules
sudo systemctl enable kcm-cron@moto.service
sudo systemctl enable kcm-cron@serial.service
sudo systemctl enable kcm-cron@udp.service
sudo systemctl enable kcm-cron@zabbix.service

# Start services
sudo systemctl start kcm-cron@moto.service
```

### 3. Verify Installation

```bash
# Check service status
systemctl status kcm-cron@moto.service

# View logs
journalctl -u kcm-cron@moto -f
```

---

## 📖 Usage

### Web UI

1. **Access Admin Page**: Navigate to admin page
2. **Open Service Management**: Click "Cron Services" button or navigate to `admin-services.html`
3. **Manage Services**: Use Start/Stop/Restart/Enable/Disable buttons
4. **Monitor Status**: Status updates automatically every 30 seconds

### Command Line

```bash
# Start a module
sudo systemctl start kcm-cron@moto

# Stop a module
sudo systemctl stop kcm-cron@serial

# Restart a module
sudo systemctl restart kcm-cron@udp

# Enable on boot
sudo systemctl enable kcm-cron@zabbix

# Disable on boot
sudo systemctl disable kcm-cron@moto

# Check status
systemctl status kcm-cron@moto

# View logs
journalctl -u kcm-cron@moto -f
```

---

## 🔄 Migration from CronService

### Option A: Gradual Migration

1. Install systemd template units
2. Keep `CronService.php` running (monitors all modules)
3. Use web UI to manage individual modules
4. Gradually migrate to individual services

### Option B: Full Migration

1. Install systemd template units
2. Stop `CronService.php` systemd service
3. Enable individual module services
4. Use web UI for all management

---

## 🎨 UI Features

### Service Cards

Each module has a service card showing:
- **Module Name**: MOTO, SERIAL, UDP, ZABBIX
- **Status Badge**: Active (green), Inactive (red), Not Installed (gray)
- **Service Info**: Service name, enabled status, process count
- **Controls**: Start/Stop, Restart, Enable/Disable, View Logs
- **Process Info**: Running tcpdump/cat/parser processes

### Auto-Refresh

- Status updates every 30 seconds
- Manual refresh button
- Real-time status changes

---

## 🔐 Security

### Authentication

- Service management requires admin authentication
- Uses session-based auth (same as settings management)
- All API requests validated

### Command Execution

- All systemctl commands use `escapeshellarg()`
- Input validation for module names
- Process info gathered safely

---

## 📊 Benefits

### 1. Granular Control
- ✅ Start/stop individual modules
- ✅ Enable/disable per module
- ✅ Monitor each module independently

### 2. Better Monitoring
- ✅ Per-module status
- ✅ Process information
- ✅ Systemd journal integration

### 3. Web-Based Management
- ✅ No SSH required
- ✅ Easy to use interface
- ✅ Real-time status updates

### 4. Systemd Best Practices
- ✅ Template units
- ✅ Proper service management
- ✅ Resource limits
- ✅ Logging integration

---

## 🐛 Troubleshooting

### Service Not Found

**Error**: "Service kcm-cron@moto is not installed"

**Solution**:
```bash
sudo cp kcm/src/Modules/Systemd/kcm-cron@.service /etc/systemd/system/
sudo systemctl daemon-reload
```

### Permission Denied

**Error**: "Failed to start service"

**Solution**: Ensure web server user has sudo access or systemd permissions:
```bash
# Add to sudoers (adjust as needed)
www-data ALL=(ALL) NOPASSWD: /bin/systemctl start kcm-cron@*, /bin/systemctl stop kcm-cron@*, /bin/systemctl restart kcm-cron@*
```

### Module Not Enabled

**Error**: Service starts but no processes

**Solution**: Check module enablement in database:
```sql
SELECT * FROM settings WHERE setname LIKE '%Page' AND setvalue = 'yes';
```

---

## 📝 Next Steps

1. **Test Installation**: Install template units and test via web UI
2. **Migrate Gradually**: Start with one module, then expand
3. **Monitor Logs**: Use `journalctl` to monitor service behavior
4. **Customize**: Adjust resource limits and security options in service file

---

## 🎉 Summary

The Admin module now provides full systemd service management capabilities:

- ✅ Web-based UI for managing cron services
- ✅ Individual module control (start/stop/restart)
- ✅ Enable/disable on boot
- ✅ Real-time status monitoring
- ✅ Process information display
- ✅ Systemd journal integration
- ✅ Secure authentication required

This follows systemd best practices and provides a modern, user-friendly interface for managing cron services.

