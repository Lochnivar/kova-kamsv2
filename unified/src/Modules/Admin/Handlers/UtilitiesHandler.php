<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Handlers;

use Kova\Kams\Unified\Modules\Admin\Utilities\UtilitiesTester;
use Kova\Kams\Unified\Modules\Admin\Utilities\FireDrill;
use Kova\Kams\Common\Logger;

/**
 * UtilitiesHandler
 * 
 * Handles utilities API requests
 */
class UtilitiesHandler
{
    private UtilitiesTester $tester;
    private FireDrill $fireDrill;
    private ?Logger $logger;

    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger;
        $this->tester = new UtilitiesTester();
        $this->fireDrill = new FireDrill();
    }

    public function handleRequest(string $action, array $input): array
    {
        try {
            switch ($action) {
                case 'sendUdpTest':
                    return $this->handleSendUdpTest($input);
                case 'sendSerialTest':
                    return $this->handleSendSerialTest($input);
                case 'testAlarm':
                    return $this->handleTestAlarm($input);
                case 'clearAlarm':
                    return $this->handleClearAlarm($input);
                case 'fireDrill':
                    return $this->handleFireDrill($input);
                default:
                    return ['success' => false, 'error' => 'Unknown action: ' . $action];
            }
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('UtilitiesHandler error', [
                    'action' => $action,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function handleSendUdpTest(array $input): array
    {
        $iface = $input['iface'] ?? '';
        $count = (int)($input['count'] ?? 10);
        $port = (int)($input['port'] ?? 0);
        
        if (empty($iface)) {
            return ['success' => false, 'error' => 'Interface required'];
        }
        
        if ($count < 1 || $count > 1000) {
            return ['success' => false, 'error' => 'Packet count must be between 1 and 1000'];
        }
        
        return $this->tester->sendTestUdpPackets($iface, $count, $port);
    }

    private function handleSendSerialTest(array $input): array
    {
        $device = $input['device'] ?? '';
        $lines = (int)($input['lines'] ?? 10);
        $data = $input['data'] ?? null;
        
        if (empty($device)) {
            return ['success' => false, 'error' => 'Device required'];
        }
        
        if ($lines < 1 || $lines > 100) {
            return ['success' => false, 'error' => 'Line count must be between 1 and 100'];
        }
        
        return $this->tester->sendTestSerialData($device, $data, $lines);
    }

    private function handleTestAlarm(array $input): array
    {
        $module = $input['module'] ?? '';
        $iface = $input['iface'] ?? '';
        
        if (empty($module) || empty($iface)) {
            return ['success' => false, 'error' => 'Module and interface required'];
        }
        
        $allowedModules = ['udp', 'serial', 'moto', 'zabbix'];
        if (!in_array($module, $allowedModules)) {
            return ['success' => false, 'error' => 'Invalid module. Must be one of: ' . implode(', ', $allowedModules)];
        }
        
        return $this->tester->testAlarm($module, $iface);
    }

    private function handleClearAlarm(array $input): array
    {
        $alarmId = (int)($input['alarmId'] ?? 0);
        
        if ($alarmId < 1) {
            return ['success' => false, 'error' => 'Valid alarm ID required'];
        }
        
        return $this->tester->testClearAlarm($alarmId);
    }

    private function handleFireDrill(array $input): array
    {
        $autoClear = isset($input['autoClear']) && $input['autoClear'] === '1';
        
        return $this->fireDrill->executeFireDrill($autoClear);
    }
}

