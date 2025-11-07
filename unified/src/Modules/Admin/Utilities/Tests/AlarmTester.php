<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Utilities\Tests;

use Kova\Kams\Kcm\Modules\Common\AlarmHandler;
use Kova\Kams\Core\Common;

/**
 * AlarmTester
 * 
 * Tests alarm raising and clearing
 */
class AlarmTester
{
    private AlarmHandler $alarmHandler;
    private Common $common;
    
    public function __construct(?AlarmHandler $alarmHandler = null, ?Common $common = null)
    {
        $this->alarmHandler = $alarmHandler ?? new AlarmHandler();
        $this->common = $common ?? new Common((new \Kova\Kams\Common\Config())->config);
    }
    
    /**
     * Test alarm raising for a specific module/interface
     * 
     * @param string $module Module name (udp, serial, moto, zabbix)
     * @param string $iface Interface/channel identifier
     * @return array{success: bool, alarmId: int|null, message: string}
     */
    public function testAlarm(string $module, string $iface): array
    {
        try {
            $valArray = [];
            $valArray[1] = '';   // USER
            $valArray[2] = '[TEST] ' . $this->getAlarmMessage($module);  // Message
            $valArray[3] = 'Test Value';  // Value 1
            $valArray[4] = '';  // Value 2
            $valArray[5] = '';  // Value 3
            $valArray[6] = $iface; // Channel/Interface
            $valArray[7] = 'Test Duration';  // Duration
            
            $msgline = $this->common->buildMsgLine($valArray);
            $result = $this->alarmHandler->raiseAlarm($module, $iface, $msgline);
            
            // Handle both array format ['LAST_INSERT_ID()' => id] and direct ID
            $alarmId = is_array($result) ? ($result['LAST_INSERT_ID()'] ?? $result['id'] ?? null) : $result;
            
            return [
                'success' => true,
                'alarmId' => $alarmId,
                'message' => "Test alarm raised for {$module}/{$iface} (ID: {$alarmId})"
            ];
            
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'alarmId' => null,
                'message' => 'Error raising test alarm: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Test alarm clearing
     * 
     * @param int $alarmId Alarm ID to clear
     * @return array{success: bool, message: string}
     */
    public function testClearAlarm(int $alarmId): array
    {
        try {
            // Get alarm details from database
            $db = new \Kova\Kams\Common\Database('kams');
            $qb = $db->createQueryBuilder();
            $qb->select('*')
               ->from('kamsAlarms')
               ->where('id = :id')
               ->setParameter('id', $alarmId);
            
            $result = $db->executeQueryBuilder($qb);
            
            if (empty($result)) {
                return [
                    'success' => false,
                    'message' => "Alarm ID {$alarmId} not found"
                ];
            }
            
            $alarm = $result[0];
            
            $valArray = [];
            $valArray[1] = '';
            $valArray[2] = '[TEST CLEAR] ' . ($alarm['msgline'] ?? '');
            
            $msgline = $this->common->buildMsgLine($valArray);
            $this->alarmHandler->clearAlarm($alarm['module'], $alarmId, $alarm['iface'], $msgline);
            
            return [
                'success' => true,
                'message' => "Test alarm {$alarmId} cleared successfully"
            ];
            
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error clearing test alarm: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get alarm message for module
     */
    private function getAlarmMessage(string $module): string
    {
        $messages = [
            'udp' => 'UDP Fell Below Threshold on the SPAN Port',
            'serial' => 'Serial Data is Below Threshold',
            'moto' => 'Motorola Channel Timeout',
            'zabbix' => 'Server Did Not Check In'
        ];
        
        return $messages[$module] ?? 'Test Alarm';
    }
}

