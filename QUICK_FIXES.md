# Quick Fixes - Immediate Improvements

## 1. Remove All Debug Statements (5 minutes)

**Find and replace:**
```bash
# Remove var_dump
grep -r "var_dump" kcm/src/ unified/src/

# Replace with:
$this->logger->debug('Message', ['context' => $data]);
```

**Files to fix:**
- `kcm/src/Modules/Motorola/MotoCron.php` - 6 instances
- `kcm/src/Modules/Crons/CronController.php` - 2 instances
- `kcm/src/Modules/Serial/SerialCron.php` - Multiple instances

---

## 2. Fix SQL Injection (CRITICAL - 10 minutes)

**File:** `kcm/src/Modules/Serial/SerialCron.php:328`

**Before:**
```php
$query = "Insert INTO Entries (Name,Roles,AgentID) VALUES ('$Name','$Role','$Number')";
$result = mysqli_query($link, $query);
```

**After:**
```php
$this->dbConn->insert('Entries', [
    'Name' => $Name,
    'Roles' => $Role,
    'AgentID' => $Number
]);
```

**Also check:**
- `unified/src/Modules/Server/channels-check-ajax.php` - Replace mysqli with Database class

---

## 3. Replace Echo with Logger (5 minutes)

**Find:**
```bash
grep -r "echo.*PHP_EOL" kcm/src/
```

**Replace:**
```php
// Before
echo "Start Cron DB vars" . PHP_EOL;

// After
$this->logger->info('Start Cron DB vars');
```

---

## 4. Add Command Escaping (10 minutes)

**File:** `kcm/src/Modules/Motorola/MotoCron.php:44`

**Before:**
```php
$command = "timeout 6000 /usr/bin/tcpdump ... -i " . $iArray[0] . " ...";
```

**After:**
```php
$command = "timeout 6000 /usr/bin/tcpdump ... -i " . escapeshellarg($iArray[0]) . " ...";
```

**Or better - use Process class (see FUNCTIONALITY_IMPROVEMENTS.md)**

---

## 5. Add Input Validation (15 minutes)

**Add to all input points:**
```php
// Validate interface name
if (!preg_match('/^[a-z0-9_-]+$/i', $iface)) {
    throw new InvalidArgumentException("Invalid interface name");
}

// Validate Agent ID
if (empty($Number) || !is_numeric($Number)) {
    throw new InvalidArgumentException("Invalid Agent ID");
}
```

---

## 6. Add Error Handling to File Operations (10 minutes)

**Before:**
```php
$rawFile = file($this->fileName . "-" . $iface['ifaceid']);
```

**After:**
```php
$filepath = $this->fileName . "-" . $iface['ifaceid'];
if (!file_exists($filepath)) {
    $this->logger->warning('File not found', ['file' => $filepath]);
    return [];
}

$rawFile = @file($filepath);
if ($rawFile === false) {
    $this->logger->error('Failed to read file', ['file' => $filepath]);
    throw new FileException("Cannot read file: $filepath");
}
```

---

## 7. Replace Magic Numbers with Constants (5 minutes)

**Before:**
```php
case "Hours":
    $timeCheck = time() - ($row[4] * 3600);
    break;
case "Days":
    $timeCheck = time() - ($row[4] * 86400);
    break;
```

**After:**
```php
private const SECONDS_PER_HOUR = 3600;
private const SECONDS_PER_DAY = 86400;
private const SECONDS_PER_WEEK = 604800;

case "Hours":
    $timeCheck = time() - ($row[4] * self::SECONDS_PER_HOUR);
    break;
```

---

## 8. Add Return Types to Methods (10 minutes)

**Before:**
```php
public function getMotoData()
{
```

**After:**
```php
public function getMotoData(): array
{
```

---

## Total Time: ~70 minutes for all quick fixes

These can be done immediately and will significantly improve code quality and security.

