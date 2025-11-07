<?php
namespace Kova\Kams\Common;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use RuntimeException;
use InvalidArgumentException;
use Kova\Kams\Bones\DbAdapter;

final class Db
{
    private Connection $conn;

    /**
     * Construct with an existing Doctrine DBAL Connection.
     */
    public function __construct(Connection $connection)
    {
        $this->conn = $connection;
    }

    /**
     * Convenience factory: build a Db from a Bones DbAdapter and a database key.
     */
    public static function fromBonesAdapter(DbAdapter $adapter, string $dbKey): self
    {
        return new self($adapter->getConnection($dbKey));
    }

    /**
     * Insert a row. $data is column => value. $types optional column => ParameterType::*.
     * Returns affected row count.
     */
    public function insert(string $table, array $data, array $types = []): int
    {
        try {
            return $this->conn->insert($table, $data, $types);
        } catch (\Throwable $e) {
            $qb = $this->conn->createQueryBuilder()->insert($table);
            foreach ($data as $col => $_) {
                $qb->setValue($col, ':' . $col);
            }
            $params = $data;
            $paramTypes = $this->buildParamTypes($params, $types);
            try {
                return $this->conn->executeStatement($qb->getSQL(), $params, $paramTypes);
            } catch (\Throwable $e2) {
                throw new RuntimeException('Insert failed: ' . $e2->getMessage(), 0, $e2);
            }
        }
    }

    /**
     * Update rows by criteria. $criteria is column => value. Returns affected rows.
     */
    public function update(string $table, array $data, array $criteria, array $types = []): int
    {
        try {
            return $this->conn->update($table, $data, $criteria, $types);
        } catch (\Throwable $e) {
            $qb = $this->conn->createQueryBuilder()->update($table);
            foreach ($data as $col => $_) {
                $qb->set($col, ':' . $col);
            }
            $whereParts = [];
            foreach ($criteria as $col => $_) {
                $whereParts[] = $qb->expr()->eq($col, ':' . $col . '_crit');
            }
            $qb->where(...$whereParts);

            $params = [];
            foreach ($data as $k => $v) $params[$k] = $v;
            foreach ($criteria as $k => $v) $params[$k . '_crit'] = $v;

            $paramTypes = $this->buildParamTypes($params, $types);
            try {
                return $this->conn->executeStatement($qb->getSQL(), $params, $paramTypes);
            } catch (\Throwable $e2) {
                throw new RuntimeException('Update failed: ' . $e2->getMessage(), 0, $e2);
            }
        }
    }

    /**
     * Delete rows by criteria. Returns affected rows.
     */
    public function delete(string $table, array $criteria, array $types = []): int
    {
        try {
            return $this->conn->delete($table, $criteria, $types);
        } catch (\Throwable $e) {
            $qb = $this->conn->createQueryBuilder()->delete($table);
            $whereParts = [];
            foreach ($criteria as $col => $_) {
                $whereParts[] = $qb->expr()->eq($col, ':' . $col);
            }
            $qb->where(...$whereParts);

            $params = $criteria;
            $paramTypes = $this->buildParamTypes($params, $types);
            try {
                return $this->conn->executeStatement($qb->getSQL(), $params, $paramTypes);
            } catch (\Throwable $e2) {
                throw new RuntimeException('Delete failed: ' . $e2->getMessage(), 0, $e2);
            }
        }
    }

    /**
     * Fetch many rows from $table. $criteria is optional column=>value.
     * Returns array of associative arrays.
     */
    public function fetchAll(string $table, array $criteria = [], array $orderBy = [], int $limit = null, int $offset = null): array
    {
        $qb = $this->conn->createQueryBuilder()->select('*')->from($table);
        foreach ($criteria as $col => $val) {
            $qb->andWhere($qb->expr()->eq($col, ':' . $col))->setParameter($col, $val, $this->detectType($val));
        }
        foreach ($orderBy as $col => $dir) {
            $qb->addOrderBy($col, $dir);
        }
        if ($limit !== null) $qb->setMaxResults($limit);
        if ($offset !== null) $qb->setFirstResult($offset);
        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Fetch a single row (or null).
     */
    public function fetchOne(string $table, array $criteria = []): ?array
    {
        $rows = $this->fetchAll($table, $criteria, [], 1);
        return $rows[0] ?? null;
    }

    /**
     * Low-level query: accepts raw SQL or QueryBuilder; returns Doctrine\DBAL\Result.
     */
    public function query($sqlOrQb, array $params = [], array $types = [])
    {
        if ($sqlOrQb instanceof QueryBuilder) {
            return $this->conn->executeQuery($sqlOrQb->getSQL(), $params, $types);
        }
        return $this->conn->executeQuery($sqlOrQb, $params, $types);
    }

    /**
     * Execute arbitrary statement (INSERT/UPDATE/DELETE) with parameters and types.
     */
    public function execute(string $sql, array $params = [], array $types = []): int
    {
        return $this->conn->executeStatement($sql, $params, $types);
    }

    /**
     * Transaction helper that runs $callable($this) inside a transaction.
     */
    public function transactional(callable $callable)
    {
        return $this->conn->transactional(function (Connection $c) use ($callable) {
            return $callable($this);
        });
    }

    /**
     * Expose raw Connection when needed (transactions, QueryBuilder, advanced ops).
     */
    public function getConnection(): Connection
    {
        return $this->conn;
    }

    /**
     * Close the underlying connection (convenience).
     */
    public function close(): void
    {
        try {
            $this->conn->close();
        } catch (\Throwable $_) {
        }
    }

    /**
     * Build parameter types array for executeStatement.
     */
    private function buildParamTypes(array $params, array $explicitTypes = []): array
    {
        $mapped = [];
        foreach ($params as $k => $v) {
            if (array_key_exists($k, $explicitTypes)) {
                $mapped[$k] = $explicitTypes[$k];
                continue;
            }
            $mapped[$k] = $this->detectType($v);
        }
        return $mapped;
    }

    /**
     * Heuristic type detection for ParameterType mapping.
     */
    private function detectType($value): ParameterType
    {
        return TypeDetector::detectParameterType($value);
    }
}
