<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Common;

use Kova\Kams\Common\Config as CommonConfig;

/**
 * Config class for Unified module
 * Extends Common\Config - parent class already loads config from database
 * No additional methods needed - just use parent functionality
 */
class Config extends CommonConfig
{
    // Parent class already:
    // - Has public array $config property
    // - Loads config in constructor
    // - Provides get() method
    // No additional methods needed
}
