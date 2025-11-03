<?php

namespace Kova\Unified\Modules\Common;

use Kova\Unified\Modules\Common\Database;

class Config
{
    public $config;

    public function __construct()
    {
        return $this->getConfig();
    }

    public function getConfig()
    {
        $db = new Database("kams");

        $sql = "SELECT * FROM settings";

        $results = $db->dbQuery($sql);

        foreach ($results as $row) {

            $k = $row['setname'];
            if (strpos($row['setvalue'], "~")) {
                $v = explode("~", $row['setvalue']);
            } else {
                $v = $row['setvalue'];
            }

            $this->config[$k] = $v;
        }

        return $this->config;
    }
}
