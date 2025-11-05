# Autoload Verification - Unified vs KCM

## Critical Question
**Will both Frontend-Unified and Backend-KCM autoload the same core classes?**

## Answer: YES ✅

### 1. Single Autoloader Configuration

The `composer.json` defines **one autoloader** at the project root that maps:
```json
{
  "autoload": {
    "psr-4": {
      "Kova\\Kams\\Core\\": "src/Core/",
      "Kova\\Kams\\Unified\\": "unified/src/",
      "Kova\\Kams\\Kcm\\": "kcm/src/",
      "Kova\\Kams\\Common\\": "src/Common/",
      "Kova\\Kams\\Bones\\": "src/Bones/"
    }
  }
}
```

**Key Point**: `Kova\Kams\Core\` maps to `src/Core/` - a **single shared location** at the project root.

### 2. Both Branches Use Same Namespace

**Unified modules**:
```php
use Kova\Kams\Core\Common as CoreCommon;
use Kova\Kams\Core\Communicator as CoreCommunicator;
```

**KCM modules**:
```php
use Kova\Kams\Core\Common as CoreCommon;
use Kova\Kams\Core\Communicator as CoreCommunicator;
```

Both reference the **exact same namespace**: `Kova\Kams\Core\`

### 3. Autoloader Path Resolution

#### Unified (Frontend)
- Entry point: `unified/src/Dispatcher.php`
- Uses: `kova_path('app/bootstrap.php')` → `KOVA_ROOT . '/vendor/autoload.php'`
- Resolves to: `/srv/kova/vendor/autoload.php` ✅

#### KCM (Backend)
- Entry points: 
  - `kcm/CronService.php` → `__DIR__ . "/vendor/autoload.php"` (needs fix)
  - `kcm/src/Dispatcher.php` → `require("../vendor/autoload.php")` ✅
  - `kcm/CronMain.php` → `__DIR__ . "/vendor/autoload.php"` (needs fix)

**Note**: Some KCM entry points use relative paths that may need adjustment to point to the root `vendor/autoload.php`.

### 4. Physical File Location

All core classes are in **one location**:
```
/srv/kova/src/Core/
├── Common.php          ← Single source
└── Communicator.php    ← Single source
```

Both Unified and KCM modules extend classes from this **same location**.

## Verification Steps

1. ✅ Both use same namespace: `Kova\Kams\Core\`
2. ✅ Both reference same physical path: `src/Core/`
3. ✅ Single composer.json defines all mappings
4. ⚠️ Need to verify KCM autoloader paths resolve correctly

## Recommendation

Ensure all entry points load the root `vendor/autoload.php`:
- Unified: ✅ Already uses `KOVA_ROOT . '/vendor/autoload.php'`
- KCM: Should use `__DIR__ . '/../vendor/autoload.php'` or `KOVA_ROOT . '/vendor/autoload.php'`

## Conclusion

**YES** - Both branches will autoload from the same core because:
1. Single namespace mapping in composer.json
2. Single physical file location
3. Both reference the same namespace
4. Composer's PSR-4 autoloader resolves to the same files

Any differences would only occur if:
- Different composer.json files (not the case)
- Different vendor/autoload.php files (not recommended)
- Path resolution issues (should be verified)

