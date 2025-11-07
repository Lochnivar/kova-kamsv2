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
        // Legacy method - delegate to parent's select() method for SELECT queries
        // This maintains backward compatibility with old dbQuery() calls
        return parent::select($sql, ...$values);
    }
}
