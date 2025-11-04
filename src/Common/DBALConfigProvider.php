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
     */
    public function get(string $key, $default = null)
    {
        $config = $this->all();

        return $config[$key] ?? $default;
    }

    /**
     * Force a reload from the database.
     */
    public function reload(): void
    {
        $sql = sprintf('SELECT * FROM %s', $this->table);
        $result = $this->connection->executeQuery($sql);

        $data = [];
        while ($row = $result->fetchAssociative()) {
            if ($row === false) {
                continue;
            }

            if (array_key_exists('setname', $row)) {
                $key = $row['setname'];
                $value = $row['setvalue'] ?? null;
            } elseif (array_key_exists('name', $row)) {
                $key = $row['name'];
                $value = $row['value'] ?? null;
            } else {
                $values = array_values($row);
                if (count($values) < 2) {
                    continue;
                }
                $key = (string) $values[0];
                $value = $values[1];
            }

            $data[$key] = $this->normalizeValue($value);
        }

        $this->cache = $data;
        $this->lastLoaded = time();
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
