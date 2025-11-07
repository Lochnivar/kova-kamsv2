<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Handlers;

use Kova\Kams\Unified\Modules\Admin\Services\SettingsService;
use Kova\Kams\Unified\Modules\Admin\Validation\TypeValidator;
use Kova\Kams\Unified\Modules\Admin\Schema\SettingsSchema;
use Kova\Kams\Common\Logger;
use Kova\Kams\Common\SettingsValueNormalizer;

class SettingsHandler
{
    private SettingsService $settingsService;
    private TypeValidator $validator;
    private SettingsSchema $schema;
    private Logger $logger;

    public function __construct(?SettingsService $settingsService = null, ?TypeValidator $validator = null, ?SettingsSchema $schema = null, ?Logger $logger = null)
    {
        $this->settingsService = $settingsService ?? new SettingsService();
        $this->validator = $validator ?? new TypeValidator();
        $this->schema = $schema ?? new SettingsSchema();
        $this->logger = $logger ?? new Logger('admin.log');
    }

    public function handleGet(): array
    {
        try {
            $settings = $this->settingsService->getAll();
            return [
                'success' => true,
                'data' => $settings
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error getting settings', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function handlePut(int $id, array $input): array
    {
        try {
            $name = $input['name'] ?? null;
            $value = $input['value'] ?? null;
            $type = $input['type'] ?? null;
            $description = $input['description'] ?? null;
            
            error_log("SettingsHandler::handlePut - Raw input: ID={$id}, Name={$name}, Value type=" . gettype($value) . ", Value=" . substr(json_encode($value), 0, 300) . ", Type={$type}");

            // Get setting from database if name not provided
            if (!$name) {
                $setting = $this->settingsService->getById($id);
                if (!$setting) {
                    return [
                        'success' => false,
                        'error' => 'Setting not found'
                    ];
                }
                $name = $setting['name'] ?? null;
                // Use existing type if not provided
                $type = $type ?? $setting['type'] ?? null;
            }

            // Validate required fields
            if (!$name) {
                return [
                    'success' => false,
                    'error' => 'Setting name is required'
                ];
            }

            // Use schema for validation and type determination
            $schema = null;
            if ($this->schema->hasSchema($name)) {
                $schema = $this->schema->getSchema($name);
                
                // Use schema type if not provided
                $type = $type ?? $schema['type'] ?? 'string';
                
                // Apply default value if value is null and default exists
                if ($value === null && isset($schema['default'])) {
                    $value = $schema['default'];
                }
                
                // Validate required field
                if (($schema['required'] ?? false) && ($value === null || $value === '')) {
                    return [
                        'success' => false,
                        'error' => 'Value is required for setting: ' . $name
                    ];
                }
            } else {
                // No schema - use provided type or default to string
                $type = $type ?? 'string';
            }

            // Validate value type
            if ($value !== null && $value !== '') {
                // For object types with array validation, accept arrays as well
                $effectiveType = $type;
                if ($type === 'object' && $schema && isset($schema['validation']['array']) && $schema['validation']['array'] === true) {
                    // This is an object type that accepts arrays - validate as list instead
                    $effectiveType = 'list';
                }
                
                // Basic type validation
                if (!$this->validator->validate($value, $effectiveType)) {
                    return [
                        'success' => false,
                        'error' => 'Value does not match expected type: ' . $type
                    ];
                }
                
                // Additional schema validation for complex types
                if ($schema && in_array($type, ['object', 'list'])) {
                    $validationResult = $this->validateAgainstSchema($value, $schema);
                    if (!$validationResult['valid']) {
                        return [
                            'success' => false,
                            'error' => $validationResult['error'] ?? 'Validation failed'
                        ];
                    }
                }
            }

            // Update the setting (normalization happens in SettingsService::update())
            error_log("SettingsHandler::handlePut - About to call update, ID: {$id}, Name: {$name}, Value: " . substr(json_encode($value), 0, 200) . ", Type: {$type}");
            
            $result = $this->settingsService->update($id, [
                'name' => $name,
                'value' => $value,
                'type' => $type,
                'description' => $description ?? $schema['description'] ?? null
            ]);

            error_log("SettingsHandler::handlePut - Update result: " . ($result ? 'true' : 'false'));

            if (!$result) {
                return [
                    'success' => false,
                    'error' => 'Update failed - no rows affected'
                ];
            }

            return ['success' => true];
        } catch (\Exception $e) {
            $this->logger->error('Error updating setting', [
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate a value against schema validation rules.
     * 
     * @param mixed $value The value to validate
     * @param array $schema The schema definition
     * @return array ['valid' => bool, 'error' => string|null]
     */
    private function validateAgainstSchema($value, array $schema): array
    {
        $validation = $schema['validation'] ?? [];
        
        if (empty($validation)) {
            return ['valid' => true];
        }
        
        // Handle 'in' validation (enum-like)
        if (isset($validation['in'])) {
            $normalizedValue = is_string($value) ? strtolower(trim($value)) : $value;
            $allowedValues = array_map('strtolower', $validation['in']);
            if (!in_array($normalizedValue, $allowedValues, true)) {
                return [
                    'valid' => false,
                    'error' => 'Value must be one of: ' . implode(', ', $validation['in'])
                ];
            }
        }
        
        // Handle object/list schema validation
        if (isset($validation['schema']) && in_array($schema['type'] ?? '', ['object', 'list'])) {
            $decoded = is_string($value) ? json_decode($value, true) : $value;
            if (!is_array($decoded)) {
                return [
                    'valid' => false,
                    'error' => 'Invalid format - expected array'
                ];
            }
            
            // Check if this is an object type that accepts arrays
            $isArrayOfObjects = ($schema['type'] === 'object' && isset($validation['array']) && $validation['array'] === true);
            
            if ($isArrayOfObjects || $schema['type'] === 'list') {
                // Validate each item in the array against the schema
                foreach ($decoded as $item) {
                    if (!is_array($item)) {
                        return [
                            'valid' => false,
                            'error' => 'Array items must be objects'
                        ];
                    }
                    
                    // Validate object properties against schema
                    foreach ($validation['schema'] as $prop => $rules) {
                        if (strpos($rules, 'required') !== false && !isset($item[$prop])) {
                            return [
                                'valid' => false,
                                'error' => "Required property '{$prop}' is missing in array item"
                            ];
                        }
                    }
                }
            } else {
                // For single object validation
                // Validate object properties
                foreach ($validation['schema'] as $prop => $rules) {
                    // Simple validation - can be enhanced
                    if (strpos($rules, 'required') !== false && !isset($decoded[$prop])) {
                        return [
                            'valid' => false,
                            'error' => "Required property '{$prop}' is missing"
                        ];
                    }
                }
            }
        }
        
        return ['valid' => true];
    }
}

