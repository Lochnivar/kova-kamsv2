<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Auth;

use Kova\Kams\Common\Database;

class Authenticator
{
    private const DEFAULT_PASSWORD = 'kovaADMIN';

    public function login(string $password): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Trim password to handle whitespace issues
        $password = trim($password);
        
        $expectedPassword = $this->getPassword();
        
        // Use strict comparison
        if ($password === $expectedPassword) {
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_authenticated_at'] = time();
            return ['success' => true];
        }

        // Return more detailed error for debugging (remove in production)
        return [
            'success' => false, 
            'error' => 'Invalid password',
            'debug' => [
                'expected_length' => strlen($expectedPassword),
                'provided_length' => strlen($password),
                'match' => $password === $expectedPassword
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

    private function getPassword(): string
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
                return $setting['setvalue'] ?? $setting['value'] ?? self::DEFAULT_PASSWORD;
            }
            
            return self::DEFAULT_PASSWORD;
        } catch (\Exception $e) {
            return self::DEFAULT_PASSWORD;
        }
    }
}

