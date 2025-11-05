<?php

declare(strict_types=1);

namespace Kova\Kams\Core;

use Kova\Kams\Common\Database;
use Kova\Kams\Common\Logger;
use Kova\Kams\Common\Config;

/**
 * Abstract base class for all cron jobs.
 * Provides common functionality and enforces consistent patterns.
 */
abstract class AbstractCron
{
    protected Database $dbConn;
    protected array $config;
    protected Common $common;
    protected string $mod;
    protected string $ifaces;
    protected ?string $fileName = null;
    protected ?string $modEnabled = null;
    protected Logger $logger;

    public function __construct(Config|array $config, ?Container $container = null)
    {
        $container = $container ?? Container::getInstance();
        
        $this->dbConn = $container->get('database', 'kams');
        $this->config = is_object($config) ? $config->config : $config;
        $this->common = $container->get('common', is_object($config) ? $config : null);
        $this->logger = $container->get('logger');
        
        $this->initialize();
    }

    /**
     * Initialize module-specific properties.
     * Override in child classes.
     */
    protected function initialize(): void
    {
        // Override in child classes
    }

    /**
     * Check if this module is enabled.
     */
    public function modEnabled(): string
    {
        return $this->modEnabled ?? 'no';
    }

    /**
     * Start the cron job.
     * Must be implemented by child classes.
     */
    abstract public function startCron(): array;

    /**
     * Process cron results.
     * Must be implemented by child classes.
     */
    abstract public function processCron(): array;

    /**
     * Run alert checking logic.
     * Must be implemented by child classes.
     */
    abstract public function AlertCron($rawFile, $iface): array;

    /**
     * Generate report data.
     * Must be implemented by child classes.
     */
    abstract public function ReportCron(): array;

    /**
     * Record data from file.
     * Must be implemented by child classes.
     */
    abstract public function RecordCron($file, $iface = null): void;
}

