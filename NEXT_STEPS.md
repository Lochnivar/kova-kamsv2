# Next Steps for Refactoring

## ✅ Completed Foundation

1. **Dependency Injection Container** (`src/Core/Container.php`)
   - Singleton and factory patterns
   - Pre-registered core services
   - Ready to use throughout codebase

2. **Abstract Base Classes**
   - `AbstractCron.php` - Base for all cron jobs
   - `AbstractWorker.php` - Base for all data workers
   - Enforces consistent patterns

3. **Hard-Coded Paths Fixed**
   - ✅ Communicator.php
   - ✅ Log file paths
   - ✅ File dump paths
   - ⚠️ Some exec commands still have hard-coded paths (tcpdump)

## 🎯 Immediate Next Steps (In Priority Order)

### 1. Refactor Cron Classes to Use AbstractCron (High Impact)

**Example Created**: `kcm/src/Modules/Motorola/MotoCron.refactored.php`

**Benefits:**
- Reduces code by ~50%
- Automatic dependency injection
- Consistent initialization
- Built-in logging

**Action Items:**
- [ ] Refactor `MotoCron.php` to extend `AbstractCron`
- [ ] Refactor `SerialCron.php` to extend `AbstractCron`
- [ ] Refactor `UdpCron.php` to extend `AbstractCron`
- [ ] Refactor `ZabbixCron.php` to extend `AbstractCron`

**Pattern:**
```php
class MotoCron extends AbstractCron {
    protected function initialize(): void {
        // Only module-specific initialization
    }
    // Implement abstract methods only
}
```

### 2. Refactor Worker Classes to Use AbstractWorker (High Impact)

**Action Items:**
- [ ] Refactor `Motorola/Workers/Dataworker.php`
- [ ] Refactor `Serial/Workers/Dataworker.php`
- [ ] Refactor `UDP/Workers/Dataworker.php`

**Pattern:**
```php
class Dataworker extends AbstractWorker {
    protected function initialize(): void {
        $this->mod = "moto";
    }
    public function getAvgs(): array {
        // Implementation
    }
}
```

### 3. Update Entry Points to Use Container (Medium Impact)

**Files to Update:**
- `unified/src/Dispatcher.php`
- `kcm/CronService.php`
- `kcm/CronMain.php`
- `kcm/src/Dispatcher.php`

**Pattern:**
```php
$container = Container::getInstance();
$config = $container->get('config');
$db = $container->get('database');
```

### 4. Remove Wrapper Classes (Low Impact, High Cleanup)

Since we're using class aliases, we can:
1. Update all references to use Core classes directly
2. Remove `UnifiedCommon`, `KcmCommon`, etc.
3. Use `Kova\Kams\Core\Common` everywhere

### 5. Add Type Safety (Medium Impact)

**Action Items:**
- [ ] Add `declare(strict_types=1)` to all files
- [ ] Add return types to all methods
- [ ] Add parameter types
- [ ] Remove `mixed` types where possible

### 6. Fix Remaining Hard-Coded Paths (Low Impact)

**Remaining Issues:**
- tcpdump command paths in cron classes
- Some include paths in SerialCron
- Any remaining `/usr/src/` references

### 7. Extract Common Patterns (Medium Impact)

**Opportunities:**
- Alarm handling logic (similar across modules)
- Time calculation logic
- Interface parsing logic
- File processing patterns

## Quick Start Guide

### To Refactor a Cron Class:

1. **Change class declaration:**
```php
// Before
class MotoCron {
    public function __construct($config) {
        $this->dbConn = new DB('kams');
        $this->config = $config->config;
        $this->common = new Common($this->config);
        // ... manual init
    }
}

// After
class MotoCron extends AbstractCron {
    protected function initialize(): void {
        // Only module-specific code
    }
}
```

2. **Remove manual initialization** - now handled by AbstractCron

3. **Implement abstract methods** - only what's module-specific

4. **Use injected dependencies** - `$this->dbConn`, `$this->common`, `$this->logger` are already available

### To Refactor a Worker Class:

1. **Change class declaration:**
```php
class Dataworker extends AbstractWorker {
    protected function initialize(): void {
        $this->mod = "moto";
    }
}
```

2. **Use `getLastPacketStamp()` from base class** - no need to duplicate

3. **Focus on module-specific logic only**

## Expected Results

After refactoring:
- **Cron classes**: ~150 lines each (down from ~300)
- **Worker classes**: ~50 lines each (down from ~100)
- **Total code reduction**: ~2000+ lines eliminated
- **Consistency**: All modules follow same patterns
- **Maintainability**: Changes in one place affect all modules
- **Testability**: Easy to mock dependencies

## Testing Strategy

After each refactoring:
1. Run syntax checks: `php -l filename.php`
2. Test class loading: `php -r "require 'vendor/autoload.php'; new ClassName();"`
3. Verify functionality matches original
4. Check for any missing dependencies

## Rollout Plan

1. **Week 1**: Refactor all Cron classes (4 classes)
2. **Week 2**: Refactor all Worker classes (3 classes)
3. **Week 3**: Update entry points and remove wrappers
4. **Week 4**: Add type safety and final cleanup

Would you like me to start refactoring the actual cron classes now, or would you prefer to review the example first?

