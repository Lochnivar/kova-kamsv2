<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Utilities;

use Kova\Kams\Core\Common;
use Kova\Kams\Kcm\Modules\Common\AlarmHandler;
use Kova\Kams\Common\Config;

/**
 * FireDrill
 * 
 * Orchestrates fire drill testing of all alarm functions
 */
class FireDrill
{
    private Common $common;
    private AlarmHandler $alarmHandler;
    private Tests\AlarmTester $alarmTester;
    
    public function __construct(?Common $common = null, ?AlarmHandler $alarmHandler = null)
    {
        $config = new Config();
        $this->common = $common ?? new Common($config->config);
        $this->alarmHandler = $alarmHandler ?? new AlarmHandler();
        $this->alarmTester = new Tests\AlarmTester($this->alarmHandler, $this->common);
    }
    
    /**
     * Execute fire drill - test all alarm functions
     * 
     * @param bool $autoClear Auto-clear alarms after test (default: true)
     * @return array{success: bool, results: array, alarmsRaised: array, autoCleared: bool}
     */
    public function executeFireDrill(bool $autoClear = true): array
    {
        $results = [];
        $alarmsRaised = [];
        
        try {
            // 1. Test UDP alarms for all configured interfaces
            $udpInterfaces = $this->common->getIfaces('udp');
            foreach ($udpInterfaces as $iface) {
                $ifaceName = $iface['interface'] ?? $iface['name'] ?? '';
                if ($ifaceName) {
                    $result = $this->testUdpAlarm($ifaceName);
                    $results['udp'][] = $result;
                    if ($result['alarmId']) {
                        $alarmsRaised[] = $result['alarmId'];
                    }
                }
            }
            
            // 2. Test Serial alarms for all configured devices
            $serialInterfaces = $this->common->getIfaces('serial');
            foreach ($serialInterfaces as $iface) {
                $ifaceName = $iface['interface'] ?? $iface['name'] ?? '';
                if ($ifaceName) {
                    $result = $this->testSerialAlarm($ifaceName);
                    $results['serial'][] = $result;
                    if ($result['alarmId']) {
                        $alarmsRaised[] = $result['alarmId'];
                    }
                }
            }
            
            // 3. Test Motorola alarms (if channels exist)
            $result = $this->testMotorolaAlarms();
            if (!empty($result)) {
                $results['moto'] = $result;
                foreach ($result as $r) {
                    if ($r['alarmId']) {
                        $alarmsRaised[] = $r['alarmId'];
                    }
                }
            }
            
            // 4. Auto-clear if requested
            if ($autoClear && !empty($alarmsRaised)) {
                foreach ($alarmsRaised as $alarmId) {
                    $this->alarmTester->testClearAlarm($alarmId);
                }
            }
            
            return [
                'success' => true,
                'results' => $results,
                'alarmsRaised' => $alarmsRaised,
                'autoCleared' => $autoClear,
                'message' => sprintf(
                    'Fire drill completed: %d alarms raised%s',
                    count($alarmsRaised),
                    $autoClear ? ' and cleared' : ''
                )
            ];
            
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'results' => $results,
                'alarmsRaised' => $alarmsRaised,
                'autoCleared' => false,
                'message' => 'Fire drill error: ' . $e->getMessage()
            ];
        }
    }
    
    private function testUdpAlarm(string $iface): array
    {
        return $this->alarmTester->testAlarm('udp', $iface);
    }
    
    private function testSerialAlarm(string $iface): array
    {
        return $this->alarmTester->testAlarm('serial', $iface);
    }
    
    private function testMotorolaAlarms(): array
    {
        $results = [];
        
        try {
            $db = new \Kova\Kams\Common\Database('kams');
            $qb = $db->createQueryBuilder();
            $qb->select('channel_id')
               ->from('moto_channel_data')
               ->where('timeout_number <> :zero')
               ->setParameter('zero', 0)
               ->setMaxResults(5); // Limit to 5 channels for fire drill
            
            $channels = $db->executeQueryBuilder($qb);
            
            foreach ($channels as $channel) {
                $channelId = $channel['channel_id'] ?? '';
                if ($channelId) {
                    $results[] = $this->alarmTester->testAlarm('moto', (string)$channelId);
                }
            }
        } catch (\Throwable $e) {
            // Silently continue if Motorola channels don't exist
        }
        
        return $results;
    }
}

