<?php

namespace Kova\Kcm\Modules\Common;

use PDO;

class Database
{

    public $dbConn;
    public function __construct($db)
    {
        return $this->buildDBConn($db);
    }

    /**
     * Build Database Connection
     */

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

    /**
     * General purpose query function
     */

    public function dbQuery($sql, ...$values)
    {

        $qry = $this->dbConn->prepare($sql);
        $x = 1;
        foreach ($values as $value) {
            if (is_int($value)) {
                $param = \PDO::PARAM_INT;
            } elseif (is_float($value)) {
                $param = \PDO::PARAM_STR;
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
