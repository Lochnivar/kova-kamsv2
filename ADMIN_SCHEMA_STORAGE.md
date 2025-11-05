# Settings Schema Storage Options

## Recommended Options

### Option 1: PHP Class in Admin Module (Recommended) ✅

**Location:** `unified/src/Modules/Admin/Schema/SettingsSchema.php`

**Structure:**
```
unified/src/Modules/Admin/
├── Schema/
│   └── SettingsSchema.php          ← Schema definition class
├── Services/
│   ├── SettingsService.php
│   └── SystemdServiceManager.php
├── Handlers/
├── Validation/
└── ...
```

**Pros:**
- Type-safe (PHP class)
- IDE autocomplete support
- Can add methods for complex validation
- Easy to version control
- Can be tested
- Follows existing module structure

**Cons:**
- Requires PHP knowledge to modify
- Need to redeploy code to change schema

**Example:**
```php
<?php

namespace Kova\Kams\Unified\Modules\Admin\Schema;

class SettingsSchema
{
    private array $schema = [
        'MotorolaPage' => [
            'type' => 'bool',
            'group' => 'Motorola',
            'required' => false,
            'default' => '0',
            'validation' => ['in' => ['0', '1', 'yes', 'no']],
            'description' => 'Enable Motorola monitoring page'
        ],
        // ... more settings
    ];
    
    public function getSchema(string $name): ?array {
        return $this->schema[$name] ?? null;
    }
    
    public function getAll(): array {
        return $this->schema;
    }
}
```

---

### Option 2: JSON Configuration File

**Location:** `config/settings-schema.json`

**Structure:**
```
config/
├── databases.json
├── settings-schema.json          ← Schema definition
└── ...
```

**Pros:**
- Easy to edit (no PHP knowledge needed)
- Can be modified without code changes
- Human-readable
- Can be loaded dynamically

**Cons:**
- No type safety
- No IDE autocomplete
- Need to validate JSON syntax
- Less flexible for complex validation

**Example:**
```json
{
  "MotorolaPage": {
    "type": "bool",
    "group": "Motorola",
    "required": false,
    "default": "0",
    "validation": {
      "in": ["0", "1", "yes", "no"]
    },
    "description": "Enable Motorola monitoring page"
  },
  "UDPInterfaceName": {
    "type": "object",
    "group": "UDP",
    "required": true,
    "validation": {
      "schema": {
        "interface": "string|required",
        "label": "string|required",
        "threshold": "int|min:1|max:10000"
      }
    }
  }
}
```

**PHP Loader:**
```php
<?php

namespace Kova\Kams\Unified\Modules\Admin\Schema;

class SettingsSchemaLoader
{
    private array $schema = [];
    
    public function __construct(?string $path = null)
    {
        $path = $path ?? kova_path('config/settings-schema.json');
        $json = file_get_contents($path);
        $this->schema = json_decode($json, true);
    }
    
    public function getSchema(string $name): ?array {
        return $this->schema[$name] ?? null;
    }
}
```

---

### Option 3: Hybrid Approach (Best of Both Worlds) ⭐

**Structure:**
```
unified/src/Modules/Admin/
├── Schema/
│   ├── SettingsSchema.php         ← PHP class with defaults
│   └── SettingsSchemaLoader.php   ← Loads from JSON if exists
├── config/
│   └── settings-schema.json       ← Optional override file
└── ...
```

**How it works:**
1. PHP class has default schema (for development/new installs)
2. JSON file can override/extend schema (for customization)
3. Loader merges both (JSON takes precedence)

**Pros:**
- Best of both worlds
- Defaults in code (version controlled)
- Customizations in JSON (easier to modify)
- Can work without JSON file
- Flexible and maintainable

**Example:**
```php
<?php

namespace Kova\Kams\Unified\Modules\Admin\Schema;

class SettingsSchema
{
    private array $schema;
    
    public function __construct()
    {
        // Load defaults from class
        $this->schema = $this->getDefaultSchema();
        
        // Merge with JSON file if exists
        $jsonPath = kova_path('config/settings-schema.json');
        if (file_exists($jsonPath)) {
            $json = json_decode(file_get_contents($jsonPath), true);
            if (is_array($json)) {
                $this->schema = array_merge($this->schema, $json);
            }
        }
    }
    
    private function getDefaultSchema(): array
    {
        return [
            'MotorolaPage' => [
                'type' => 'bool',
                'group' => 'Motorola',
                // ... defaults
            ],
            // ... more defaults
        ];
    }
    
    public function getSchema(string $name): ?array {
        return $this->schema[$name] ?? null;
    }
}
```

---

### Option 4: Database-Stored Schema (Advanced)

**Location:** New table `settings_schema`

**Structure:**
```sql
CREATE TABLE settings_schema (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    type VARCHAR(50) NOT NULL,
    group_name VARCHAR(100),
    required BOOLEAN DEFAULT FALSE,
    default_value TEXT,
    validation_rules JSON,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Pros:**
- Can be modified via admin UI
- No code deployment needed
- Can be versioned in database
- Dynamic schema management

**Cons:**
- More complex
- Requires migration
- Performance overhead
- Harder to version control
- Risk of inconsistency

---

## Recommendation: Option 3 (Hybrid) ⭐

**Why:**
1. **Defaults in code** - Version controlled, always available
2. **JSON for customization** - Easy to modify without code changes
3. **Best practices** - Follows common patterns (Laravel, Symfony)
4. **Flexible** - Works with or without JSON file
5. **Maintainable** - Clear separation of concerns

**Implementation:**
```
unified/src/Modules/Admin/
├── Schema/
│   └── SettingsSchema.php          ← Main class (defaults + JSON loader)
│
config/
└── settings-schema.json            ← Optional customizations
```

**Usage:**
```php
// In SettingsService or SettingsHandler
use Kova\Kams\Unified\Modules\Admin\Schema\SettingsSchema;

$schema = new SettingsSchema();
$settingSchema = $schema->getSchema('MotorolaPage');

// Validate
if ($settingSchema) {
    // Use schema for validation
}
```

---

## File Structure Summary

```
/srv/kova/
├── config/
│   ├── databases.json
│   └── settings-schema.json          ← Optional JSON overrides
│
├── unified/src/Modules/Admin/
│   ├── Schema/
│   │   └── SettingsSchema.php        ← PHP class with defaults
│   ├── Services/
│   │   ├── SettingsService.php
│   │   └── SystemdServiceManager.php
│   ├── Handlers/
│   │   ├── SettingsHandler.php
│   │   └── AuthHandler.php
│   └── Validation/
│       └── TypeValidator.php
│
└── ...
```

---

## Migration Path

1. **Start with Option 1** (PHP class) - Quick to implement
2. **Add Option 2 support** (JSON loader) - When you need customization
3. **Result: Option 3** (Hybrid) - Best solution

This gives you:
- ✅ Immediate implementation (PHP class)
- ✅ Future flexibility (JSON support)
- ✅ No breaking changes
- ✅ Easy to maintain

