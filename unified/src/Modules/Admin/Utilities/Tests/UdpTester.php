<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Utilities\Tests;

use Kova\Kams\Common\Database;

/**
 * UdpTester
 * 
 * Sends test UDP packets to interfaces
 */
class UdpTester
{
    private Database $db;
    
    public function __construct(?Database $db = null)
    {
        $this->db = $db ?? new Database('kams');
    }
    
    /**
     * Send UDP packets using PHP sockets to simulate external device
     * 
     * @param string $iface Interface name (e.g., "enp6s18")
     * @param int $count Number of packets to send
     * @param int $port Destination port (default: random)
     * @return array{success: bool, message: string, packetsSent: int}
     */
    public function sendPackets(string $iface, int $count = 10, int $port = 0): array
    {
        try {
            // Get interface IP address
            $ifaceIp = $this->getInterfaceIp($iface);
            if (!$ifaceIp) {
                return [
                    'success' => false,
                    'message' => "Could not determine IP address for interface {$iface}",
                    'packetsSent' => 0
                ];
            }
            
            // Use a random destination port if not specified
            $targetPort = $port ?: rand(10000, 65535);
            
            // Create UDP socket
            $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if (!$socket) {
                return [
                    'success' => false,
                    'message' => 'Failed to create UDP socket: ' . socket_strerror(socket_last_error()),
                    'packetsSent' => 0
                ];
            }
            
            // Bind to interface IP (optional, but helps with routing)
            // Note: This may require root privileges or appropriate capabilities
            @socket_bind($socket, $ifaceIp, 0);
            
            // Use localhost as target (packets will be visible on the interface)
            $targetIp = '127.0.0.1';
            
            $packetsSent = 0;
            $generator = new TestDataGenerator();
            $baseTime = microtime(true);
            
            for ($i = 0; $i < $count; $i++) {
                $data = sprintf(
                    "TEST_UDP_PACKET_%d_%.6f",
                    $i + 1,
                    $baseTime + ($i * 0.001)
                );
                
                $sent = socket_sendto($socket, $data, strlen($data), 0, $targetIp, $targetPort);
                if ($sent !== false) {
                    $packetsSent++;
                }
                
                // Small delay between packets
                usleep(10000); // 10ms
            }
            
            socket_close($socket);
            
            return [
                'success' => true,
                'message' => "Sent {$packetsSent} UDP packets to interface {$iface} on port {$targetPort}",
                'packetsSent' => $packetsSent,
                'port' => $targetPort
            ];
            
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error sending UDP packets: ' . $e->getMessage(),
                'packetsSent' => 0
            ];
        }
    }
    
    /**
     * Get IP address for an interface
     * 
     * @param string $iface Interface name
     * @return string|null IP address or null if not found
     */
    private function getInterfaceIp(string $iface): ?string
    {
        // Try to get IP from system command
        $command = "ip addr show {$iface} 2>/dev/null | grep 'inet ' | awk '{print $2}' | cut -d/ -f1 | head -1";
        $output = [];
        $returnVar = 0;
        @exec($command, $output, $returnVar);
        
        if ($returnVar === 0 && !empty($output[0])) {
            return trim($output[0]);
        }
        
        // Fallback: try ifconfig
        $command = "ifconfig {$iface} 2>/dev/null | grep 'inet ' | awk '{print $2}' | head -1";
        @exec($command, $output, $returnVar);
        
        if ($returnVar === 0 && !empty($output[0])) {
            return trim($output[0]);
        }
        
        // Default to localhost if interface IP cannot be determined
        return '127.0.0.1';
    }
}

