# Refactoring Priorities (No Backward Compatibility Required)

Since backward compatibility is not required, we can make aggressive changes. Here's the prioritized plan:

## ✅ Completed (Foundation)

1. ✅ **Dependency Injection Container** - `src/Core/Container.php`
   - Centralized service management
   - Singleton and factory patterns
   - Ready to use throughout codebase

2. ✅ **Abstract Base Classes**
   - `AbstractCron.php` - Base for all cron jobs
   - `AbstractWorker.php` - Base for all data workers
   - Enforces consistent patterns

3. ✅ **Removed Hard-Coded Paths**
   - Fixed in `Communicator.php`
   - Fixed log paths in cron classes
   - Using `kova_path()` helper

## 🔄 Next: Refactor Modules to Use New Infrastructure

### Phase 1: Refactor Cron Classes (High Priority)

**Example: Refactor MotoCron to use AbstractCron and DI**

**Before:**
```php
class MotoCron {
    public function __construct($config) {
        $this->dbConn = new DB('kams');
        $this->config = $config->config;
        $this->common = new Common($this->config);
        // ... manual initialization
    }
}
```

**After:**
```php
class MotoCron extends AbstractCron {
    protected function initialize(): void {
        $this->mod = "moto";
        $this->ifaces = $this->config['MotorolaInterfaceName'];
        $this->modEnabled = strtolower($this->config['MotorolaPage']);
        $this->fileName = kova_path('tmp/motodump');
    }
    // ... only implement abstract methods
}
```

**Benefits:**
- 50% less boilerplate code
- Consistent initialization
- Automatic dependency injection
- Easier to maintain

### Phase 2: Refactor Worker Classes (High Priority)

**Example: Refactor Dataworker classes**

Use `AbstractWorker` base class - eliminates duplicate code for:
- Database connection
- Config handling
- Common utility access
- Logger access

### Phase 3: Update All Entry Points (Medium Priority)

Replace direct instantiation with Container:
```php
// Old
$db = new Database('kams');
$config = new Config();
$common = new Common($config);

// New
$container = Container::getInstance();
$db = $container->get('database');
$config = $container->get('config');
$common = $container->get('common', $config);
```

### Phase 4: Remove Wrapper Classes (Low Priority)

Since we're using aliases, we can eventually:
1. Update all references to use Core classes directly
2. Remove Unified/KCM wrapper classes
3. Use Core classes everywhere

### Phase 5: Add Type Safety (Medium Priority)

- Add `declare(strict_types=1)` to all files
- Add return types to all methods
- Add parameter types
- Remove mixed types where possible

## Implementation Order

1. **Week 1**: Refactor all Cron classes to extend AbstractCron
2. **Week 2**: Refactor all Worker classes to extend AbstractWorker  
3. **Week 3**: Update entry points to use Container
4. **Week 4**: Remove wrapper classes, use Core directly
5. **Week 5**: Add type safety throughout

## Quick Wins Available Now

1. **Fix remaining hard-coded paths** (30 min)
   - ✅ Fixed in Communicator
   - ✅ Fixed log paths
   - ⚠️ Still some in cron exec commands (tcpdump paths)

2. **Refactor one Cron class as example** (1 hour)
   - Show the pattern
   - Others can follow

3. **Refactor one Worker class as example** (1 hour)
   - Show the pattern
   - Others can follow

4. **Add strict types to core classes** (30 min)
   - Immediate type safety improvement

## Metrics

**Code Reduction Goals:**
- Cron classes: Reduce from ~300 lines to ~150 lines each
- Worker classes: Reduce from ~100 lines to ~50 lines each
- Eliminate 2000+ lines of duplicate code

**Quality Goals:**
- 100% type coverage in core classes
- 80% type coverage in modules
- Zero hard-coded paths
- All classes use DI container

