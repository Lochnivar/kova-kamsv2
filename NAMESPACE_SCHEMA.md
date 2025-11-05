# Consolidated Namespace Schema

## Overview
All namespaces should follow the pattern: `Kova\Kams\{Component}\{SubComponent}...`

## Namespace Structure

### Core Infrastructure
- `Kova\Kams\Common\` - Shared/common infrastructure
  - `Database.php` - Database wrapper
  - `Db.php` - DBAL wrapper
  - `Config.php` - Configuration loader
  - `Logger.php` - Logging service
  - `TypeDetector.php` - Type detection utility
  - `DBALConfigProvider.php` - Config provider

- `Kova\Kams\Bones\` - Low-level infrastructure
  - `DbAdapter.php` - Database adapter

### Application Components
- `Kova\Kams\Unified\Modules\{ModuleName}\...` - Unified web application
  - `Unified\Modules\Common\` - Common unified modules
  - `Unified\Modules\Motorola\` - Motorola module
  - `Unified\Modules\Serial\` - Serial module
  - `Unified\Modules\UDP\` - UDP module
  - etc.

- `Kova\Kams\Kcm\Modules\{ModuleName}\...` - KCM cron/worker modules
  - `Kcm\Modules\Common\` - Common KCM modules
  - `Kcm\Modules\Motorola\` - Motorola cron
  - `Kcm\Modules\Serial\` - Serial cron
  - `Kcm\Modules\Udp\` - UDP cron
  - etc.

- `Kova\Kams\App\` - Application/bootstrap code (future use)

## Migration Plan

1. **KCM Modules**: Change `Kova\Kcm\` → `Kova\Kams\Kcm\`
2. **Composer.json**: Already correct, just needs verification
3. **Update all use statements** across the codebase

## Current Issues
- ❌ KCM modules use `Kova\Kcm\Modules\*` but should be `Kova\Kams\Kcm\Modules\*`
- ✅ Unified modules already correct: `Kova\Kams\Unified\Modules\*`
- ✅ Common/Bones already correct: `Kova\Kams\Common\`, `Kova\Kams\Bones\`

