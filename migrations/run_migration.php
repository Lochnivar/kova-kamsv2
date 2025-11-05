<?php
/**
 * Migration Runner for Settings Schema Standardization
 * 
 * This script safely runs the migration, checking for existing structures
 * and only applying changes that are needed.
 */

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../app/bootstrap.php');

use Kova\Kams\Bones\DbAdapter;
use Kova\Kams\Common\Logger;

$logger = new Logger('migration.log');

try {
    $adapter = new DbAdapter();
    $conn = $adapter->getConnection('kams');
    
    $logger->info('Starting settings schema standardization migration');
    
    // Check current table structure
    $result = $conn->executeQuery('SHOW TABLES LIKE "settings"');
    $tableExists = $result->fetchOne();
    
    if (!$tableExists) {
        $logger->error('Settings table does not exist');
        echo "ERROR: Settings table does not exist\n";
        exit(1);
    }
    
    // Get current columns
    $columns = [];
    $result = $conn->executeQuery('DESCRIBE settings');
    while ($row = $result->fetchAssociative()) {
        $columns[$row['Field']] = $row;
    }
    
    echo "Current columns: " . implode(', ', array_keys($columns)) . "\n";
    
    // Step 1: Migrate data from old columns if they exist
    if (isset($columns['setname']) || isset($columns['setvalue'])) {
        echo "Migrating data from old columns...\n";
        $logger->info('Migrating data from old columns');
        
        $sql = "UPDATE settings 
                SET name = COALESCE(NULLIF(name, ''), setname),
                    value = COALESCE(NULLIF(value, ''), setvalue),
                    description = COALESCE(NULLIF(description, ''), sethint)
                WHERE (setname IS NOT NULL AND setname != '' AND (name IS NULL OR name = ''))
                   OR (setvalue IS NOT NULL AND setvalue != '' AND (value IS NULL OR value = ''))";
        
        $affected = $conn->executeStatement($sql);
        echo "Migrated {$affected} rows\n";
        $logger->info("Migrated {$affected} rows from old columns");
    } else {
        echo "No old columns found - skipping migration step\n";
    }
    
    // Step 2: Set default values
    echo "Setting default values...\n";
    
    $conn->executeStatement("UPDATE settings SET type = COALESCE(NULLIF(type, ''), 'string') WHERE type IS NULL OR type = ''");
    $conn->executeStatement("UPDATE settings SET group_name = COALESCE(NULLIF(group_name, ''), 'default') WHERE group_name IS NULL OR group_name = ''");
    $conn->executeStatement("UPDATE settings SET sort_order = COALESCE(sort_order, 100) WHERE sort_order IS NULL");
    
    echo "Default values set\n";
    
    // Step 3: Check for duplicate names
    $result = $conn->executeQuery("SELECT name, COUNT(*) as cnt FROM settings WHERE name IS NOT NULL AND name != '' GROUP BY name HAVING cnt > 1");
    $duplicates = $result->fetchAllAssociative();
    
    if (!empty($duplicates)) {
        echo "WARNING: Found duplicate setting names:\n";
        foreach ($duplicates as $dup) {
            echo "  - {$dup['name']}: {$dup['cnt']} occurrences\n";
        }
        echo "Cannot add unique constraint until duplicates are resolved.\n";
        $logger->warning('Found duplicate setting names', ['duplicates' => $duplicates]);
    } else {
        echo "No duplicate names found - safe to add unique constraint\n";
    }
    
    // Step 4: Check and create indexes
    echo "Checking indexes...\n";
    
    $result = $conn->executeQuery("SHOW INDEXES FROM settings WHERE Key_name = 'idx_settings_name'");
    if (!$result->fetchOne()) {
        echo "Creating index idx_settings_name...\n";
        $conn->executeStatement("CREATE INDEX idx_settings_name ON settings(name)");
        echo "Index created\n";
    } else {
        echo "Index idx_settings_name already exists\n";
    }
    
    $result = $conn->executeQuery("SHOW INDEXES FROM settings WHERE Key_name = 'idx_settings_group'");
    if (!$result->fetchOne()) {
        echo "Creating index idx_settings_group...\n";
        $conn->executeStatement("CREATE INDEX idx_settings_group ON settings(group_name)");
        echo "Index created\n";
    } else {
        echo "Index idx_settings_group already exists\n";
    }
    
    // Step 5: Add unique constraint if no duplicates
    if (empty($duplicates)) {
        $result = $conn->executeQuery("SHOW INDEXES FROM settings WHERE Key_name = 'idx_settings_name_unique'");
        if (!$result->fetchOne()) {
            echo "Creating unique index idx_settings_name_unique...\n";
            try {
                $conn->executeStatement("CREATE UNIQUE INDEX idx_settings_name_unique ON settings(name)");
                echo "Unique index created\n";
                $logger->info('Unique index on name created');
            } catch (\Exception $e) {
                echo "WARNING: Could not create unique index: " . $e->getMessage() . "\n";
                $logger->warning('Could not create unique index', ['error' => $e->getMessage()]);
            }
        } else {
            echo "Unique index idx_settings_name_unique already exists\n";
        }
    } else {
        echo "Skipping unique index creation due to duplicates\n";
    }
    
    // Final verification
    echo "\n=== Verification ===\n";
    $result = $conn->executeQuery("SELECT COUNT(*) as total FROM settings");
    $total = $result->fetchOne();
    echo "Total settings: {$total}\n";
    
    $result = $conn->executeQuery("SELECT COUNT(*) as with_name FROM settings WHERE name IS NOT NULL AND name != ''");
    $withName = $result->fetchOne();
    echo "Settings with name: {$withName}\n";
    
    $result = $conn->executeQuery("SELECT COUNT(*) as with_value FROM settings WHERE value IS NOT NULL AND value != ''");
    $withValue = $result->fetchOne();
    echo "Settings with value: {$withValue}\n";
    
    $result = $conn->executeQuery("SHOW INDEXES FROM settings");
    $indexes = [];
    while ($row = $result->fetchAssociative()) {
        if (!isset($indexes[$row['Key_name']])) {
            $indexes[$row['Key_name']] = $row['Key_name'];
        }
    }
    echo "Indexes: " . implode(', ', array_keys($indexes)) . "\n";
    
    echo "\nMigration completed successfully!\n";
    $logger->info('Migration completed successfully');
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    $logger->error('Migration failed', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    exit(1);
}

