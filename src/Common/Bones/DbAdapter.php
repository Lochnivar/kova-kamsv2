<?php
namespace Kova\Kams\Bones;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Connection;
use Kova\Kams\Common\Db;
use Kova\Kams\Common\DBALConfigProvider;
use RuntimeException;
use InvalidArgumentException;

final class DbAdapter
{
    private string $envPath;
    private array $env;
    /** @var array<string, Connection> */
    private array $connections = [];
    /** @var array<string, Db> */
    private array $dbAdapters = [];

    /**
     * @param string $envPath path to environment.json
     */
    public function __construct(?string $envPath = null)
    {
        if ($envPath === null) {
            $envPath = getenv('KOVA_DB_CONFIG') ?: __DIR__ . '/../../../config/databases.json';
        }

        $this->envPath = $envPath;
        $json = @file_get_contents($this->envPath);
        if ($json === false) {
            throw new RuntimeException("Environment file not readable: {$this->envPath}");
        }
        $this->env = json_decode($json, true);
        if (!is_array($this->env)) {
            throw new RuntimeException("Invalid JSON in environment file: {$this->envPath}");
        }
    }

    /**
     * Return a cached or newly created Connection for $dbKey.
     */
    public function getConnection(string $dbKey): Connection
    {
        if (isset($this->connections[$dbKey])) {
            return $this->connections[$dbKey];
        }

        if (!isset($this->env[$dbKey]['database']) || !is_array($this->env[$dbKey]['database'])) {
            throw new InvalidArgumentException("Missing database config for key: {$dbKey}");
        }

        $cfg = $this->env[$dbKey]['database'];
        $params = [
            'dbname'   => $cfg['db'] ?? $cfg['database'] ?? null,
            'user'     => $cfg['username'] ?? null,
            'password' => $cfg['password'] ?? null,
            'host'     => $cfg['host'] ?? null,
            'driver'   => $cfg['driver'] ?? 'pdo_mysql',
            'charset'  => $cfg['charset'] ?? 'utf8mb4',
        ];

        foreach (['dbname','user','host'] as $k) {
            if (empty($params[$k])) {
                throw new InvalidArgumentException("Incomplete DB config for key {$dbKey}: missing {$k}");
            }
        }

        $conn = DriverManager::getConnection($params);
        $this->connections[$dbKey] = $conn;
        return $conn;
    }

    /**
     * Return a cached or newly created Db wrapper for $dbKey.
     */
    public function getDb(string $dbKey): Db
    {
        if (isset($this->dbAdapters[$dbKey])) {
            return $this->dbAdapters[$dbKey];
        }
        $db = new Db($this->getConnection($dbKey));
        $this->dbAdapters[$dbKey] = $db;
        return $db;
    }

    /**
     * Convenience: build a DBALConfigProvider for $dbKey.
     */
    public function getConfigProvider(string $dbKey, string $table = 'settings', ?int $ttl = 60): DBALConfigProvider
    {
        return new DBALConfigProvider($this->getConnection($dbKey), $table, $ttl);
    }

    /**
     * Close and remove cached connection/adapter for key.
     */
    public function close(string $dbKey): void
    {
        if (isset($this->connections[$dbKey])) {
            try {
                $this->connections[$dbKey]->close();
            } catch (\Throwable $_) {
            }
            unset($this->connections[$dbKey]);
        }
        unset($this->dbAdapters[$dbKey]);
    }

    /**
     * Tear down all connections/adapters (useful for long-running workers).
     */
    public function closeAll(): void
    {
        foreach (array_keys($this->connections) as $k) {
            $this->close($k);
        }
    }
}
