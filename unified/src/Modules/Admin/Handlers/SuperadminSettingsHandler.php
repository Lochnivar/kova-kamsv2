<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Handlers;

use Kova\Kams\Unified\Modules\Admin\Services\SettingsService;
use Kova\Kams\Unified\Modules\Admin\Validation\TypeValidator;
use Kova\Kams\Unified\Modules\Admin\Schema\SettingsSchema;
use Kova\Kams\Common\Logger;
use Kova\Kams\Common\SettingsValueNormalizer;

/**
 * SuperadminSettingsHandler
 * 
 * Full CRUD handler for superadmin with ability to edit all fields
 * including type, group_name, and sort_order.
 */
class SuperadminSettingsHandler
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

    public function handlePost(array $input): array
    {
        try {
            $name = $input['name'] ?? null;
            $value = $input['value'] ?? null;
            $type = $input['type'] ?? 'string';
            $description = $input['description'] ?? null;
            $group_name = $input['group_name'] ?? 'default';
            $sort_order = $input['sort_order'] ?? 100;

            // Validate required fields
            if (!$name) {
                return [
                    'success' => false,
                    'error' => 'Setting name is required'
                ];
            }

            // Use schema for validation if available
            $schema = null;
            if ($this->schema->hasSchema($name)) {
                $schema = $this->schema->getSchema($name);
                
                // Use schema type if not provided
                $type = $type ?? $schema['type'] ?? 'string';
                
                // Apply default value if value is null and default exists
                if ($value === null && isset($schema['default'])) {
                    $value = $schema['default'];
                }
            }

            // Validate value type
            if ($value !== null && $value !== '') {
                if (!$this->validator->validate($value, $type)) {
                    return [
                        'success' => false,
                        'error' => 'Value does not match expected type: ' . $type
                    ];
                }
            }

            // Create the setting
            $id = $this->settingsService->create([
                'name' => $name,
                'value' => $value,
                'type' => $type,
                'description' => $description ?? $schema['description'] ?? null,
                'group_name' => $group_name,
                'sort_order' => (int)$sort_order
            ]);

            return [
                'success' => true,
                'id' => $id
            ];
        } catch (\Exception $e) {
            $this->logger->error('Error creating setting', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
            $group_name = $input['group_name'] ?? null;
            $sort_order = $input['sort_order'] ?? null;

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
                
                if (!$this->validator->validate($value, $effectiveType)) {
                    return [
                        'success' => false,
                        'error' => 'Value does not match expected type: ' . $type
                    ];
                }
            }

            // Update the setting (all fields allowed for superadmin)
            $updateData = [
                'name' => $name,
                'value' => $value,
                'type' => $type,
                'description' => $description
            ];

            // Only update group_name and sort_order if provided
            if ($group_name !== null) {
                $updateData['group_name'] = $group_name;
            }
            if ($sort_order !== null) {
                $updateData['sort_order'] = (int)$sort_order;
            }

            $this->settingsService->update($id, $updateData);

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

    public function handleDelete(int $id): array
    {
        try {
            // Verify setting exists
            $setting = $this->settingsService->getById($id);
            if (!$setting) {
                return [
                    'success' => false,
                    'error' => 'Setting not found'
                ];
            }

            $this->settingsService->delete($id);

            return ['success' => true];
        } catch (\Exception $e) {
            $this->logger->error('Error deleting setting', [
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
}

