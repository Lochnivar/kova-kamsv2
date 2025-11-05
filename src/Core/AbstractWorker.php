<?php

declare(strict_types=1);

namespace Kova\Kams\Core;

use Kova\Kams\Common\Database;
use Kova\Kams\Common\Logger;
use Kova\Kams\Common\Config;

/**
 * Abstract base class for all data worker classes.
 * Provides common data fetching and processing patterns.
 */
abstract class AbstractWorker
{
    protected array $configs;
    protected Database $dbConn;
    protected string $mod;
    protected Common $common;
    protected Logger $logger;

    public function __construct(array $configs, ?Container $container = null)
    {
        $container = $container ?? Container::getInstance();
        
        $this->configs = $configs;
        $this->dbConn = $container->get('database', 'kams');
        $this->common = $container->get('common');
        $this->logger = $container->get('logger');
        
        $this->initialize();
    }

    /**
     * Initialize worker-specific properties.
     * Override in child classes.
     */
    protected function initialize(): void
    {
        // Override in child classes
    }

    /**
     * Get average data for the module.
     * Must be implemented by child classes.
     */
    abstract public function getAvgs(): array;

    /**
     * Get last packet timestamp for an interface.
     */
    public function getLastPacketStamp(string $iface, string $table, string $ifaceColumn = 'iface'): ?string
    {
        $qb = $this->dbConn->createQueryBuilder();
        $qb->select('MAX(epoch) AS max_epoch')
           ->from($table)
           ->where($ifaceColumn . ' = :iface')
           ->andWhere('size > 0')
           ->setParameter('iface', $iface);
        $result = $this->dbConn->executeQueryBuilder($qb);

        $lasttime = $result[0] ?? null;
        if ($lasttime === null || ($lasttime['max_epoch'] ?? null) === null) {
            return null;
        }

        $dt = new \DateTime();
        $dt->setTimestamp((int)$lasttime['max_epoch']);

        return $dt->format("Y-m-d H:i:s");
    }
}

