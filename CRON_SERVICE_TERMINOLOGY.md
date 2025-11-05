# Cron Service Management - Terminology Guide

## Current Implementation Logic

### Systemd Service States

The Admin module uses two independent concepts:

1. **Active/Inactive** (Service State)
   - **Active** = Service is currently running
   - **Inactive** = Service is currently stopped
   - Controlled by: `start` / `stop` / `restart` actions

2. **Enabled/Disabled** (Boot State)
   - **Enabled** = Service will start automatically on boot
   - **Disabled** = Service will NOT start on boot
   - Controlled by: `enable` / `disable` actions

### Important Distinction

**You CAN pause a cron without disabling it!**

- **Stop** = Pause the service (it's inactive) but keep it enabled for boot
- **Disable** = Remove from boot startup (but doesn't stop if already running)
- **Start** = Resume a paused service
- **Enable** = Allow service to start on boot

### Example Scenarios

**Scenario 1: Pause without disabling**
- Service is **Active** and **Enabled**
- Click "Stop" → Service becomes **Inactive** but remains **Enabled**
- On reboot, service will start automatically (because it's enabled)
- Click "Start" to resume

**Scenario 2: Disable but keep running**
- Service is **Active** and **Enabled**
- Click "Disable" → Service remains **Active** but becomes **Disabled**
- On reboot, service will NOT start (because it's disabled)
- Service continues running until stopped

**Scenario 3: Full disable**
- Service is **Active** and **Enabled**
- Click "Stop" → **Inactive**, **Enabled**
- Click "Disable" → **Inactive**, **Disabled**
- Service won't run now or on reboot

---

## Current UI Behavior

The UI shows:
- **Status Badge**: Shows "Active" or "Inactive" (current running state)
- **Enabled field**: Shows "Yes" or "No" (boot startup state)
- **Controls**: 
  - Start/Stop buttons (toggle active state)
  - Enable/Disable buttons (toggle boot state)

---

## Recommended: Add "Pause/Resume" Terminology

To make it clearer, we can add:
- **Pause** = Stop (but keep enabled) - clearer terminology
- **Resume** = Start (if paused)

This makes it explicit that you're pausing without disabling.

