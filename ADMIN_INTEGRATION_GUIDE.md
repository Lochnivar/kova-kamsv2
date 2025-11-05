# Admin Page Integration Guide

## Overview

I've created a refactored admin module that follows the current codebase patterns and uses the Database class instead of direct mysqli connections.

## Files Created

### 1. `unified/src/Modules/Admin/AdminController.php`
- **Purpose**: Main admin controller class
- **Features**:
  - Uses Database class (no direct mysqli)
  - Proper error handling
  - Input validation
  - AJAX support
  - Follows namespace conventions

### 2. `unified/src/Modules/Admin/admin.php`
- **Purpose**: Entry point for admin page
- **Features**:
  - Session-based authentication
  - Uses AdminController
  - Can be accessed directly or via routing

## Integration Points

### Option 1: Direct Access (Recommended)
Access the admin page directly:
```
http://yoursite/src/Modules/Admin/admin.php
```

### Option 2: Via Dispatcher (AJAX)
Already integrated in `Dispatcher.php`:
```javascript
// In your frontend JavaScript
$.ajax({
    url: 'src/Dispatcher.php',
    method: 'POST',
    data: { action: 'getAdmin' },
    success: function(html) {
        $('#adminContent').html(html);
    }
});
```

## Using Your Own Admin Code

If you have existing admin code, here's how to integrate it:

### Step 1: Create Your Admin Class

Create `unified/src/Modules/Admin/YourAdminController.php`:

```php
<?php

namespace Kova\Kams\Unified\Modules\Admin;

use Kova\Kams\Common\Database;
use Kova\Kams\Unified\Modules\Common\Config;

class YourAdminController
{
    private Database $db;
    private array $config;

    public function __construct(?Config $config = null)
    {
        $this->db = new Database('kams');
        $this->config = $config ? $config->config : (new Config())->config;
    }

    // Add your admin methods here
    public function yourMethod(): string
    {
        // Use $this->db for database operations
        // Use QueryBuilder for queries
        $qb = $this->db->createQueryBuilder();
        // ... your code
    }
}
```

### Step 2: Add to Dispatcher

Add to `unified/src/Dispatcher.php`:

```php
case "yourAdminAction":
    $admin = new \Kova\Kams\Unified\Modules\Admin\YourAdminController($config);
    echo $admin->yourMethod();
    break;
```

### Step 3: Create Entry Point (if needed)

Create `unified/src/Modules/Admin/your-admin-page.php`:

```php
<?php
require_once(__DIR__ . '/../../../../app/bootstrap.php');

use Kova\Kams\Unified\Modules\Admin\YourAdminController;
use Kova\Kams\Unified\Modules\Common\Config;

$config = new Config();
$admin = new YourAdminController($config);
echo $admin->renderYourPage();
```

## Key Patterns to Follow

### 1. Use Database Class
```php
// ❌ Don't do this
$mysqli = new mysqli($host, $user, $pass, $db);
$result = $mysqli->query("SELECT * FROM table");

// ✅ Do this
$db = new Database('kams');
$qb = $db->createQueryBuilder();
$qb->select('*')->from('table');
$result = $db->executeQueryBuilder($qb);
```

### 2. Use QueryBuilder
```php
// ❌ Don't do this
$sql = "UPDATE settings SET value = '$value' WHERE name = '$name'";
$mysqli->query($sql);

// ✅ Do this
$qb = $this->db->createQueryBuilder();
$qb->update('settings')
   ->set('value', ':value')
   ->where('name = :name')
   ->setParameter('value', $value)
   ->setParameter('name', $name);
$qb->executeStatement();
```

### 3. Input Validation
```php
// Always validate input
if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
    throw new InvalidArgumentException("Invalid input");
}

// Escape output
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

### 4. Error Handling
```php
try {
    $result = $this->db->insert('table', $data);
} catch (\Throwable $e) {
    $this->logger->error('Insert failed', ['error' => $e->getMessage()]);
    return ['success' => false, 'error' => $e->getMessage()];
}
```

## Updating Existing Admin Pages

The existing `KAMS-ADMIN` module needs updating:

### Current Issues:
1. Direct mysqli connections
2. SQL injection vulnerabilities
3. No namespace
4. Hard-coded credentials

### Migration Steps:

1. **Update adminWorker.php**:
```php
// Before
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
$sql = "UPDATE settings SET setvalue = '". $v . "' where setname = '" . $k . "'";
$mysqli->query($sql);

// After
use Kova\Kams\Common\Database;
$db = new Database('kams');
$qb = $db->createQueryBuilder();
$qb->update('settings')
   ->set('setvalue', ':value')
   ->where('setname = :name')
   ->setParameter('value', $v)
   ->setParameter('name', $k);
$qb->executeStatement();
```

2. **Update admin.php**:
```php
// Before
$mysqli = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
$sql = "SELECT * FROM settings";
$response = $mysqli->query($sql);

// After
use Kova\Kams\Unified\Modules\Admin\AdminController;
$admin = new AdminController();
$results = $admin->getSettings();
```

## Testing

1. **Test direct access**: `http://yoursite/src/Modules/Admin/admin.php`
2. **Test via AJAX**: Use browser console to test Dispatcher route
3. **Test authentication**: Verify login works
4. **Test updates**: Verify settings can be updated

## Security Recommendations

1. **Move password to config**: Don't hard-code passwords
2. **Use proper authentication**: Consider OAuth or JWT
3. **Add CSRF protection**: For form submissions
4. **Rate limiting**: Prevent brute force attacks
5. **Input sanitization**: Always validate and sanitize

## Next Steps

1. Share your admin page code if you have it
2. I can help integrate it following these patterns
3. We can update the existing KAMS-ADMIN module to use the new structure
4. Add any additional admin functionality you need

