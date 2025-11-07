<?php

namespace Kova\Kams\Common;

/**
 * Normalizes settings values for storage and use.
 * 
 * Ensures consistent type handling across the application:
 * - Storage: Converts PHP values to normalized string/JSON format
 * - Retrieval: Converts stored values back to appropriate PHP types
 */
class SettingsValueNormalizer
{
    /**
     * Normalize a value for storage in the database.
     * 
     * @param mixed $value The value to normalize
     * @param string $type The expected type (bool, int, float, string, object, list)
     * @return string Normalized string representation for storage
     */
    public static function normalizeForStorage($value, string $type): string
    {
        switch ($type) {
            case 'bool':
                return self::normalizeBool($value) ? '1' : '0';
            
            case 'int':
                return (string)(int)$value;
            
            case 'float':
                return (string)(float)$value;
            
            case 'object':
            case 'list':
                // Ensure valid JSON encoding
                if (is_string($value)) {
                    // If already a JSON string, validate and return it (don't double-encode)
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        // Valid JSON array/object - re-encode to ensure proper formatting
                        $normalized = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                        error_log("SettingsValueNormalizer::normalizeForStorage - String input decoded and re-encoded: " . substr($value, 0, 100) . " -> " . substr($normalized, 0, 100));
                        return $normalized;
                    }
                    // If JSON decode failed but it looks like JSON, return as-is
                    if (trim($value) !== '' && ($value[0] === '[' || $value[0] === '{')) {
                        error_log("SettingsValueNormalizer::normalizeForStorage - String input looks like JSON but decode failed, returning as-is: " . substr($value, 0, 100));
                        return $value;
                    }
                    // Empty string or invalid JSON - encode as empty array
                    error_log("SettingsValueNormalizer::normalizeForStorage - String input invalid, encoding as empty array");
                    return $type === 'list' ? '[]' : '{}';
                }
                // If it's already an array/object, encode it
                if (is_array($value) || is_object($value)) {
                    $normalized = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    error_log("SettingsValueNormalizer::normalizeForStorage - Array/object input encoded: " . substr($normalized, 0, 100));
                    return $normalized;
                }
                // Otherwise encode the value
                $normalized = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                error_log("SettingsValueNormalizer::normalizeForStorage - Other type encoded: " . substr($normalized, 0, 100));
                return $normalized;
            
            case 'string':
            default:
                return (string)$value;
        }
    }
    
    /**
     * Normalize a stored value for use in PHP code.
     * 
     * @param mixed $value The stored value (usually string)
     * @param string $type The expected type
     * @return mixed Normalized PHP value
     */
    public static function normalizeForUse($value, string $type)
    {
        if ($value === null) {
            return null;
        }
        
        switch ($type) {
            case 'bool':
                return self::normalizeBool($value);
            
            case 'int':
                return (int)$value;
            
            case 'float':
                return (float)$value;
            
            case 'object':
            case 'list':
                // Try to decode JSON
                if (is_string($value)) {
                    $decoded = json_decode($value, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        return $decoded;
                    }
                    // Legacy support: try ~ delimiter for arrays
                    if ($type === 'list' && strpos($value, '~') !== false) {
                        return array_map('trim', explode('~', $value));
                    }
                }
                // If already an array/object, return as-is
                if (is_array($value)) {
                    return $value;
                }
                // Fallback: return empty array/list or object
                return $type === 'list' ? [] : [];
            
            case 'string':
            default:
                return (string)$value;
        }
    }
    
    /**
     * Normalize a value to a boolean.
     * 
     * Handles various boolean representations:
     * - true/false (boolean)
     * - 1/0 (integer)
     * - "1"/"0", "yes"/"no", "true"/"false", "on"/"off" (string)
     * 
     * @param mixed $value The value to normalize
     * @return bool Normalized boolean value
     */
    public static function normalizeBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_int($value)) {
            return $value !== 0;
        }
        
        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['1', 'yes', 'true', 'on'], true);
        }
        
        // For arrays/objects, consider non-empty as true
        if (is_array($value)) {
            return !empty($value);
        }
        
        // Default to false for unknown types
        return false;
    }
    
    /**
     * Get the type of a value (for validation).
     * 
     * @param mixed $value The value to check
     * @return string Detected type (bool, int, float, string, object, list)
     */
    public static function detectType($value): string
    {
        if (is_bool($value)) {
            return 'bool';
        }
        
        if (is_int($value)) {
            return 'int';
        }
        
        if (is_float($value)) {
            return 'float';
        }
        
        if (is_array($value)) {
            // Determine if it's a list (numeric keys) or object (associative)
            if (array_keys($value) === range(0, count($value) - 1)) {
                return 'list';
            }
            return 'object';
        }
        
        return 'string';
    }
}

