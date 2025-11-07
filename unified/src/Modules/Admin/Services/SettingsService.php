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
            $originalValue = $data['value'];
            $data['value'] = SettingsValueNormalizer::normalizeForStorage(
                $data['value'],
                $data['type']
            );
            // Debug logging
            error_log("SettingsService::update - ID: {$id}, Type: {$data['type']}, Original: " . substr((string)$originalValue, 0, 200) . ", Normalized: " . substr($data['value'], 0, 200));
        }
        
        $qb = $this->db->createQueryBuilder();
        $qb->update('settings');
        
        $fieldsSet = 0;
        if (isset($data['name'])) {
            $qb->set('name', ':name')->setParameter('name', $data['name']);
            $fieldsSet++;
        }
        if (isset($data['value'])) {
            $qb->set('value', ':value')->setParameter('value', $data['value']);
            $fieldsSet++;
        }
        if (isset($data['type'])) {
            $qb->set('type', ':type')->setParameter('type', $data['type']);
            $fieldsSet++;
        }
        if (isset($data['description'])) {
            $qb->set('description', ':description')->setParameter('description', $data['description']);
            $fieldsSet++;
        }
        if (isset($data['group_name'])) {
            $qb->set('group_name', ':group_name')->setParameter('group_name', $data['group_name']);
            $fieldsSet++;
        }
        if (isset($data['sort_order'])) {
            $qb->set('sort_order', ':sort_order')->setParameter('sort_order', $data['sort_order']);
            $fieldsSet++;
        }
        
        if ($fieldsSet === 0) {
            error_log("SettingsService::update - No fields to update for ID: {$id}");
            return false;
        }
        
        $qb->where('id = :id')->setParameter('id', $id);
        
        // For UPDATE queries, use executeStatementBuilder (not executeQueryBuilder which is for SELECT)
        try {
            $affectedRows = $this->db->executeStatementBuilder($qb);
            error_log("SettingsService::update - ID: {$id}, Fields set: {$fieldsSet}, SQL: " . $qb->getSQL() . ", Params: " . json_encode($qb->getParameters()) . ", Affected rows: {$affectedRows}");
        } catch (\Throwable $e) {
            error_log("SettingsService::update - ERROR for ID: {$id}: " . $e->getMessage() . ", SQL: " . $qb->getSQL() . ", Params: " . json_encode($qb->getParameters()));
            throw $e;
        }
        
        // Invalidate config cache after update
        $this->invalidateConfigCache();
        
        return $affectedRows > 0;
    }

    public function create(array $data): int
    {
        // Validate required fields
        if (empty($data['name'])) {
            throw new \InvalidArgumentException('Setting name is required');
        }

        // Check for duplicate name
        $qb = $this->db->createQueryBuilder();
        $qb->select('id')
           ->from('settings')
           ->where('(setname = :name OR name = :name)')
           ->setParameter('name', $data['name'])
           ->setMaxResults(1);
        
        $existing = $this->db->executeQueryBuilder($qb);
        if (!empty($existing)) {
            throw new \InvalidArgumentException('Setting with this name already exists');
        }

        // Normalize value before storage if type is provided
        if (isset($data['value']) && isset($data['type'])) {
            $data['value'] = SettingsValueNormalizer::normalizeForStorage(
                $data['value'],
                $data['type']
            );
        }

        // Prepare data for insert
        $insertData = [];
        if (isset($data['name'])) {
            $insertData['name'] = $data['name'];
        }
        if (isset($data['value'])) {
            $insertData['value'] = $data['value'];
        }
        if (isset($data['type'])) {
            $insertData['type'] = $data['type'];
        }
        if (isset($data['description'])) {
            $insertData['description'] = $data['description'];
        }
        if (isset($data['group_name'])) {
            $insertData['group_name'] = $data['group_name'];
        }
        if (isset($data['sort_order'])) {
            $insertData['sort_order'] = $data['sort_order'];
        }

        $this->db->insert('settings', $insertData);
        $id = (int)$this->db->lastInsertId();
        
        // Invalidate config cache after create
        $this->invalidateConfigCache();
        
        return $id;
    }

    public function delete(int $id): bool
    {
        $qb = $this->db->createQueryBuilder();
        $qb->delete('settings')
           ->where('id = :id')
           ->setParameter('id', $id);
        
        // For DELETE queries, use executeStatementBuilder (not executeQueryBuilder which is for SELECT)
        $affectedRows = $this->db->executeStatementBuilder($qb);
        
        // Invalidate config cache after delete
        $this->invalidateConfigCache();
        
        return $affectedRows > 0;
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

