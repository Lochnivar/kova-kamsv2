<?php

namespace Kova\Kams\Common;

use Kova\Kams\Bones\DbAdapter;

/**
 * Shared configuration loader backed by the `settings` table.
 */
class Config
{
    /** @var array<string, mixed> */
    public array $config = [];

    private DBALConfigProvider $provider;

    public function __construct(string $dbKey = 'kams', ?DbAdapter $adapter = null, string $table = 'settings')
    {
        $adapter = $adapter ?? new DbAdapter();
        $connection = $adapter->getConnection($dbKey);
        $this->provider = new DBALConfigProvider($connection, $table);
        $this->config = $this->provider->all();
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
    
    /**
     * Get a boolean config value.
     * 
     * @param string $key The setting key
     * @param bool $default Default value if not found
     * @return bool The boolean value
     */
    public function getBool(string $key, bool $default = false): bool
    {
        return $this->provider->getBool($key, $default);
    }
    
    /**
     * Get an integer config value.
     * 
     * @param string $key The setting key
     * @param int $default Default value if not found
     * @return int The integer value
     */
    public function getInt(string $key, int $default = 0): int
    {
        return $this->provider->getInt($key, $default);
    }
    
    /**
     * Get a string config value.
     * 
     * @param string $key The setting key
     * @param string $default Default value if not found
     * @return string The string value
     */
    public function getString(string $key, string $default = ''): string
    {
        return $this->provider->getString($key, $default);
    }
    
    /**
     * Get an object/array config value.
     * 
     * @param string $key The setting key
     * @param array $default Default value if not found
     * @return array The array/object value
     */
    public function getObject(string $key, array $default = []): array
    {
        return $this->provider->getObject($key, $default);
    }
    
    /**
     * Get a list/array config value.
     * 
     * @param string $key The setting key
     * @param array $default Default value if not found
     * @return array The list value
     */
    public function getList(string $key, array $default = []): array
    {
        return $this->provider->getList($key, $default);
    }

    public function refresh(): void
    {
        $this->provider->reload();
        $this->config = $this->provider->all();
    }
    
    /**
     * Clear the config cache (force reload on next access).
     */
    public function clearCache(): void
    {
        $this->provider->clearCache();
        $this->config = [];
    }
}
