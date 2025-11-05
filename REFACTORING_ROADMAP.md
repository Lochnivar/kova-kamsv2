# Refactoring Roadmap - Next Steps

## Overview
This document outlines the recommended next steps for refactoring the Kova KAMS codebase, prioritized by impact and dependencies.

## Completed ✅
1. ✅ Removed `dbQuery()` method - replaced with DBAL methods
2. ✅ Consolidated namespaces to `Kova\Kams\*` schema
3. ✅ Created core classes (Common, Communicator) to eliminate duplication
4. ✅ Fixed hard-coded paths using `kova_path()` helper
5. ✅ Implemented centralized logging service
6. ✅ Improved error handling with structured logging

## Priority 1: Dependency Injection Container (High Impact)

### Problem
- Classes directly instantiate dependencies (`new Database()`, `new Config()`, `new Common()`)
- Makes testing difficult
- No centralized service management
- Hard to swap implementations

### Solution
Create a service container in `src/Core/Container.php`:

```php
namespace Kova\Kams\Core;

class Container
{
    private array $services = [];
    
    public function get(string $id) { ... }
    public function register(string $id, callable $factory) { ... }
    public function singleton(string $id, callable $factory) { ... }
}
```

### Benefits
- Centralized service management
- Easier testing (can mock dependencies)
- Better separation of concerns
- Follows SOLID principles

### Files to Refactor
- All Cron classes (MotoCron, SerialCron, UdpCron, etc.)
- All Worker classes (Dataworker classes)
- Dispatcher classes
- AlarmHandler

**Estimated Impact**: High - affects ~20+ files
**Estimated Effort**: Medium (2-3 days)

---

## Priority 2: Abstract Base Classes for Common Patterns (High Impact)

### Problem
- Cron classes follow similar patterns but duplicate code
- Worker classes have similar structure
- Configuration access patterns repeated

### Solution
Create abstract base classes:

1. **`src/Core/AbstractCron.php`**
   - Common properties: `$dbConn`, `$config`, `$common`, `$mod`, `$ifaces`
   - Common methods: `modEnabled()`, initialization patterns
   - Abstract methods: `startCron()`, `processCron()`, `AlertCron()`

2. **`src/Core/AbstractWorker.php`**
   - Common data fetching patterns
   - Standardized database queries
   - Common error handling

3. **`src/Core/AbstractModule.php`**
   - Base functionality for all modules
   - Configuration access patterns
   - Interface management

### Benefits
- Eliminates code duplication
- Ensures consistent patterns
- Easier to add new modules
- Centralized bug fixes

### Files to Refactor
- `kcm/src/Modules/*/Cron.php` classes
- `unified/src/Modules/*/Workers/Dataworker.php` classes

**Estimated Impact**: High - reduces duplication significantly
**Estimated Effort**: Medium (2-3 days)

---

## Priority 3: Remove Remaining Hard-Coded Paths (Medium Impact)

### Problem
Found hard-coded paths:
- `src/Core/Communicator.php`: `/usr/src/KCM/kcm-endpoint.php`
- Various cron files: `/usr/src/KCM/src/Modules/Crons/KCM-Cron-Service.log`
- `__DIR__` usage in some places

### Solution
- Replace with `kova_path()` helper
- Use configuration for service endpoints
- Centralize log paths

### Files to Fix
- `src/Core/Communicator.php`
- `kcm/src/Modules/*/Cron.php` files
- Any remaining file operations

**Estimated Impact**: Medium - improves portability
**Estimated Effort**: Low (4-6 hours)

---

## Priority 4: Configuration Service Improvements (Medium Impact)

### Problem
- Direct array access: `$this->config['key']`
- No type safety
- No validation
- Mixed access patterns

### Solution
Enhance `Config` class:
- Add typed getters: `getString()`, `getInt()`, `getBool()`, `getArray()`
- Add validation
- Add default value handling
- Cache frequently accessed values

### Benefits
- Type safety
- Better error messages
- Consistent access patterns
- Easier to maintain

**Estimated Impact**: Medium - improves code quality
**Estimated Effort**: Low-Medium (1-2 days)

---

## Priority 5: Type Safety Improvements (Medium Impact)

### Problem
- Missing return type hints
- Missing parameter type hints
- Mixed return types (`array|false|null`)
- Missing PHPDoc annotations

### Solution
- Add return types to all methods
- Add parameter types
- Use strict types (`declare(strict_types=1)`)
- Add comprehensive PHPDoc

### Benefits
- Better IDE support
- Catch errors at compile time
- Self-documenting code
- Better static analysis

**Estimated Impact**: Medium - improves maintainability
**Estimated Effort**: Medium (2-3 days across all files)

---

## Priority 6: Error Handling Standardization (Medium Impact)

### Problem
- Inconsistent error handling patterns
- Mix of exceptions and return values
- Some silent failures
- Inconsistent logging

### Solution
- Create custom exception classes
- Standardize error handling patterns
- Use try-catch consistently
- Log all errors with context

### Files to Create
- `src/Core/Exceptions/BaseException.php`
- `src/Core/Exceptions/DatabaseException.php`
- `src/Core/Exceptions/ConfigurationException.php`
- `src/Core/Exceptions/ValidationException.php`

**Estimated Impact**: Medium - improves debugging
**Estimated Effort**: Low-Medium (1-2 days)

---

## Priority 7: Extract Worker Patterns (Low-Medium Impact)

### Problem
- Dataworker classes have similar patterns
- Duplicate query patterns
- Similar data processing logic

### Solution
- Create `DataProcessor` base class
- Extract common query patterns
- Create reusable query builders
- Standardize data transformation

**Estimated Impact**: Low-Medium - reduces duplication
**Estimated Effort**: Medium (2-3 days)

---

## Priority 8: Testing Infrastructure (High Long-term Value)

### Problem
- No unit tests
- No integration tests
- Difficult to verify changes

### Solution
- Set up PHPUnit
- Create test base classes
- Add tests for core classes
- Add integration tests for critical paths

### Benefits
- Confidence in refactoring
- Catch regressions early
- Document expected behavior

**Estimated Impact**: High long-term value
**Estimated Effort**: High (ongoing)

---

## Priority 9: Documentation (Low-Medium Impact)

### Problem
- Missing PHPDoc in many places
- No API documentation
- Unclear method purposes

### Solution
- Add comprehensive PHPDoc
- Generate API docs with phpDocumentor
- Create developer guides
- Document architecture decisions

**Estimated Impact**: Low-Medium - improves onboarding
**Estimated Effort**: Medium (ongoing)

---

## Recommended Implementation Order

### Phase 1: Foundation (Week 1-2)
1. **Dependency Injection Container** (Priority 1)
2. **Remove Hard-Coded Paths** (Priority 3)
3. **Configuration Improvements** (Priority 4)

### Phase 2: Structure (Week 3-4)
4. **Abstract Base Classes** (Priority 2)
5. **Error Handling Standardization** (Priority 6)
6. **Type Safety Improvements** (Priority 5) - Start with core classes

### Phase 3: Quality (Week 5+)
7. **Extract Worker Patterns** (Priority 7)
8. **Testing Infrastructure** (Priority 8)
9. **Documentation** (Priority 9)

## Quick Wins (Can Do Immediately)

1. ✅ Fix hard-coded path in `Communicator.php` (15 minutes)
2. ✅ Add return types to core classes (1-2 hours)
3. ✅ Add PHPDoc to core classes (2-3 hours)
4. ✅ Create custom exception classes (1 hour)
5. ✅ Standardize config access patterns (2-3 hours)

## Metrics to Track

- Code duplication reduction
- Test coverage percentage
- Type safety percentage (methods with types)
- Hard-coded paths remaining
- Cyclomatic complexity reduction

## Success Criteria

- [ ] All classes use dependency injection
- [ ] No hard-coded paths remain
- [ ] 80%+ type coverage
- [ ] Base classes for common patterns
- [ ] Comprehensive error handling
- [ ] Test coverage > 50%
- [ ] All core classes documented

