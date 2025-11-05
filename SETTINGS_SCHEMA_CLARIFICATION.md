# Settings Schema Clarification

## Current Database Structure (No Change Needed)

```sql
settings table:
- id (int)
- name (string) - e.g., "MotorolaPage"
- value (text) - e.g., "1" or '{"interface":"enp6s18","label":"Viper 1","threshold":100}'
- type (string) - e.g., "bool" or "object"
- description (text)
- group_name (string)
- sort_order (int)
```

**The `type` column already exists** - it's stored separately, not embedded in the value.

**The `value` column** contains:
- For `type='bool'`: `"1"` or `"0"` (string)
- For `type='string'`: `"some text"` (string)
- For `type='int'`: `"500"` (string)
- For `type='object'`: `'{"interface":"enp6s18","label":"Viper 1","threshold":100}'` (JSON string)
- For `type='list'`: `'["item1","item2","item3"]'` (JSON array string)

## What the Schema Recommendation Means

The **Settings Schema** is a PHP configuration class that defines **metadata** about each setting - it doesn't change the database structure.

### Current Situation
```php
// No schema - validation is ad-hoc
if ($type === 'bool') {
    // validate bool
} elseif ($type === 'object') {
    // validate object
}
```

### With Schema (Recommended)
```php
// PHP configuration class - defines metadata
class SettingsSchema {
    private array $schema = [
        'MotorolaPage' => [
            'type' => 'bool',              // Expected type
            'group' => 'Motorola',         // Display group
            'required' => false,           // Is it required?
            'default' => '0',              // Default value if missing
            'validation' => [              // Validation rules
                'in' => ['0', '1', 'yes', 'no']
            ],
            'description' => 'Enable Motorola monitoring page'
        ],
        'UDPInterfaceName' => [
            'type' => 'object',
            'group' => 'UDP',
            'required' => true,
            'validation' => [
                'schema' => [               // Nested schema for object
                    'interface' => 'string|required',
                    'label' => 'string|required',
                    'threshold' => 'int|min:1|max:10000'
                ]
            ]
        ]
    ];
}
```

## Database Storage Examples

### Example 1: Boolean Setting
**Database:**
```
name: "MotorolaPage"
value: "1"              ← Just the value (string "1")
type: "bool"            ← Type stored separately
description: "Enable Motorola page"
```

**Schema (PHP config):**
```php
'MotorolaPage' => [
    'type' => 'bool',           // Tells validator how to check
    'validation' => ['in' => ['0', '1', 'yes', 'no']],
    'default' => '0'
]
```

### Example 2: Object Setting
**Database:**
```
name: "UDPInterfaceName"
value: '{"interface":"enp6s18","label":"Viper 1","threshold":100}'  ← JSON string
type: "object"                                                       ← Type stored separately
description: "UDP interface configuration"
```

**Schema (PHP config):**
```php
'UDPInterfaceName' => [
    'type' => 'object',
    'validation' => [
        'schema' => [
            'interface' => 'string|required',
            'label' => 'string|required',
            'threshold' => 'int|min:1|max:10000'
        ]
    ]
]
```

## What This Gives You

1. **Validation Rules**: The schema tells the validator how to validate each setting
2. **Default Values**: If a setting is missing, use the default
3. **Type Safety**: Ensures values match expected types
4. **Documentation**: Schema serves as documentation
5. **Consistency**: All settings validated the same way

## Visual Representation

```
┌─────────────────────────────────────────────────┐
│ Database (settings table)                       │
├─────────────────────────────────────────────────┤
│ id  │ name              │ value                  │ type   │
├─────┼───────────────────┼──────────────────────┼────────┤
│ 1   │ MotorolaPage      │ "1"                    │ bool   │
│ 2   │ UDPInterfaceName  │ '{"interface":"..."}'  │ object │
│ 3   │ SiteName          │ "Lockwood-Dev"         │ string │
└─────────────────────────────────────────────────┘
         │
         │ referenced by
         ▼
┌─────────────────────────────────────────────────┐
│ PHP SettingsSchema (configuration)              │
├─────────────────────────────────────────────────┤
│ 'MotorolaPage' => [                             │
│   'type' => 'bool',                             │
│   'validation' => [...],                         │
│   'default' => '0'                              │
│ ]                                                │
│                                                 │
│ 'UDPInterfaceName' => [                         │
│   'type' => 'object',                           │
│   'validation' => ['schema' => [...]]           │
│ ]                                                │
└─────────────────────────────────────────────────┘
```

## Key Points

✅ **Database structure stays the same** - `name`, `value`, `type` columns remain
✅ **Type is stored separately** - not embedded in the value
✅ **Value might be JSON** - only if `type='object'` or `type='list'`
✅ **Schema is PHP config** - defines validation rules and metadata
✅ **No migration needed** - works with existing database

## The Confusion

You might have thought I meant:
```json
// ❌ NOT what I'm recommending
{
  "value": "1",
  "type": "bool"
}
```

But I actually mean:
```php
// ✅ PHP configuration that describes the setting
'MotorolaPage' => [
    'type' => 'bool',        // Metadata
    'validation' => [...],   // Metadata
    'default' => '0'         // Metadata
]
```

The database still has:
```
name: "MotorolaPage"
value: "1"      ← Just the value
type: "bool"    ← Just the type
```

