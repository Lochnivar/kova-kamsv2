<?php

declare(strict_types=1);

namespace Kova\Kams\Core;

use Kova\Kams\Common\Config;
use Kova\Kams\Common\Database;
use Kova\Kams\Common\Logger;
use Kova\Kams\Bones\DbAdapter;
use Closure;
use InvalidArgumentException;

/**
 * Simple Dependency Injection Container for managing shared services.
 */
final class Container
{
    private array $services = [];
    private array $singletons = [];
    private array $factories = [];

    public function __construct()
    {
        $this->registerCoreServices();
    }

    /**
     * Register core services that are always needed.
     */
    private function registerCoreServices(): void
    {
        // Database adapter - singleton
        $this->singleton('dbAdapter', function () {
            return new DbAdapter();
        });

        // Database - singleton per dbKey
        $this->factory('database', function (string $dbKey = 'kams') {
            $adapter = $this->get('dbAdapter');
            return new Database($dbKey, $adapter, $this->get('logger'));
        });

        // Config - singleton
        $this->singleton('config', function () {
            return new Config();
        });

        // Logger - singleton
        $this->singleton('logger', function () {
            return new Logger('app.log');
        });

        // Common utility - factory (can have different configs)
        $this->factory('common', function (?Config $config = null) {
            $config = $config ?? $this->get('config');
            return new Common($config);
        });

        // Communicator - factory (can have different configs)
        $this->factory('communicator', function (?Config $config = null) {
            $config = $config ?? $this->get('config');
            return new Communicator($config);
        });
    }

    /**
     * Register a singleton service.
     */
    public function singleton(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
        $this->singletons[$id] = true;
    }

    /**
     * Register a factory service (creates new instance each time).
     */
    public function factory(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
        $this->singletons[$id] = false;
    }

    /**
     * Get a service instance.
     */
    public function get(string $id, ...$args)
    {
        // Check if already resolved singleton
        if (isset($this->services[$id])) {
            return $this->services[$id];
        }

        // Check if factory exists
        if (!isset($this->factories[$id])) {
            throw new InvalidArgumentException("Service '{$id}' not registered");
        }

        // Resolve service
        $service = $this->factories[$id]($this, ...$args);

        // Store if singleton
        if ($this->singletons[$id] ?? false) {
            $this->services[$id] = $service;
        }

        return $service;
    }

    /**
     * Check if a service is registered.
     */
    public function has(string $id): bool
    {
        return isset($this->factories[$id]);
    }

    /**
     * Get container instance (singleton pattern for container itself).
     */
    private static ?Container $instance = null;

    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

