# Unified Frontend Entry Point

## 🎯 Main Entry Point

**File**: `unified/index.html`

**URL**: 
```
http://yoursite/unified/index.html
```
or
```
http://yoursite/unified/
```

## 📋 Architecture Overview

### Frontend Flow:
```
index.html
  ↓
Loads jQuery + kova.js
  ↓
On document ready:
  - getNavBar() → Loads navigation bar
  - getHome() → Loads home page content
  ↓
AJAX POST requests to:
  src/Dispatcher.php?action=<action>
```

### Backend Entry Point:

**File**: `unified/src/Dispatcher.php`

**Purpose**: Routes all AJAX requests based on `action` parameter

## 🔄 Request Flow

### 1. **Initial Page Load**
```
GET /unified/index.html
  ↓
Loads HTML structure
  ↓
JavaScript executes:
  - getNavBar() → POST to Dispatcher.php?action=getNavBar
  - getHome() → POST to Dispatcher.php?action=getHome
```

### 2. **AJAX Requests**
All subsequent requests go through:
```
POST /unified/src/Dispatcher.php
Parameters:
  - action: "getNavBar" | "getHome" | "getAdmin" | "getUDPAvgs" | etc.
```

## 📁 File Structure

```
unified/
├── index.html              ← Main entry point (USER FACING)
├── src/
│   ├── Dispatcher.php      ← Backend router (AJAX endpoint)
│   ├── Bones/
│   │   └── js/
│   │       └── kova.js     ← Frontend JavaScript functions
│   └── Modules/
│       ├── Admin/
│       ├── Home/
│       ├── UDP/
│       ├── Serial/
│       └── ...
```

## 🎨 Key Components

### `index.html`
- Main HTML structure
- Loads CSS and JavaScript libraries
- Contains empty containers:
  - `#siteName` - Site name display
  - `.topnav` - Navigation bar container
  - `#mainDisplay` - Main content area

### `src/Dispatcher.php`
- Routes all AJAX requests
- Handles actions:
  - `getAdmin` - Admin page
  - `getHome` - Home page content
  - `getNavBar` - Navigation bar
  - `getSiteName` - Site name
  - `getUDPAvgs` - UDP averages
  - `getSerialAvgs` - Serial averages
  - `getMotoData` - Motorola data
  - `getSysHealth` - System health
  - `getIfaces` - Interface list
  - `updateSettings` - Update settings (admin)

### `src/Bones/js/kova.js`
- JavaScript functions for AJAX calls
- Main functions:
  - `getNavBar()` - Loads navigation
  - `getHome()` - Loads home content
  - `getUDPTable()` - Loads UDP table
  - `loadIfacePage()` - Loads interface page
  - `loadUDPSumGauges()` - Loads gauge charts

## 🚀 Access URLs

### Main Dashboard:
```
http://yoursite/unified/index.html
```

### Admin Page:
```
http://yoursite/unified/src/Modules/Admin/index.php
```

### Direct Module Pages:
```
http://yoursite/unified/src/Modules/Motorola/index.php
http://yoursite/unified/src/Modules/Serial/index.php
http://yoursite/unified/src/Modules/UDP/Workers/index.php
```

### API Endpoints:
```
POST http://yoursite/unified/src/Dispatcher.php
POST http://yoursite/unified/src/Modules/Admin/api.php
POST http://yoursite/unified/src/Modules/Admin/superadmin_api.php
```

## 🔧 Configuration

The frontend loads configuration through:
- `Dispatcher.php` → Creates `Config` object
- `Config` class reads from database/settings

## 📝 Example Usage

### Loading Admin Page via AJAX:
```javascript
$.post("src/Dispatcher.php", { action: "getAdmin" }, function(data) {
    $("#mainDisplay").html(data);
});
```

### Getting Navigation Bar:
```javascript
$.post("src/Dispatcher.php", { action: "getNavBar" }, function(data) {
    $(".topnav").html(data);
});
```

## 🎯 Summary

**Primary Entry Point**: `unified/index.html`
- User-facing HTML file
- Loads JavaScript that makes AJAX calls
- Updates page content dynamically

**Backend Entry Point**: `unified/src/Dispatcher.php`
- Handles all AJAX routing
- Connects frontend to backend modules
- Returns HTML/JSON based on action

