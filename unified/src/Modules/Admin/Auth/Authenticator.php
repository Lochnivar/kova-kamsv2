<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Auth;

use Kova\Kams\Common\Database;

class Authenticator
{
    private const DEFAULT_ADMIN_PASSWORD = 'kovaADMIN';
    private const DEFAULT_SUPERADMIN_PASSWORD = 'superAdmin';

    public function login(string $password): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Trim password to handle whitespace issues
        $password = trim($password);
        
        $adminPassword = $this->getAdminPassword();
        $superadminPassword = $this->getSuperadminPassword();
        
        // Check superadmin password first (more privileged)
        if ($password === $superadminPassword) {
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_level'] = 'superadmin';
            $_SESSION['admin_authenticated_at'] = time();
            return ['success' => true, 'level' => 'superadmin'];
        }
        
        // Check admin password
        if ($password === $adminPassword) {
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_level'] = 'admin';
            $_SESSION['admin_authenticated_at'] = time();
            return ['success' => true, 'level' => 'admin'];
        }

        // Return more detailed error for debugging (remove in production)
        return [
            'success' => false, 
            'error' => 'Invalid password',
            'debug' => [
                'admin_length' => strlen($adminPassword),
                'superadmin_length' => strlen($superadminPassword),
                'provided_length' => strlen($password)
            ]
        ];
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['admin_authenticated'] = false;
        unset($_SESSION['admin_authenticated'], $_SESSION['admin_authenticated_at']);
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public function check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
    }

    public function getLevel(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['admin_level'] ?? null;
    }

    public function isSuperadmin(): bool
    {
        return $this->getLevel() === 'superadmin';
    }

    private function getAdminPassword(): string
    {
        try {
            $db = new Database('kams');
            $qb = $db->createQueryBuilder();
            $qb->select('*')
               ->from('settings')
               ->where('(setname = :name OR name = :name)')
               ->setParameter('name', 'adminPassword')
               ->setMaxResults(1);
            
            $result = $db->executeQueryBuilder($qb);
            $setting = $result[0] ?? null;
            
            if ($setting) {
                // Handle both old (setvalue) and new (value) column names
                return $setting['setvalue'] ?? $setting['value'] ?? self::DEFAULT_ADMIN_PASSWORD;
            }
            
            return self::DEFAULT_ADMIN_PASSWORD;
        } catch (\Exception $e) {
            return self::DEFAULT_ADMIN_PASSWORD;
        }
    }

    private function getSuperadminPassword(): string
    {
        try {
            $db = new Database('kams');
            $qb = $db->createQueryBuilder();
            $qb->select('*')
               ->from('settings')
               ->where('(setname = :name OR name = :name)')
               ->setParameter('name', 'superadminPassword')
               ->setMaxResults(1);
            
            $result = $db->executeQueryBuilder($qb);
            $setting = $result[0] ?? null;
            
            if ($setting) {
                // Handle both old (setvalue) and new (value) column names
                return $setting['setvalue'] ?? $setting['value'] ?? self::DEFAULT_SUPERADMIN_PASSWORD;
            }
            
            return self::DEFAULT_SUPERADMIN_PASSWORD;
        } catch (\Exception $e) {
            return self::DEFAULT_SUPERADMIN_PASSWORD;
        }
    }
}

