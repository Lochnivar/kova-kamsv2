<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Validation;

class TypeValidator
{
    public function validate($value, string $type): bool
    {
        switch ($type) {
            case 'int':
                return is_numeric($value) && (int)$value == $value;
                
            case 'float':
                return is_numeric($value);
                
            case 'bool':
                return in_array(strtolower((string)$value), ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off'], true) 
                    || is_bool($value);
                
            case 'json':
                if (is_string($value)) {
                    json_decode($value);
                    return json_last_error() === JSON_ERROR_NONE;
                }
                return is_array($value) || is_object($value);
                
            case 'list':
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    return is_array($decoded) && array_values($decoded) === $decoded;
                }
                return is_array($value) && array_values($value) === $value;
                
            case 'object':
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    return is_array($decoded) && array_values($decoded) !== $decoded;
                }
                return is_object($value) || (is_array($value) && array_values($value) !== $value);
                
            case 'string':
            default:
                return true;
        }
    }
}

