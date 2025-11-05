<?php

namespace Kova\Kams\Common;

use Doctrine\DBAL\ParameterType;

/**
 * Utility for detecting PHP value types and mapping them to Doctrine DBAL ParameterType.
 */
final class TypeDetector
{
    /**
     * Detect the appropriate ParameterType for a given value.
     */
    public static function detectParameterType($value): int
    {
        if (is_int($value)) {
            return ParameterType::INTEGER;
        }

        if (is_bool($value)) {
            return ParameterType::BOOLEAN;
        }

        if ($value instanceof \DateTimeInterface) {
            return ParameterType::STRING;
        }

        if ($value === null) {
            return ParameterType::NULL;
        }

        return ParameterType::STRING;
    }
}


