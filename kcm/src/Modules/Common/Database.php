<?php

namespace Kova\Kcm\Modules\Common;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Throwable;

class Database
{
    /** @var Connection|null */
    public $dbConn;

    public function __construct($db)
    {
        $this->buildDBConn($db);
    }

    /**
     * Build Database Connection
     */
    private function buildDBConn($db): void
    {
        $configPath = __DIR__ . '/configs/environment.json';

        if (!is_file($configPath)) {
            throw new \RuntimeException('Environment configuration not found at ' . $configPath);
        }

        $environ = file_get_contents($configPath);
        $dbValues = json_decode($environ, true);

        if (!is_array($dbValues) || !isset($dbValues[$db]['database'])) {
            throw new \RuntimeException('Database configuration missing for key: ' . $db);
        }

        $dbConfig = $dbValues[$db]['database'];

        $connectionParams = [
            'dbname' => $dbConfig['db'] ?? null,
            'user' => $dbConfig['username'] ?? null,
            'password' => $dbConfig['password'] ?? null,
            'host' => $dbConfig['host'] ?? 'localhost',
            'driver' => 'pdo_mysql',
            'charset' => 'utf8mb4',
        ];

        try {
            $this->dbConn = DriverManager::getConnection($connectionParams, new Configuration());
        } catch (DBALException $e) {
            $this->logError('DB Connection issues : ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * General purpose query function
     */
    public function dbQuery(string $sql, ...$values)
    {
        if (!$this->dbConn instanceof Connection) {
            throw new \RuntimeException('Database connection has not been initialised.');
        }

        $operation = strtoupper(strtok(ltrim($sql), " \t\n\r\0\x0B"));
        $types = array_map([$this, 'detectParameterType'], $values);

        try {
            if (in_array($operation, ['SELECT', 'SHOW', 'DESCRIBE', 'PRAGMA'])) {
                $result = $this->dbConn->executeQuery($sql, $values, $types);
                $rows = $result->fetchAllAssociative();

                return array_map([$this, 'appendNumericIndexes'], $rows);
            }

            $this->dbConn->executeStatement($sql, $values, $types);

            return [];
        } catch (Throwable $e) {
            $this->logError('DB Query error : ' . $e->getMessage() . ' SQL: ' . $sql);

            return [];
        }
    }

    private function detectParameterType($value): int
    {
        if (is_int($value)) {
            return ParameterType::INTEGER;
        }

        if (is_bool($value)) {
            return ParameterType::BOOLEAN;
        }

        if (is_null($value)) {
            return ParameterType::NULL;
        }

        if (is_float($value)) {
            return ParameterType::STRING;
        }

        return ParameterType::STRING;
    }

    /**
     * Add numeric indexes to the row result to mimic PDO::FETCH_BOTH
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
        } catch (Throwable $e) {
            // swallow logging errors to avoid cascading failures
        }
    }

    /**
     * Check to see if the cron is enabled
     * 
     * return @string enabled
     */

    public function checkCronEnabled($mod)
    {

        $sql = "SELECT setvalue FROM settings WHERE setname = ?";

        $result = $this->dbQuery($sql, $mod);

        return $result;
    }

    /**
     * Retrieve current Proc ID of mod to kill it.
     * 
     * @string result
     */

    public function getProcID($mod, $ifaceid)
    {

        $sql = "SELECT procid FROM kamsCrons WHERE module = ? AND ifaceid = ?";

        $result = $this->dbQuery($sql, $mod, $ifaceid);

        var_dump($result);

        if (empty($result) == "true") {
            echo "Array Empty, Adding to Database" . PHP_EOL;

            $sql = "INSERT INTO kamsCrons (module, ifaceid) VALUES (?,?)";

            $this->dbQuery($sql, $mod, $ifaceid);
        }

        return $result;
    }

    public function getCurrentPIDs()
    {
        $pids = [];
        $sql = "SELECT procid FROM kamsCrons";
        $result = $this->dbQuery($sql);
        echo "PIDS in Common Database" . PHP_EOL;
        var_dump($result);
        foreach ($result as $row) {
            $pids[] = $row['procid'];
        }

        return $pids;
    }

    /**
     * Update Proc ID of a mod to kill it later
     * 
     * return @void
     */

    public function putProcID($mod, $procid, $ifaceid)
    {
        $sql = "INSERT INTO kamsCrons(procid, module,ifaceid) VALUES (?,?,?)";

        $result = $this->dbQuery($sql, $procid, $mod, $ifaceid);
    }

    public function getIfaces($mod = "null")
    {

        $sql = "SELECT ifaceid FROM kamsCrons WHERE module = ?";

        $result = $this->dbQuery($sql, $mod);

        return $result;
    }

    private function washResult($result) {}
}
