<?php

namespace Kova\Kams\Kcm\Modules\Common;

use Kova\Kams\Core\Communicator as CoreCommunicator;
use Kova\Kams\Kcm\Modules\Common\Config;

/**
 * KCM Communicator - uses Core\Communicator directly.
 * This is now just a type alias for convenience.
 */
class Communicator extends CoreCommunicator
{
    /**
     * Create a KCM Communicator instance with KCM Config.
     */
    public function __construct()
    {
        parent::__construct(new Config());
    }
}
