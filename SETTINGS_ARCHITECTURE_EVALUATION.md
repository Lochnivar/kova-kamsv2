# Settings Architecture Evaluation & Recommendations

## Current State Analysis

### 1. Database Schema

**Current Structure:**
```sql
-- Dual column support (legacy + new)
- id (int, PRIMARY KEY)
- setname (varchar) / name (varchar)     -- Setting key
- setvalue (text) / value (text)         -- Setting value
- type (varchar)                         -- Data type: bool, string, int, object, list
- sethint (text) / description (text)   -- Description
- group_name (varchar)                   -- Grouping for UI
- sort_order (int)                       -- Display order
```

**Issues Identified:**
1. **Dual column names** - Code must handle both `setname`/`setvalue` and `name`/`value`
2. **Type inconsistency** - `type` column exists but values stored as strings (e.g., "1" for bool)
3. **JSON storage** - Complex values stored as JSON strings, but no validation
4. **Legacy format** - Still supports `~` delimiter for arrays (hacky)
5. **No constraints** - No foreign keys, unique constraints, or check constraints

### 2. Storage Format Analysis

**Current Value Storage:**
- **Bool**: `"1"` or `"0"` (string) - should be native boolean or consistent
- **String**: Raw string value
- **Int**: `"500"` (string) - should be numeric
- **Object**: `'{"interface":"enp6s18","label":"Viper 1","threshold":100}'` (JSON string)
- **List**: `'["item1","item2"]'` (JSON string) or `"item1~item2~item3"` (legacy)

**Problems:**
- **No type safety** - Everything stored as TEXT, parsed on read
- **Fragile parsing** - `DBALConfigProvider` tries `json_decode()` on every value
- **Legacy support** - `~` delimiter parsing adds complexity
- **No validation** - Invalid JSON can be stored

### 3. Admin Page Editing Flow

**Current Flow:**
1. `SettingsService::getAll()` → Reads from DB, normalizes column names
2. Frontend receives JSON array of settings
3. User edits value inline
4. `SettingsHandler::handlePut()` → Validates (partial), updates DB
5. No cache invalidation → Stale data until TTL expires

**Issues:**
- **Partial validation** - Schema validation exists but not consistently applied
- **No cache invalidation** - Config cache not cleared after updates
- **Type coercion missing** - Updates don't ensure proper type formatting
- **No audit trail** - Changes not logged

### 4. Config Class Usage

**Current Implementation:**
```php
// DBALConfigProvider loads all settings into memory
$config = $this->provider->all(); // Array<string, mixed>

// Normalization logic:
1. Try json_decode() - if valid JSON, return array/object
2. If contains '~', explode to array
3. Otherwise return string

// Usage in code:
$enabled = $config['MotorolaPage']; // Returns "1" or "yes" (string)
if ($config['MotorolaPage'] == "yes") { ... } // String comparison
```

**Problems:**
- **Type ambiguity** - Booleans stored as "1"/"0"/"yes"/"no" strings
- **Inconsistent comparisons** - Code checks `== "yes"` or `== "1"`
- **No type hints** - Config values have no type information
- **Cache TTL** - 60-second cache means updates take time to propagate
- **No change detection** - Can't detect if a setting changed

### 5. SettingsSchema Integration

**Current State:**
- Schema class exists with metadata
- Used for validation in `SettingsHandler::handlePut()`
- **Not used** for:
  - Type coercion on write
  - Default value application
  - Type-safe reading from Config
  - Frontend display hints

---

## Recommended Improvements

### Option A: Progressive Enhancement (Recommended) ⭐

**Minimal database changes, maximum benefit.**

#### 1. Database Schema Standardization

**Migration:**
```sql
-- Standardize column names (keep old for backward compatibility during migration)
ALTER TABLE settings 
  ADD COLUMN IF NOT EXISTS name VARCHAR(255) AFTER id,
  ADD COLUMN IF NOT EXISTS value TEXT AFTER name,
  ADD COLUMN IF NOT EXISTS type VARCHAR(50) DEFAULT 'string' AFTER value,
  ADD COLUMN IF NOT EXISTS description TEXT AFTER type,
  ADD COLUMN IF NOT EXISTS group_name VARCHAR(100) AFTER description,
  ADD COLUMN IF NOT EXISTS sort_order INT DEFAULT 100 AFTER group_name,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Migrate data if old columns exist
UPDATE settings 
SET name = COALESCE(name, setname),
    value = COALESCE(value, setvalue),
    description = COALESCE(description, sethint)
WHERE setname IS NOT NULL OR setvalue IS NOT NULL;

-- Add indexes
CREATE INDEX idx_settings_name ON settings(name);
CREATE INDEX idx_settings_group ON settings(group_name);
CREATE UNIQUE INDEX idx_settings_name_unique ON settings(name);
```

**Benefits:**
- Single column name standard
- Unique constraint prevents duplicates
- Timestamps for audit trail
- Better indexing for performance

#### 2. Type-Safe Value Storage

**Create Value Normalizer:**
```php
// src/Common/SettingsValueNormalizer.php
class SettingsValueNormalizer
{
    public static function normalizeForStorage($value, string $type): string
    {
        switch ($type) {
            case 'bool':
                return self::normalizeBool($value) ? '1' : '0';
            case 'int':
                return (string)(int)$value;
            case 'float':
                return (string)(float)$value;
            case 'object':
            case 'list':
                return json_encode($value, JSON_UNESCAPED_SLASHES);
            case 'string':
            default:
                return (string)$value;
        }
    }
    
    public static function normalizeForUse($value, string $type)
    {
        if ($value === null) return null;
        
        switch ($type) {
            case 'bool':
                return self::normalizeBool($value);
            case 'int':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'object':
            case 'list':
                $decoded = json_decode($value, true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
            default:
                return $value;
        }
    }
    
    private static function normalizeBool($value): bool
    {
        if (is_bool($value)) return $value;
        if (is_int($value)) return $value !== 0;
        $str = strtolower(trim((string)$value));
        return in_array($str, ['1', 'yes', 'true', 'on'], true);
    }
}
```

#### 3. Enhanced Config Class with Type Safety

**Update DBALConfigProvider:**
```php
// src/Common/DBALConfigProvider.php
class DBALConfigProvider
{
    private SettingsSchema $schema; // NEW
    
    public function get(string $key, $default = null, ?string $expectedType = null)
    {
        $config = $this->all();
        $value = $config[$key] ?? $default;
        
        // Type coercion if schema available
        if ($expectedType && $this->schema->hasSchema($key)) {
            $schema = $this->schema->getSchema($key);
            $type = $schema['type'] ?? $expectedType;
            return SettingsValueNormalizer::normalizeForUse($value, $type);
        }
        
        return $value;
    }
    
    public function getBool(string $key, bool $default = false): bool
    {
        return (bool)$this->get($key, $default ? '1' : '0', 'bool');
    }
    
    public function getInt(string $key, int $default = 0): int
    {
        return (int)$this->get($key, (string)$default, 'int');
    }
    
    public function getString(string $key, string $default = ''): string
    {
        return (string)$this->get($key, $default, 'string');
    }
    
    public function getObject(string $key, array $default = []): array
    {
        $value = $this->get($key, json_encode($default), 'object');
        return is_array($value) ? $value : $default;
    }
}
```

#### 4. Cache Invalidation on Update

**Update SettingsService:**
```php
// unified/src/Modules/Admin/Services/SettingsService.php
class SettingsService
{
    public function update(int $id, array $data): bool
    {
        // Normalize value before storage
        if (isset($data['value']) && isset($data['type'])) {
            $data['value'] = SettingsValueNormalizer::normalizeForStorage(
                $data['value'], 
                $data['type']
            );
        }
        
        // Update database
        $qb = $this->db->createQueryBuilder();
        $qb->update('settings')
           ->set('value', ':value')
           ->set('updated_at', 'NOW()')
           ->where('id = :id');
        // ... rest of update
        
        // Invalidate config cache
        $this->invalidateCache();
        
        return true;
    }
    
    private function invalidateCache(): void
    {
        // Clear all Config instances' cache
        // Could use a cache tag system or shared cache
        // For now: reduce TTL or force reload
    }
}
```

#### 5. Enhanced Validation

**Update SettingsHandler:**
```php
// unified/src/Modules/Admin/Handlers/SettingsHandler.php
public function handlePut(int $id, array $input): array
{
    // Get setting from DB
    $setting = $this->settingsService->getById($id);
    $name = $setting['name'] ?? $input['name'] ?? null;
    
    // Get schema for validation
    $schema = $this->schema->getSchema($name);
    
    if ($schema) {
        // Use schema for type and validation
        $type = $input['type'] ?? $schema['type'] ?? 'string';
        
        // Validate value
        $validationResult = $this->validateAgainstSchema(
            $input['value'], 
            $schema
        );
        
        if (!$validationResult['valid']) {
            return [
                'success' => false,
                'error' => $validationResult['error']
            ];
        }
        
        // Normalize value
        $input['value'] = SettingsValueNormalizer::normalizeForStorage(
            $input['value'],
            $type
        );
    }
    
    // Update
    return $this->settingsService->update($id, $input);
}
```

#### 6. Audit Logging

**Add Settings Audit Table:**
```sql
CREATE TABLE settings_audit (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    setting_id INT NOT NULL,
    setting_name VARCHAR(255) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    changed_by VARCHAR(255), -- Could be user ID or session
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    change_reason VARCHAR(255),
    INDEX idx_setting_id (setting_id),
    INDEX idx_changed_at (changed_at)
);
```

---

### Option B: Full Refactor (More Disruptive)

**Complete restructure with JSON column support.**

#### 1. Modern JSON Column

```sql
ALTER TABLE settings
  MODIFY COLUMN value JSON, -- MySQL 5.7+ / MariaDB 10.2+
  ADD COLUMN metadata JSON;  -- For additional schema info
```

**Benefits:**
- Native JSON validation
- Better indexing options
- Type-safe storage
- Query JSON directly

**Trade-offs:**
- Requires MySQL 5.7+ / MariaDB 10.2+
- Migration complexity
- Breaking change for legacy code

#### 2. Separate Tables by Type

```sql
-- Simple settings (bool, string, int)
CREATE TABLE settings_simple (
    name VARCHAR(255) PRIMARY KEY,
    value_string VARCHAR(255),
    value_int INT,
    value_bool BOOLEAN,
    type ENUM('string', 'int', 'bool'),
    ...
);

-- Complex settings (objects, arrays)
CREATE TABLE settings_complex (
    name VARCHAR(255) PRIMARY KEY,
    value_json JSON,
    schema_ref VARCHAR(255), -- Reference to schema
    ...
);
```

**Benefits:**
- Type-specific optimization
- Better query performance
- Clearer data model

**Trade-offs:**
- More complex queries
- More tables to maintain
- Migration complexity

---

## Implementation Priority

### Phase 1: Immediate (Low Risk)
1. ✅ Add `SettingsValueNormalizer` for consistent type handling
2. ✅ Enhance `SettingsHandler` validation using schema
3. ✅ Add cache invalidation on update
4. ✅ Add type-safe getters to `DBALConfigProvider`

### Phase 2: Short-term (Medium Risk)
1. ✅ Standardize column names (migrate from `setname`/`setvalue`)
2. ✅ Add unique constraint on `name`
3. ✅ Add timestamps (`updated_at`, `created_at`)
4. ✅ Integrate schema defaults when setting missing

### Phase 3: Medium-term (Higher Risk)
1. ✅ Add audit logging table
2. ✅ Implement change notifications
3. ✅ Add setting dependency system
4. ✅ Add setting versioning

### Phase 4: Long-term (Optional)
1. ⚠️ Consider JSON column if DB version supports
2. ⚠️ Consider separate tables if complexity grows
3. ⚠️ Add setting encryption for sensitive values

---

## Code Examples

### Before (Current)
```php
// Config usage
$config = new Config();
$enabled = $config->config['MotorolaPage']; // "1" or "yes" (string)
if ($enabled == "yes") { ... } // String comparison

// Admin update
$settingsService->update($id, [
    'value' => '1' // Could be "1", "yes", true, etc.
]);
```

### After (Recommended)
```php
// Type-safe Config usage
$config = new Config();
$enabled = $config->getBool('MotorolaPage', false); // Always boolean
if ($enabled) { ... } // Type-safe comparison

// Admin update with validation
$settingsService->update($id, [
    'value' => true, // Normalized to "1" automatically
    'type' => 'bool'
]);
// Value is validated against schema, normalized, and cache invalidated
```

---

## Migration Strategy

### Step 1: Add New Columns (Non-Breaking)
```sql
ALTER TABLE settings 
  ADD COLUMN name VARCHAR(255),
  ADD COLUMN value TEXT,
  ADD COLUMN updated_at TIMESTAMP;
```

### Step 2: Migrate Data
```sql
UPDATE settings 
SET name = COALESCE(name, setname),
    value = COALESCE(value, setvalue)
WHERE name IS NULL OR value IS NULL;
```

### Step 3: Update Code
- Update `SettingsService` to use new columns
- Update `DBALConfigProvider` to use new columns
- Keep old column support for safety

### Step 4: Remove Old Columns (After Verification)
```sql
ALTER TABLE settings 
  DROP COLUMN setname,
  DROP COLUMN setvalue,
  DROP COLUMN sethint;
```

---

## Benefits Summary

### Immediate Benefits
- ✅ Type safety in Config class
- ✅ Consistent value normalization
- ✅ Better validation
- ✅ Cache invalidation

### Medium-term Benefits
- ✅ Standardized schema
- ✅ Audit trail
- ✅ Better performance (indexes)
- ✅ Easier maintenance

### Long-term Benefits
- ✅ Scalability
- ✅ Extensibility
- ✅ Better developer experience
- ✅ Reduced bugs from type mismatches

---

## Testing Strategy

1. **Unit Tests**: Value normalization, type coercion
2. **Integration Tests**: Admin update → Config read
3. **Migration Tests**: Old data → New format
4. **Backward Compatibility**: Ensure old code still works during transition

---

## Conclusion

**Recommended Approach**: **Option A (Progressive Enhancement)**

- Minimal database changes
- Backward compatible
- Immediate benefits
- Low risk
- Can evolve to Option B later if needed

The key improvements are:
1. **Type safety** - Normalize values on write, coerce on read
2. **Schema integration** - Use schema for validation and defaults
3. **Cache invalidation** - Ensure updates propagate immediately
4. **Standardization** - Migrate from dual column names to single standard
5. **Audit trail** - Track changes for debugging and compliance

This approach provides 80% of the benefits with 20% of the effort.

