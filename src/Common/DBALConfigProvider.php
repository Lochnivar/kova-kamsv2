<?php

namespace Kova\Kams\Common;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;

/**
 * Lightweight settings provider backed by a Doctrine DBAL connection.
 *
 * Reads key/value pairs from a table (default `settings`) and caches them in-memory
 * for the duration defined by $ttl. Intended as a drop-in replacement for the
 * legacy Config loader used by KCM and Unified modules.
 */
final class DBALConfigProvider
{
    private Connection $connection;
    private string $table;
    private ?int $ttl;
    
    /** @var array<string, string> Cache of setting types from database */
    private array $typeCache = [];

    /** @var array<string, mixed> */
    private array $cache = [];
    private ?int $lastLoaded = null;

    public function __construct(Connection $connection, string $table = 'settings', ?int $ttl = 60)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->ttl = $ttl;
    }

    /**
     * Retrieve the full configuration array (cached).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->shouldReload()) {
            $this->reload();
        }

        return $this->cache;
    }

    /**
     * Get a single config value, or default if missing.
     * 
     * @param string $key The setting key
     * @param mixed $default Default value if not found
     * @param string|null $expectedType Optional type for normalization (bool, int, string, object, list)
     * @return mixed The config value, normalized if type is provided
     */
    public function get(string $key, $default = null, ?string $expectedType = null)
    {
        $config = $this->all();
        $value = $config[$key] ?? $default;
        
        // Type normalization if type is provided
        if ($expectedType !== null && $value !== null) {
            return SettingsValueNormalizer::normalizeForUse($value, $expectedType);
        }
        
        // If we have type info from DB, use it
        if ($value !== null && isset($this->typeCache[$key])) {
            return SettingsValueNormalizer::normalizeForUse($value, $this->typeCache[$key]);
        }
        
        return $value;
    }
    
    /**
     * Get a boolean config value.
     * 
     * @param string $key The setting key
     * @param bool $default Default value if not found
     * @return bool The boolean value
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default ? '1' : '0', 'bool');
        return SettingsValueNormalizer::normalizeBool($value);
    }
    
    /**
     * Get an integer config value.
     * 
     * @param string $key The setting key
     * @param int $default Default value if not found
     * @return int The integer value
     */
    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key, (string)$default, 'int');
        return (int)$value;
    }
    
    /**
     * Get a string config value.
     * 
     * @param string $key The setting key
     * @param string $default Default value if not found
     * @return string The string value
     */
    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default, 'string');
        return (string)$value;
    }
    
    /**
     * Get an object/array config value.
     * 
     * @param string $key The setting key
     * @param array $default Default value if not found
     * @return array The array/object value
     */
    public function getObject(string $key, array $default = []): array
    {
        $value = $this->get($key, json_encode($default), 'object');
        if (is_array($value)) {
            return $value;
        }
        return $default;
    }
    
    /**
     * Get a list/array config value.
     * 
     * @param string $key The setting key
     * @param array $default Default value if not found
     * @return array The list value
     */
    public function getList(string $key, array $default = []): array
    {
        $value = $this->get($key, json_encode($default), 'list');
        if (is_array($value)) {
            return $value;
        }
        return $default;
    }

    /**
     * Force a reload from the database.
     */
    public function reload(): void
    {
        $sql = sprintf('SELECT * FROM %s', $this->table);
        $result = $this->connection->executeQuery($sql);

        $data = [];
        $types = [];
        
        while ($row = $result->fetchAssociative()) {
            if ($row === false) {
                continue;
            }

            // Determine key and value columns (handle both old and new schemas)
            if (array_key_exists('setname', $row)) {
                $key = $row['setname'];
                $value = $row['setvalue'] ?? null;
                $type = $row['type'] ?? null;
            } elseif (array_key_exists('name', $row)) {
                $key = $row['name'];
                $value = $row['value'] ?? null;
                $type = $row['type'] ?? null;
            } else {
                $values = array_values($row);
                if (count($values) < 2) {
                    continue;
                }
                $key = (string) $values[0];
                $value = $values[1];
                $type = null;
            }
            
            // Debug: Log raw database values for interface settings
            if (in_array($key, ['UDPInterfaceName', 'SerialInterfaceName', 'MotorolaInterfaceName'])) {
                error_log("DBALConfigProvider::reload(): RAW DB value for '{$key}': type=" . gettype($value));
                error_log("DBALConfigProvider::reload(): RAW DB value string: " . ($value ?? 'NULL'));
                error_log("DBALConfigProvider::reload(): DB type column: " . ($type ?? 'NULL'));
            }
            
            // Store type info for normalization
            if ($type) {
                $types[$key] = $type;
            }

            // Normalize value based on type if available
            if ($type) {
                $normalized = SettingsValueNormalizer::normalizeForUse($value, $type);
                // Debug: Log normalized value for interface settings
                if (in_array($key, ['UDPInterfaceName', 'SerialInterfaceName', 'MotorolaInterfaceName'])) {
                    error_log("DBALConfigProvider::reload(): NORMALIZED value for '{$key}': type=" . gettype($normalized));
                    if (is_array($normalized)) {
                        error_log("DBALConfigProvider::reload(): NORMALIZED array: " . json_encode($normalized));
                    } elseif (is_string($normalized)) {
                        error_log("DBALConfigProvider::reload(): NORMALIZED string: " . substr($normalized, 0, 500));
                    }
                }
                $data[$key] = $normalized;
            } else {
                $data[$key] = $this->normalizeValue($value);
            }
        }

        $this->cache = $data;
        $this->typeCache = $types;
        $this->lastLoaded = time();
    }
    
    /**
     * Clear the cache (force reload on next access).
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->typeCache = [];
        $this->lastLoaded = null;
    }

    private function shouldReload(): bool
    {
        if ($this->lastLoaded === null) {
            return true;
        }

        if ($this->ttl === null) {
            return false;
        }

        return (time() - $this->lastLoaded) >= $this->ttl;
    }

    /**
     * Normalize database values into PHP scalars/arrays.
     */
    private function normalizeValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return '';
        }

        $json = json_decode($trimmed, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        if (strpos($trimmed, '~') !== false) {
            return array_map('trim', explode('~', $trimmed));
        }

        return $trimmed;
    }
}
