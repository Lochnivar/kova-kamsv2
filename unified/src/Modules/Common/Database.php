<?php

namespace Kova\Unified\Modules\Common;

use PDO;

class Database
{

    public $dbConn;
    public function __construct($db)
    {
        return $this->buildDBConn($db);
    }
    private function buildDBConn($db)
    {
        $environ = file_get_contents(__DIR__ . "/configs/environment.json");
        
      //  echo $environ . PHP_EOL;
        $dbValues = json_decode($environ, true);

        $host = $dbValues[$db]['database']['host'];
        $username = $dbValues[$db]['database']['username'];
        $password = $dbValues[$db]['database']['password'];
        $dbase = $dbValues[$db]['database']['db'];

        try {
            $this->dbConn = new \PDO("mysql:dbname=" . $dbase . ";host=" . $host, $username, $password);
        } catch (\Exception $e) {
            var_dump("Error");
            file_put_contents("/tmp/lockwoood-errors.log", "DB Connection issues : " . $e->getMessage(), FILE_APPEND);
        }
    }

    public function dbQuery($sql, ...$values)
    {
        $qry = $this->dbConn->prepare($sql);
        $x = 1;
        foreach ($values as $value) {
            if (is_int($value)) {
                $param = \PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $param = \PDO::PARAM_BOOL;
            } elseif (is_null($value)) {
                $param = \PDO::PARAM_NULL;
            } elseif (is_string($value)) {
                $param = \PDO::PARAM_STR;
            } else {
                $param = FALSE;
            }

            $qry->bindValue($x, $value, $param);
            $x++;
        }
        $qry->execute();

        $response = $qry->fetchAll();


        return $response;
    }
}
