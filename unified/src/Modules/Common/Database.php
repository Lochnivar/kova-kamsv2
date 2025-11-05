<?php

namespace Kova\Kams\Unified\Modules\Common;

use Kova\Kams\Common\Database as CommonDatabase;

/**
 * Database class for Unified module
 * Extends Common\Database for backward compatibility
 * 
 * @deprecated Use Kova\Kams\Common\Database directly instead
 */
class Database extends CommonDatabase
{
    /**
     * @deprecated Use select(), insert(), update(), delete() instead
     */
    public function dbQuery($sql, ...$values)
    {
        // Legacy method - convert to QueryBuilder if possible
        // For now, delegate to parent's executeQuery if it exists
        if (method_exists('parent', 'executeQuery')) {
            return parent::executeQuery($sql, $values);
        }
        
        // Fallback: use PDO directly (not recommended)
        $qry = $this->getDb()->prepare($sql);
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
                $param = \PDO::PARAM_STR;
            }
            $qry->bindValue($x, $value, $param);
            $x++;
        }
        $qry->execute();
        return $qry->fetchAll();
    }
}
