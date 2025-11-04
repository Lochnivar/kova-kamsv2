<?php

namespace Kova\Kams\Common;

use Doctrine\DBAL\ParameterType;
use Kova\Kams\Bones\DbAdapter;
use Throwable;

/**
 * Shared database helper that preserves the legacy dbQuery-style API while
 * routing all work through Doctrine DBAL.
 */
final class Database
{
    private string $dbKey;
    private DbAdapter $adapter;
    private Db $db;

    public function __construct(string $dbKey = 'kams', ?DbAdapter $adapter = null)
    {
        $this->dbKey = $dbKey;
        $this->adapter = $adapter ?? new DbAdapter();
        $this->db = $this->adapter->getDb($dbKey);
    }

    /**
     * Legacy-compatible query helper. SELECT-style statements return an array
     * of rows; write operations return an empty array.
     */
    public function dbQuery(string $sql, ...$values): array
    {
        $params = $values;
        $types = array_map([$this, 'detectParameterType'], $params);
        $operation = strtoupper(strtok(ltrim($sql), " \t\n\r\0\x0B"));

        try {
            if (in_array($operation, ['SELECT', 'SHOW', 'DESCRIBE', 'PRAGMA'])) {
                $result = $this->db->query($sql, $params, $types);
                $rows = $result->fetchAllAssociative();

                return array_map([$this, 'appendNumericIndexes'], $rows);
            }

            $this->db->execute($sql, $params, $types);

            return [];
        } catch (Throwable $e) {
            $this->logError('DB Query error: ' . $e->getMessage() . ' SQL: ' . $sql);

            return [];
        }
    }

    /**
     * Check to see if the cron is enabled.
     */
    public function checkCronEnabled(string $mod): array
    {
        $sql = 'SELECT setvalue FROM settings WHERE setname = ?';

        return $this->dbQuery($sql, $mod);
    }

    /**
     * Retrieve current Proc ID of mod to kill it.
     */
    public function getProcID(string $mod, string $ifaceId): array
    {
        $sql = 'SELECT procid FROM kamsCrons WHERE module = ? AND ifaceid = ?';
        $result = $this->dbQuery($sql, $mod, $ifaceId);

        if (empty($result)) {
            $insertSql = 'INSERT INTO kamsCrons (module, ifaceid) VALUES (?, ?)';
            $this->dbQuery($insertSql, $mod, $ifaceId);

            return [];
        }

        return $result;
    }

    public function getCurrentPIDs(): array
    {
        $sql = 'SELECT procid FROM kamsCrons';
        $result = $this->dbQuery($sql);

        $pids = [];
        foreach ($result as $row) {
            if (isset($row['procid'])) {
                $pids[] = $row['procid'];
            }
        }

        return $pids;
    }

    /**
     * Update Proc ID of a mod to kill it later.
     */
    public function putProcID(string $mod, $procId, string $ifaceId): void
    {
        $sql = 'INSERT INTO kamsCrons(procid, module, ifaceid) VALUES (?, ?, ?)';
        $this->dbQuery($sql, $procId, $mod, $ifaceId);
    }

    public function getIfaces(string $mod): array
    {
        $sql = 'SELECT ifaceid FROM kamsCrons WHERE module = ?';

        return $this->dbQuery($sql, $mod);
    }

    private function detectParameterType($value): int
    {
        if (is_int($value)) {
            return ParameterType::INTEGER;
        }

        if (is_bool($value)) {
            return ParameterType::BOOLEAN;
        }

        if ($value === null) {
            return ParameterType::NULL;
        }

        return ParameterType::STRING;
    }

    /**
     * Ensure numeric indexes exist alongside associative keys to mirror
     * PDO::FETCH_BOTH behaviour.
     *
     * @param array<int|string,mixed> $row
     * @return array<int|string,mixed>
     */
    private function appendNumericIndexes(array $row): array
    {
        $values = array_values($row);
        foreach ($values as $index => $value) {
            if (!array_key_exists($index, $row)) {
                $row[$index] = $value;
            }
        }

        return $row;
    }

    private function logError(string $message): void
    {
        try {
            file_put_contents('/tmp/lockwoood-errors.log', $message . PHP_EOL, FILE_APPEND);
        } catch (Throwable $_) {
            // ignore logging errors
        }
    }
}
