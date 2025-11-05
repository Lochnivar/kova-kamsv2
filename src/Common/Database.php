<?php

namespace Kova\Kams\Common;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Kova\Kams\Bones\DbAdapter;
use Throwable;

/**
 * Shared database helper that preserves the legacy dbQuery-style API while
 * routing all work through Doctrine DBAL.
 */
class Database
{
    private string $dbKey;
    private DbAdapter $adapter;
    private Db $db;
    private ?Logger $logger;

    public function __construct(string $dbKey = 'kams', ?DbAdapter $adapter = null, ?Logger $logger = null)
    {
        $this->dbKey = $dbKey;
        $this->adapter = $adapter ?? new DbAdapter();
        $this->db = $this->adapter->getDb($dbKey);
        $this->logger = $logger;
    }


    /**
     * Execute a SELECT query and return all rows as associative arrays with numeric indexes.
     * 
     * @param string $sql SQL query with ? placeholders
     * @param mixed ...$values Parameter values
     * @return array<int, array<int|string, mixed>>
     */
    public function select(string $sql, ...$values): array
    {
        $params = $values;
        $types = array_map([TypeDetector::class, 'detectParameterType'], $params);

        try {
                $result = $this->db->query($sql, $params, $types);
                $rows = $result->fetchAllAssociative();
                return array_map([$this, 'appendNumericIndexes'], $rows);
        } catch (Throwable $e) {
            $this->logError('DB Select error: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            if (defined('KOVA_ENV') && KOVA_ENV === 'development') {
                throw $e;
            }
            return [];
        }
    }

    /**
     * Execute a SELECT query and return the first row, or null if no rows.
     * 
     * @param string $sql SQL query with ? placeholders
     * @param mixed ...$values Parameter values
     * @return array<int|string, mixed>|null
     */
    public function selectOne(string $sql, ...$values): ?array
    {
        $rows = $this->select($sql, ...$values);
        return $rows[0] ?? null;
    }

    /**
     * Execute an INSERT statement.
     * 
     * @param string $table Table name
     * @param array<string, mixed> $data Column => value pairs
     * @return int Number of affected rows
     */
    public function insert(string $table, array $data): int
    {
        try {
            return $this->db->insert($table, $data);
        } catch (Throwable $e) {
            $this->logError('DB Insert error: ' . $e->getMessage(), [
                'table' => $table,
                'data' => $data,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            if (defined('KOVA_ENV') && KOVA_ENV === 'development') {
                throw $e;
            }
            return 0;
        }
    }

    /**
     * Execute an UPDATE statement.
     * 
     * @param string $table Table name
     * @param array<string, mixed> $data Column => value pairs to update
     * @param array<string, mixed> $criteria WHERE clause criteria
     * @return int Number of affected rows
     */
    public function update(string $table, array $data, array $criteria): int
    {
        try {
            return $this->db->update($table, $data, $criteria);
        } catch (Throwable $e) {
            $this->logError('DB Update error: ' . $e->getMessage(), [
                'table' => $table,
                'data' => $data,
                'criteria' => $criteria,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            if (defined('KOVA_ENV') && KOVA_ENV === 'development') {
                throw $e;
            }
            return 0;
        }
    }

    /**
     * Execute a DELETE statement.
     * 
     * @param string $table Table name
     * @param array<string, mixed> $criteria WHERE clause criteria
     * @return int Number of affected rows
     */
    public function delete(string $table, array $criteria): int
    {
        try {
            return $this->db->delete($table, $criteria);
        } catch (Throwable $e) {
            $this->logError('DB Delete error: ' . $e->getMessage(), [
                'table' => $table,
                'criteria' => $criteria,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            if (defined('KOVA_ENV') && KOVA_ENV === 'development') {
                throw $e;
            }
            return 0;
        }
    }

    /**
     * Execute raw SQL (INSERT/UPDATE/DELETE/TRUNCATE etc.) with parameters.
     * 
     * @param string $sql SQL statement with ? placeholders
     * @param mixed ...$values Parameter values
     * @return int Number of affected rows
     */
    public function executeQuery(string $sql, ...$values): int
    {
        $params = $values;
        $types = array_map([TypeDetector::class, 'detectParameterType'], $params);

        try {
            return $this->db->execute($sql, $params, $types);
        } catch (Throwable $e) {
            $this->logError('DB Execute error: ' . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            if (defined('KOVA_ENV') && KOVA_ENV === 'development') {
                throw $e;
            }
            return 0;
        }
    }

    /**
     * Get the last inserted ID.
     * 
     * @return string|int
     */
    public function lastInsertId()
    {
        return $this->db->getConnection()->lastInsertId();
    }

    /**
     * Get the underlying Db instance for advanced operations.
     * 
     * @return Db
     */
    public function getDb(): Db
    {
        return $this->db;
    }

    /**
     * Create a QueryBuilder instance for building queries.
     * 
     * @return QueryBuilder
     */
    public function createQueryBuilder(): QueryBuilder
    {
        return $this->db->getConnection()->createQueryBuilder();
    }

    /**
     * Execute a QueryBuilder and return all rows with numeric indexes.
     * 
     * @param QueryBuilder $qb QueryBuilder instance
     * @return array<int, array<int|string, mixed>>
     */
    public function executeQueryBuilder(QueryBuilder $qb): array
    {
        try {
            $result = $qb->executeQuery();
            $rows = $result->fetchAllAssociative();
            return array_map([$this, 'appendNumericIndexes'], $rows);
        } catch (Throwable $e) {
            $this->logError('DB QueryBuilder error: ' . $e->getMessage(), [
                'sql' => $qb->getSQL(),
                'params' => $qb->getParameters(),
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            if (defined('KOVA_ENV') && KOVA_ENV === 'development') {
                throw $e;
            }
            return [];
        }
    }

    /**
     * Check to see if the cron is enabled.
     */
    public function checkCronEnabled(string $mod): array
    {
        $qb = $this->createQueryBuilder();
        $qb->select('setvalue')
           ->from('settings')
           ->where('setname = :mod')
           ->setParameter('mod', $mod);
        return $this->executeQueryBuilder($qb);
    }

    /**
     * Retrieve current Proc ID of mod to kill it.
     */
    public function getProcID(string $mod, string $ifaceId): array
    {
        $qb = $this->createQueryBuilder();
        $qb->select('procid')
           ->from('kamsCrons')
           ->where('module = :mod')
           ->andWhere('ifaceid = :iface')
           ->setParameter('mod', $mod)
           ->setParameter('iface', $ifaceId);
        $result = $this->executeQueryBuilder($qb);

        if (empty($result)) {
            $this->insert('kamsCrons', ['module' => $mod, 'ifaceid' => $ifaceId]);
            return [];
        }

        return $result;
    }

    public function getCurrentPIDs(): array
    {
        $qb = $this->createQueryBuilder();
        $qb->select('procid')
           ->from('kamsCrons');
        $result = $this->executeQueryBuilder($qb);

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
        $this->insert('kamsCrons', ['procid' => $procId, 'module' => $mod, 'ifaceid' => $ifaceId]);
    }

    public function getIfaces(string $mod): array
    {
        $qb = $this->createQueryBuilder();
        $qb->select('ifaceid')
           ->from('kamsCrons')
           ->where('module = :mod')
           ->setParameter('mod', $mod);
        return $this->executeQueryBuilder($qb);
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

    private function logError(string $message, array $context = []): void
    {
        if ($this->logger === null) {
            $this->logger = new Logger('database.log');
        }
        $this->logger->error($message, $context);
    }
}
