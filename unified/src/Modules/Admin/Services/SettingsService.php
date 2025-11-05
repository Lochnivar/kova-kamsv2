<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Services;

use Kova\Kams\Common\Database;
use Kova\Kams\Common\SettingsValueNormalizer;
use Kova\Kams\Common\Config;

class SettingsService
{
    private Database $db;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? new Database('kams');
    }

    public function getAll(): array
    {
        $qb = $this->db->createQueryBuilder();
        $qb->select('*')
           ->from('settings')
           ->orderBy('COALESCE(group_name, "default")', 'ASC')
           ->addOrderBy('COALESCE(sort_order, 100)', 'ASC')
           ->addOrderBy('name', 'ASC');
        
        $result = $this->db->executeQueryBuilder($qb);
        
        // Normalize column names (handle both old and new schemas)
        return array_map(function($row) {
            if (isset($row['setname'])) {
                return [
                    'id' => $row['id'] ?? null,
                    'name' => $row['setname'] ?? $row['name'] ?? '',
                    'value' => $row['setvalue'] ?? $row['value'] ?? '',
                    'type' => $row['type'] ?? 'string',
                    'description' => $row['sethint'] ?? $row['description'] ?? '',
                    'group_name' => $row['group_name'] ?? 'default',
                    'sort_order' => $row['sort_order'] ?? 100
                ];
            }
            return $row;
        }, $result);
    }

    public function getById(int $id): ?array
    {
        $qb = $this->db->createQueryBuilder();
        $qb->select('*')
           ->from('settings')
           ->where('id = :id')
           ->setParameter('id', $id)
           ->setMaxResults(1);
        
        $result = $this->db->executeQueryBuilder($qb);
        
        if (empty($result)) {
            return null;
        }
        
        $row = $result[0];
        
        // Normalize column names
        if (isset($row['setname'])) {
            return [
                'id' => $row['id'] ?? null,
                'name' => $row['setname'] ?? $row['name'] ?? '',
                'value' => $row['setvalue'] ?? $row['value'] ?? '',
                'type' => $row['type'] ?? 'string',
                'description' => $row['sethint'] ?? $row['description'] ?? '',
                'group_name' => $row['group_name'] ?? 'default',
                'sort_order' => $row['sort_order'] ?? 100
            ];
        }
        
        return $row;
    }

    public function update(int $id, array $data): bool
    {
        // Normalize value before storage if type is provided
        if (isset($data['value']) && isset($data['type'])) {
            $data['value'] = SettingsValueNormalizer::normalizeForStorage(
                $data['value'],
                $data['type']
            );
        }
        
        $qb = $this->db->createQueryBuilder();
        $qb->update('settings');
        
        if (isset($data['name'])) {
            $qb->set('name', ':name')->setParameter('name', $data['name']);
        }
        if (isset($data['value'])) {
            $qb->set('value', ':value')->setParameter('value', $data['value']);
        }
        if (isset($data['type'])) {
            $qb->set('type', ':type')->setParameter('type', $data['type']);
        }
        if (isset($data['description'])) {
            $qb->set('description', ':description')->setParameter('description', $data['description']);
        }
        
        // Note: updated_at column will be added in Phase 2 (database migration)
        // For now, we skip it to maintain backward compatibility
        
        $qb->where('id = :id')->setParameter('id', $id);
        
        $this->db->executeQueryBuilder($qb);
        
        // Invalidate config cache after update
        $this->invalidateConfigCache();
        
        return true;
    }
    
    /**
     * Invalidate all Config caches.
     * 
     * This ensures that config changes are immediately reflected.
     * Note: This is a simple approach. For production, consider using
     * a shared cache system or cache tags.
     */
    private function invalidateConfigCache(): void
    {
        try {
            // Clear cache by creating a new Config instance and clearing it
            // This affects the current process only. For multi-process setups,
            // consider using a shared cache or file-based cache invalidation.
            $config = new Config('kams');
            $config->clearCache();
        } catch (\Exception $e) {
            // Silently fail - cache invalidation is best-effort
            error_log("SettingsService: Failed to invalidate cache: " . $e->getMessage());
        }
    }
}

