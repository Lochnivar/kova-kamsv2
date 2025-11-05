<?php

namespace Kova\Kams\Unified\Modules\Common;

use Kova\Kams\Core\Communicator as CoreCommunicator;
use Kova\Kams\Unified\Modules\Common\Config;

/**
 * Communicator class for Unified module
 * Extends Core\Communicator and adds Unified-specific methods
 */
class Communicator extends CoreCommunicator
{
    public function __construct()
    {
        $config = new Config();
        parent::__construct($config);
    }
}
