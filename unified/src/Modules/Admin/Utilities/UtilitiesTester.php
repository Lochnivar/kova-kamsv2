<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Utilities;

use Kova\Kams\Unified\Modules\Admin\Utilities\Tests\UdpTester;
use Kova\Kams\Unified\Modules\Admin\Utilities\Tests\SerialTester;
use Kova\Kams\Unified\Modules\Admin\Utilities\Tests\AlarmTester;

/**
 * UtilitiesTester
 * 
 * Main testing class for utilities module
 */
class UtilitiesTester
{
    private UdpTester $udpTester;
    private SerialTester $serialTester;
    private AlarmTester $alarmTester;
    
    public function __construct()
    {
        $this->udpTester = new UdpTester();
        $this->serialTester = new SerialTester();
        $this->alarmTester = new AlarmTester();
    }
    
    /**
     * Send test UDP packets to a configured interface
     * 
     * @param string $iface Interface name (e.g., "enp6s18")
     * @param int $packetCount Number of packets to send
     * @param int $port Destination port (default: random)
     * @return array{success: bool, message: string, packetsSent: int}
     */
    public function sendTestUdpPackets(string $iface, int $packetCount = 10, int $port = 0): array
    {
        return $this->udpTester->sendPackets($iface, $packetCount, $port);
    }
    
    /**
     * Send test data to a serial device
     * 
     * @param string $device Device path (e.g., "/dev/ttyS4")
     * @param string|null $data Test data to send
     * @param int $lines Number of lines to send
     * @return array{success: bool, message: string, linesSent: int}
     */
    public function sendTestSerialData(string $device, ?string $data = null, int $lines = 10): array
    {
        return $this->serialTester->writeToSerial($device, $data, $lines);
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
        return $this->alarmTester->testAlarm($module, $iface);
    }
    
    /**
     * Test alarm clearing
     * 
     * @param int $alarmId Alarm ID to clear
     * @return array{success: bool, message: string}
     */
    public function testClearAlarm(int $alarmId): array
    {
        return $this->alarmTester->testClearAlarm($alarmId);
    }
}



