# Admin Page Access Guide

## ✅ Access Methods

The admin page is now accessible in multiple ways:

### 1. **Direct URL Access** (Recommended)
```
http://yoursite/src/Modules/Admin/index.php
```
or
```
http://yoursite/unified/src/Modules/Admin/index.php
```

### 2. **Via Navigation Bar**
The Admin link now appears in the navigation bar on all pages that use the `getNavBar()` function.

The link appears as a green "Admin" button in the top navigation.

### 3. **Via Dispatcher (AJAX)**
If you're using the unified frontend system, you can load it via AJAX:
```javascript
$.post("src/Dispatcher.php", { action: "getAdmin" }, function(data) {
    $("#mainDisplay").html(data);
});
```

## 🔐 Authentication

The admin page requires a password to access:

- **Default Password**: `kovaADMIN`
- **Configurable**: You can set `adminPassword` in your settings table to change it

On first access, you'll see a login form. After logging in, you'll remain logged in for the session.

## 🎨 Navigation Bar Integration

The Admin link is automatically added to the navigation bar:
- **Color**: Green (#4CAF50) to distinguish it from other modules
- **Visibility**: Enabled by default (can be controlled via `AdminPage` config setting)
- **Path**: `../Admin/index.php` (relative to where navbar is included)

### To Disable Admin Link:
Set `AdminPage = "no"` in your settings table.

## 📋 Superadmin Page

For full CRUD access (create/delete settings), use:
```
http://yoursite/src/Modules/Admin/superadmin.html
```

Or directly access the API:
```
http://yoursite/src/Modules/Admin/superadmin_api.php
```

## 🔧 Configuration

### Settings to Control Admin Access:

1. **`adminPassword`** - Password for regular admin access
2. **`superadminPassword`** - Password for superadmin access
3. **`AdminPage`** - Set to "no" to hide Admin link from navbar (default: "yes")

## 📁 File Structure

```
unified/src/Modules/Admin/
├── index.php              ← Main entry point (NEW!)
├── admin.php              ← Alternative entry point
├── admin.html             ← Admin UI template
├── superadmin.html        ← Superadmin UI template
├── AdminController.php    ← Controller logic
├── AdminApi.php           ← Regular admin API (GET, PUT only)
├── SuperadminApi.php      ← Superadmin API (full CRUD)
├── api.php                ← Admin API entry point
└── superadmin_api.php     ← Superadmin API entry point
```

## ✨ Features

### Regular Admin (`index.php`):
- ✅ View all settings
- ✅ Update existing settings (name, value, type, description)
- ❌ Cannot create new settings
- ❌ Cannot delete settings
- ❌ Cannot change group_name or sort_order

### Superadmin (`superadmin.html`):
- ✅ Full CRUD access
- ✅ Create new settings
- ✅ Delete settings
- ✅ Update all fields including group_name and sort_order

## 🚀 Quick Start

1. **Access the page**: Navigate to `/src/Modules/Admin/index.php`
2. **Login**: Enter password (default: `kovaADMIN`)
3. **Edit settings**: Use the form to update settings
4. **Save**: Click "Save Settings" to persist changes

## 🔒 Security Notes

- Change default passwords in production
- Consider implementing proper user authentication
- Session-based authentication is used (session expires on browser close)
- Passwords are currently stored in plain text in the settings table (consider hashing for production)

