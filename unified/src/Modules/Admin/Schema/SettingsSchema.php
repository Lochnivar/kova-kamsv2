<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Schema;

// Only use kova_path if it exists (may not be loaded yet)

/**
 * SettingsSchema
 * 
 * Defines schema for all settings with validation rules, defaults, and metadata.
 * Uses hybrid approach: defaults in code, can be overridden by JSON file.
 */
class SettingsSchema
{
    private array $schema;

    public function __construct(?string $jsonPath = null)
    {
        // Load defaults from code
        $this->schema = $this->getDefaultSchema();
        
        // Merge with JSON file if exists (silently fail if file doesn't exist)
        try {
            if ($jsonPath === null) {
                try {
                    $jsonPath = $this->getDefaultJsonPath();
                } catch (\Throwable $e) {
                    // If path resolution fails, just skip JSON loading
                    return;
                }
            }
            
            if ($jsonPath && file_exists($jsonPath) && is_readable($jsonPath)) {
                $jsonContent = @file_get_contents($jsonPath);
                if ($jsonContent !== false && $jsonContent !== '') {
                    $json = @json_decode($jsonContent, true);
                    if (is_array($json) && json_last_error() === JSON_ERROR_NONE) {
                        $this->schema = array_merge($this->schema, $json);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore JSON loading errors - use defaults only
            // Don't throw - just use the defaults
        }
    }

    /**
     * Get default schema definitions
     */
    private function getDefaultSchema(): array
    {
        return [
            // Motorola Settings
            'MotorolaPage' => [
                'type' => 'bool',
                'group' => 'Motorola',
                'required' => false,
                'default' => '0',
                'validation' => ['in' => ['0', '1', 'yes', 'no']],
                'description' => 'Enable Motorola monitoring page'
            ],
            'MotorolaInterfaceName' => [
                'type' => 'object',
                'group' => 'Motorola',
                'required' => false,
                'default' => '[]',
                'validation' => [
                    'schema' => [
                        'interface' => 'string|required',
                        'label' => 'string|required',
                        'threshold' => 'int|min:1|max:100000'
                    ],
                    'array' => true
                ],
                'description' => 'Motorola interface configuration'
            ],
            
            // Serial Settings
            'SerialMonitorPage' => [
                'type' => 'bool',
                'group' => 'Serial',
                'required' => false,
                'default' => '0',
                'validation' => ['in' => ['0', '1', 'yes', 'no']],
                'description' => 'Enable Serial monitoring page'
            ],
            'SerialInterfaceName' => [
                'type' => 'object',
                'group' => 'Serial',
                'required' => false,
                'default' => '[]',
                'validation' => [
                    'schema' => [
                        'interface' => 'string|required',
                        'label' => 'string|required',
                        'threshold' => 'int|min:1|max:100000'
                    ],
                    'array' => true
                ],
                'description' => 'Serial interface configuration'
            ],
            'SerialSiteHeader' => [
                'type' => 'string',
                'group' => 'Serial',
                'required' => false,
                'default' => '',
                'validation' => ['max_length' => 50],
                'description' => 'Serial site header text'
            ],
            'SerialTriggerLimit' => [
                'type' => 'int',
                'group' => 'Serial',
                'required' => false,
                'default' => '500',
                'validation' => ['min' => 1, 'max' => 100000],
                'description' => 'Serial trigger limit threshold'
            ],
            'addSerialUsersV15' => [
                'type' => 'bool',
                'group' => 'Serial',
                'required' => false,
                'default' => '0',
                'validation' => ['in' => ['0', '1', 'yes', 'no']],
                'description' => 'Add serial users V15 feature'
            ],
            'VerintBKViperID' => [
                'type' => 'string',
                'group' => 'Serial',
                'required' => false,
                'default' => '',
                'validation' => ['max_length' => 50],
                'description' => 'Verint BK Viper ID'
            ],
            'VerintIP' => [
                'type' => 'string',
                'group' => 'Serial',
                'required' => false,
                'default' => '',
                'validation' => ['format' => 'ip'],
                'description' => 'Verint IP address'
            ],
            
            // UDP Settings
            'UDPMonitorPage' => [
                'type' => 'bool',
                'group' => 'UDP',
                'required' => false,
                'default' => '0',
                'validation' => ['in' => ['0', '1', 'yes', 'no']],
                'description' => 'Enable UDP monitoring page'
            ],
            'UDPInterfaceName' => [
                'type' => 'object',
                'group' => 'UDP',
                'required' => false,
                'default' => '{}',
                'validation' => [
                    'schema' => [
                        'interface' => 'string|required',
                        'label' => 'string|required',
                        'threshold' => 'int|min:1|max:100000'
                    ]
                ],
                'description' => 'UDP interface configuration'
            ],
            'netmonSiteHeader' => [
                'type' => 'string',
                'group' => 'UDP',
                'required' => false,
                'default' => '',
                'validation' => ['max_length' => 50],
                'description' => 'Network monitor site header'
            ],
            'udpTriggerLimit' => [
                'type' => 'int',
                'group' => 'UDP',
                'required' => false,
                'default' => '250',
                'validation' => ['min' => 1, 'max' => 100000],
                'description' => 'UDP trigger limit threshold'
            ],
            
            // Site Settings
            'SiteName' => [
                'type' => 'string',
                'group' => 'Site',
                'required' => true,
                'default' => 'Kova System',
                'validation' => ['min_length' => 1, 'max_length' => 100],
                'description' => 'Site name'
            ],
            'TimeZone' => [
                'type' => 'string',
                'group' => 'Site',
                'required' => false,
                'default' => 'America/New_York',
                'validation' => ['format' => 'timezone'],
                'description' => 'Timezone (e.g., America/New_York)'
            ],
            'servers' => [
                'type' => 'list',
                'group' => 'Site',
                'required' => false,
                'default' => '[]',
                'validation' => ['items' => 'string'],
                'description' => 'List of server names'
            ],
            'nightStartHour' => [
                'type' => 'int',
                'group' => 'Site',
                'required' => false,
                'default' => '23',
                'validation' => ['min' => 0, 'max' => 23],
                'description' => 'Night start hour (0-23)'
            ],
            'nightEndHour' => [
                'type' => 'int',
                'group' => 'Site',
                'required' => false,
                'default' => '6',
                'validation' => ['min' => 0, 'max' => 23],
                'description' => 'Night end hour (0-23)'
            ],
            'sshName' => [
                'type' => 'string',
                'group' => 'Site',
                'required' => false,
                'default' => '',
                'validation' => ['max_length' => 100],
                'description' => 'SSH name/hostname'
            ],
            
            // SiteInfo Settings
            'channelSiteHeader' => [
                'type' => 'string',
                'group' => 'SiteInfo',
                'required' => false,
                'default' => '',
                'validation' => ['max_length' => 50],
                'description' => 'Channel site header'
            ],
            
            // Verint Settings
            'VerintIPport' => [
                'type' => 'object',
                'group' => 'Verint',
                'required' => false,
                'default' => '{"host":"","port":0}',
                'validation' => [
                    'schema' => [
                        'host' => 'string|required',
                        'port' => 'int|required|min:1|max:65535'
                    ]
                ],
                'description' => 'Verint IP and port configuration'
            ],
            'VerintOrgID' => [
                'type' => 'string',
                'group' => 'Verint',
                'required' => false,
                'default' => '',
                'validation' => ['max_length' => 50],
                'description' => 'Verint Organization ID'
            ],
            'VerintViperID' => [
                'type' => 'string',
                'group' => 'Verint',
                'required' => false,
                'default' => '',
                'validation' => ['max_length' => 50],
                'description' => 'Verint Viper ID'
            ],
            
            // Zabbix Settings
            'ZabbixPage' => [
                'type' => 'bool',
                'group' => 'Zabbix',
                'required' => false,
                'default' => '0',
                'validation' => ['in' => ['0', '1', 'yes', 'no']],
                'description' => 'Enable Zabbix monitoring page'
            ],
            
            // Email Settings
            'ClientEmail' => [
                'type' => 'string',
                'group' => 'Email',
                'required' => false,
                'default' => '',
                'validation' => ['format' => 'email'],
                'description' => 'Client email address'
            ],
            
            // Admin Settings
            'adminPassword' => [
                'type' => 'string',
                'group' => 'Admin',
                'required' => false,
                'default' => 'kovaADMIN',
                'validation' => ['min_length' => 6],
                'description' => 'Admin password (stored in settings table)',
                'hidden' => true // Don't display in admin UI
            ]
        ];
    }

    /**
     * Get default JSON schema path
     */
    private function getDefaultJsonPath(): string
    {
        // Try multiple path resolution methods
        $paths = [];
        
        // Use kova_path if available (may not be loaded if bootstrap hasn't run)
        if (function_exists('kova_path')) {
            try {
                $path = kova_path('config/settings-schema.json');
                if ($path) {
                    $paths[] = $path;
                }
            } catch (\Throwable $e) {
                // Ignore path resolution errors
            }
        }
        
        if (defined('KOVA_ROOT')) {
            $paths[] = KOVA_ROOT . '/config/settings-schema.json';
        }
        
        // Try relative to this file
        $paths[] = __DIR__ . '/../../../../config/settings-schema.json';
        
        // Try relative to project root (common pattern)
        $paths[] = dirname(__DIR__, 4) . '/config/settings-schema.json';
        
        // Return first path that exists, or the most likely one
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Return the most likely path even if it doesn't exist yet
        return $paths[count($paths) - 2] ?? __DIR__ . '/../../../../config/settings-schema.json';
    }

    /**
     * Get schema for a specific setting
     */
    public function getSchema(string $name): ?array
    {
        return $this->schema[$name] ?? null;
    }

    /**
     * Get all schemas
     */
    public function getAll(): array
    {
        return $this->schema;
    }

    /**
     * Check if setting exists in schema
     */
    public function hasSchema(string $name): bool
    {
        return isset($this->schema[$name]);
    }

    /**
     * Get default value for a setting
     */
    public function getDefault(string $name): ?string
    {
        return $this->schema[$name]['default'] ?? null;
    }

    /**
     * Get validation rules for a setting
     */
    public function getValidationRules(string $name): array
    {
        return $this->schema[$name]['validation'] ?? [];
    }

    /**
     * Get settings grouped by group name
     */
    public function getGrouped(): array
    {
        $grouped = [];
        foreach ($this->schema as $name => $schema) {
            $group = $schema['group'] ?? 'default';
            if (!isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][$name] = $schema;
        }
        return $grouped;
    }
}

