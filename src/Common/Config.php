<?php

namespace Kova\Kams\Common;

use Kova\Kams\Bones\DbAdapter;

/**
 * Shared configuration loader backed by the `settings` table.
 */
final class Config
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

    public function refresh(): void
    {
        $this->provider->reload();
        $this->config = $this->provider->all();
    }
}
