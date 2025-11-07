# Motorola Status Implementation - v2 Standards

## Overview

The Motorola status functionality has been integrated from v1/channels into v2 using modern standards (DBAL QueryBuilder, namespaces, type safety, etc.).

## Changes Made

### 1. **Dataworker Refactoring** (`unified/src/Modules/Motorola/Workers/Dataworker.php`)

**Before:**
- Used deprecated `dbQuery()` method
- Array index access (`$rowOne[3]`, `$rowOne[4]`, etc.)
- Incorrect time calculation logic
- Hardcoded database connection

**After:**
- ✅ Uses DBAL QueryBuilder for all database operations
- ✅ Named column access (`$row['last_activity']`, etc.)
- ✅ Proper time threshold calculation
- ✅ Uses `Kova\Kams\Common\Database`
- ✅ Type-safe method signatures
- ✅ Proper error handling

**Key Methods:**
- `getMotoData()`: Returns `['activeChannels' => int, 'issues' => int, 'notMonitored' => int]`
- `calculateTimeThreshold()`: Calculates timeout thresholds based on Hours/Days/Weeks
- `getLastPacketStamp()`: Gets last packet timestamp for an interface (refactored to use QueryBuilder)

### 2. **ChannelStatus Class** (`unified/src/Modules/Motorola/ChannelStatus.php`)

**New class** that provides the channel status display functionality:

- **`renderStatus()`**: Main method that renders all sections
- **`renderWarnings()`**: Displays channels with timeout issues (red/yellow buttons)
- **`renderActiveChannels()`**: Displays channels with recent activity (green buttons)
- **`renderUnmonitoredChannels()`**: Displays channels with timeout_number = 0 (gray buttons)

**Features:**
- Uses DBAL QueryBuilder
- Proper HTML escaping for security
- Time threshold calculation (Hours/Days/Weeks)
- Bootstrap button styling matching v1 design

### 3. **channels-check-ajax.php** (`unified/src/Modules/Motorola/channels-check-ajax.php`)

**New entry point** that loads bootstrap and renders channel status:

- Simple wrapper that instantiates `ChannelStatus` and calls `renderStatus()`
- Uses v2 bootstrap for autoloading and configuration

### 4. **index.php Update** (`unified/src/Modules/Motorola/index.php`)

**Refactored** to use v2 standards:

- ✅ Uses `Kova\Kams\Common\Config` instead of hardcoded includes
- ✅ Type-safe config access (`getBool()`, `getString()`)
- ✅ Proper HTML escaping
- ✅ Updated asset paths to use `../Bones/` structure
- ✅ Removed hardcoded database connections
- ✅ Auto-refreshes every 15 seconds via AJAX

### 5. **Dispatcher Integration**

**Added** `getMotoData` action to `Dispatcher.php`:

```php
case "getMotoData":
    $cargo = new motoDataworker($configs);
    header('Content-Type: application/json');
    echo json_encode($cargo->getMotoData());
    break;
```

This allows the home page (`Home.php`) to fetch Motorola status data via AJAX.

### 6. **Moto.php Status**

The `Moto.php::motoSum()` method already returns the correct HTML structure with IDs (`motoActive`, `motoWarnings`, `motoNM`) that are populated by `kova.js` via the `getMotoData` action.

## Database Structure

**Table**: `moto_channel_data`

Columns:
- `id` (mediumint, primary key)
- `channel_id` (int) - Channel identifier
- `channel_name` (varchar) - Display name
- `last_activity` (int) - Unix timestamp of last activity
- `timeout_number` (smallint) - Timeout value (0 = unmonitored)
- `timeout_value` (varchar) - Timeout unit: "Hours", "Days", "Weeks"
- `alarm` (varchar) - Alarm configuration
- `spare_3` (varchar) - Reserved

## Functionality

### Status Categories

1. **Active Channels** (Green buttons)
   - Channels with `timeout_number <> 0`
   - `last_activity` is within the timeout threshold
   - Shows: Channel name, last activity timestamp, timeout settings

2. **Warnings** (Yellow/Red buttons)
   - Channels with `timeout_number <> 0`
   - `last_activity` is older than timeout threshold OR no activity recorded
   - Yellow: Activity older than timeout
   - Red: No activity ever recorded

3. **Unmonitored Channels** (Gray buttons)
   - Channels with `timeout_number = 0`
   - Not actively monitored for timeouts
   - Shows: Channel name and last activity (if any)

### Time Calculation

The system calculates time thresholds based on:
- **Hours**: `timeNow - (timeout_number * 3600)`
- **Days**: `timeNow - (timeout_number * 86400)`
- **Weeks**: `timeNow - (timeout_number * 604800)`

## Access Points

1. **Standalone Page**: `/unified/src/Modules/Motorola/index.php`
   - Full channel status page with auto-refresh
   - Includes navigation bar
   - Shows all three sections (Warnings, Active, Unmonitored)

2. **AJAX Endpoint**: `/unified/src/Modules/Motorola/channels-check-ajax.php`
   - Returns HTML for the status sections
   - Used by `index.php` for auto-refresh

3. **Home Page Summary**: Via `getMotoData` action
   - Returns JSON: `{activeChannels: int, issues: int, notMonitored: int}`
   - Populates `#motoActive`, `#motoWarnings`, `#motoNM` on home page

## Integration with v2 Standards

✅ **DBAL QueryBuilder**: All database queries use QueryBuilder  
✅ **Type Safety**: Strict types, proper type hints  
✅ **Namespaces**: `Kova\Kams\Unified\Modules\Motorola`  
✅ **Config Class**: Uses `Kova\Kams\Common\Config`  
✅ **Database Class**: Uses `Kova\Kams\Common\Database`  
✅ **Security**: HTML escaping, parameterized queries  
✅ **Error Handling**: Proper exception handling  
✅ **Code Organization**: Separated concerns (Dataworker, ChannelStatus, entry points)

## Testing

To test the implementation:

1. **Home Page**: Check that Motorola status shows correct counts
2. **Standalone Page**: Navigate to `/unified/src/Modules/Motorola/index.php`
3. **Auto-refresh**: Verify page updates every 15 seconds
4. **Database**: Ensure `moto_channel_data` table has data

## Next Steps (Optional Enhancements)

1. **Admin Interface**: Add channel management (add/edit/delete channels)
2. **Alarm System**: Integrate with alarm handler for timeout warnings
3. **Historical Data**: Track channel activity over time
4. **Notifications**: Email/SMS alerts for channel timeouts
5. **Dashboard Widget**: Add channel status widget to home page

