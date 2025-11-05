<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Handlers;

use Kova\Kams\Unified\Modules\Admin\Auth\Authenticator;
use Kova\Kams\Common\Logger;

class AuthHandler
{
    private Authenticator $authenticator;
    private Logger $logger;

    public function __construct(?Authenticator $authenticator = null, ?Logger $logger = null)
    {
        $this->authenticator = $authenticator ?? new Authenticator();
        $this->logger = $logger ?? new Logger('admin.log');
    }

    public function handleRequest(string $method, string $action, array $input): void
    {
        header('Content-Type: application/json');

        try {
            if ($action === 'login') {
                $password = $input['password'] ?? '';
                
                if (empty($password)) {
                    http_response_code(401);
                    echo json_encode(['error' => 'Password is required']);
                    return;
                }
                
                $result = $this->authenticator->login($password);
                
                if ($result['success']) {
                    echo json_encode(['success' => true]);
                } else {
                    http_response_code(401);
                    // Include debug info in development
                    $errorResponse = ['error' => $result['error'] ?? 'Invalid password'];
                    if (isset($result['debug'])) {
                        $errorResponse['debug'] = $result['debug'];
                    }
                    echo json_encode($errorResponse);
                }
            } elseif ($action === 'logout') {
                $this->authenticator->logout();
                echo json_encode(['success' => true]);
            } elseif ($action === 'check') {
                $isAuthenticated = $this->authenticator->check();
                echo json_encode(['authenticated' => $isAuthenticated]);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
            }
        } catch (\Exception $e) {
            $this->logger->error('Auth handler error', [
                'action' => $action,
                'error' => $e->getMessage()
            ]);
            http_response_code(500);
            echo json_encode(['error' => 'Server error']);
        }
    }

    public function check(): bool
    {
        return $this->authenticator->check();
    }
}

