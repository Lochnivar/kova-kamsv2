<?php

namespace Kova\Kcm\Modules\Common;

use Exception;

/**
 * Backwards-compatible Config loader that accepts multiple Database module shapes:
 * - existing Common\Database (legacy)
 * - new Kova\Kcm\Modules\Database\Database
 * - a passed-in database object (any object exposing a dbQuery(string) method)
 * - falls back to a PDO DSN string (will create PDO and query)
 *
 * Usage:
 *  new Config();                         // uses legacy Common\Database("kams") if present
 *  new Config('kams');                   // passes "kams" through to DB constructors that accept it
 *  new Config($dbObject);                // uses provided DB object (must have dbQuery method)
 *
 * The class reads the settings table and normalizes values (JSON decode, tilde-list fallback).
 */
class Config
{
    /** @var array<string,mixed> */
    public $config = [];

    /** @var object|null */
    protected $db = null;

    /**
     * @param mixed|null $dbParam optional: database instance or constructor parameter (string DSN/identifier)
     */
    public function __construct($dbParam = null)
    {
        $this->initDb($dbParam);
        $this->getConfig();
    }

    /**
     * Initialize $this->db using available Database classes or provided instance.
     *
     * @param mixed $dbParam
     * @return void
     */
    protected function initDb($dbParam = null): void
    {
        // If caller provided an object that has dbQuery, use it directly.
        if (is_object($dbParam) && method_exists($dbParam, 'dbQuery')) {
            $this->db = $dbParam;
            return;
        }

        // Try several likely DB class names that might exist in the upgraded module.
        $candidates = [
            // legacy
            '\\Kova\\Kcm\\Modules\\Common\\Database',
            // hypothetical new module location
            '\\Kova\\Kcm\\Modules\\Database\\Database',
            '\\Kova\\Kcm\\Database\\Database',
            '\\Kova\\Database\\Database',
            // generic Database class
            '\\Database',
        ];

        foreach ($candidates as $class) {
            if (class_exists($class)) {
                try {
                    // If dbParam is provided, attempt to pass it to the constructor
                    if ($dbParam !== null) {
                        $this->db = new $class($dbParam);
                    } else {
                        // common legacy used "kams" as parameter; try that first
                        try {
                            $this->db = new $class('kams');
                        } catch (Exception $e) {
                            // try no-arg constructor
                            $this->db = new $class();
                        }
                    }

                    // verify we have a usable object
                    if (is_object($this->db) && method_exists($this->db, 'dbQuery')) {
                        return;
                    }
                } catch (Exception $e) {
                    // try next candidate
                    $this->db = null;
                }
            }
        }

        // If dbParam looks like a PDO DSN string, try to create PDO (minimal support)
        if (is_string($dbParam) && stripos($dbParam, 'mysql:') === 0) {
            try {
                $pdo = new \PDO($dbParam);
                $this->db = new class($pdo) {
                    private $pdo;
                    public function __construct($pdo) { $this->pdo = $pdo; }
                    public function dbQuery(string $sql) {
                        $stmt = $this->pdo->query($sql);
                        return $stmt ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];
                    }
                };
                return;
            } catch (Exception $e) {
                // fall through
            }
        }

        // As a last resort, throw so calling code knows DB wasn't initialized
        throw new Exception('No compatible Database module found. Provide a DB instance with dbQuery(string) or install a supported Database class.');
    }

    /**
     * Load configuration from settings table.
     *
     * Supports columns named either (setname,setvalue) or (name,value).
     * Automatically decodes JSON values; preserves plain scalars.
     *
     * @return array<string,mixed>
     */
    public function getConfig(): array
    {
        // If db is not initialized for some reason, attempt lazy init with legacy Database
        if ($this->db === null) {
            $this->initDb('kams');
        }

        // Query the settings table. Use a safe SQL string; modules may alias columns differently.
        $sql = "SELECT * FROM settings";

        $results = $this->db->dbQuery($sql);

        if (!is_array($results)) {
            return $this->config;
        }

        foreach ($results as $row) {
            if (!is_array($row)) {
                continue;
            }

            // accept either naming convention
            if (array_key_exists('setname', $row)) {
                $k = $row['setname'];
                $raw = $row['setvalue'] ?? '';
            } elseif (array_key_exists('name', $row)) {
                $k = $row['name'];
                $raw = $row['value'] ?? '';
            } else {
                // fallback to first two columns if present
                $cols = array_values($row);
                if (count($cols) >= 2) {
                    $k = (string)$cols[0];
                    $raw = $cols[1];
                } else {
                    continue;
                }
            }

            $v = $this->normalizeValue($raw);

            $this->config[$k] = $v;
        }

        return $this->config;
    }

    /**
     * Normalize a raw DB string into an appropriate PHP value.
     * - If the string is valid JSON, decode it to array/object
     * - Else if contains ~ and looks like a delimiter-list, split to array
     * - Else return the trimmed scalar string
     *
     * @param mixed $raw
     * @return mixed
     */
    protected function normalizeValue($raw)
    {
        if ($raw === null) {
            return null;
        }

        if (!is_string($raw)) {
            return $raw;
        }

        $trimmed = trim($raw);

        if ($trimmed !== '') {
            $json = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }

        if (strpos($trimmed, '~') !== false) {
            $parts = array_map('trim', explode('~', $trimmed));
            return $parts;
        }

        return $trimmed;
    }
}
