<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Utilities;

/**
 * NetworkDetector
 * 
 * Detects active network interfaces from the system using ip/ifconfig commands
 */
class NetworkDetector
{
    /**
     * Detect active network interfaces from the system
     * 
     * @return array Array of detected network interfaces with their information
     */
    public function detectNetworkInterfaces(): array
    {
        $interfaces = [];
        
        // Method 1: Use 'ip link show' (preferred, modern)
        $ipOutput = [];
        $exitCode = 0;
        exec('ip -o link show 2>/dev/null', $ipOutput, $exitCode);
        
        if ($exitCode === 0 && !empty($ipOutput)) {
            foreach ($ipOutput as $line) {
                // Parse: "1: lo: <LOOPBACK,UP,LOWER_UP> mtu 65536"
                // or: "2: enp6s18: <BROADCAST,MULTICAST,UP,LOWER_UP> mtu 1500"
                if (preg_match('/^\d+:\s+([^:]+):\s+<([^>]+)>\s+/', $line, $matches)) {
                    $ifaceName = trim($matches[1]);
                    $flags = $matches[2];
                    
                    // Skip loopback and virtual interfaces (unless specifically needed)
                    if ($ifaceName === 'lo' || strpos($ifaceName, 'docker') === 0 || 
                        strpos($ifaceName, 'veth') === 0 || strpos($ifaceName, 'br-') === 0) {
                        continue;
                    }
                    
                    // Check if interface is UP
                    $isUp = strpos($flags, 'UP') !== false;
                    
                    // Get additional info
                    $info = $this->getInterfaceInfo($ifaceName);
                    
                    $interfaces[$ifaceName] = [
                        'interface' => $ifaceName,
                        'name' => $ifaceName,
                        'label' => $info['label'] ?? $ifaceName,
                        'type' => $info['type'] ?? 'ethernet',
                        'state' => $isUp ? 'up' : 'down',
                        'mac' => $info['mac'] ?? null,
                        'ip' => $info['ip'] ?? null
                    ];
                }
            }
        }
        
        // Method 2: Fallback to ifconfig if ip command failed
        if (empty($interfaces)) {
            $ifconfigOutput = [];
            exec('ifconfig -a 2>/dev/null | grep -E "^[a-z0-9]+"', $ifconfigOutput, $exitCode);
            
            if ($exitCode === 0 && !empty($ifconfigOutput)) {
                foreach ($ifconfigOutput as $line) {
                    // Parse: "enp6s18: flags=4163<UP,BROADCAST,RUNNING,MULTICAST>  mtu 1500"
                    if (preg_match('/^([a-z0-9]+):\s+flags=/', $line, $matches)) {
                        $ifaceName = trim($matches[1]);
                        
                        // Skip loopback
                        if ($ifaceName === 'lo') {
                            continue;
                        }
                        
                        $info = $this->getInterfaceInfo($ifaceName);
                        
                        if (!isset($interfaces[$ifaceName])) {
                            $interfaces[$ifaceName] = [
                                'interface' => $ifaceName,
                                'name' => $ifaceName,
                                'label' => $info['label'] ?? $ifaceName,
                                'type' => $info['type'] ?? 'ethernet',
                                'state' => 'unknown',
                                'mac' => $info['mac'] ?? null,
                                'ip' => $info['ip'] ?? null
                            ];
                        }
                    }
                }
            }
        }
        
        // Method 3: Scan /sys/class/net as final fallback
        if (empty($interfaces)) {
            $netDir = '/sys/class/net';
            if (is_dir($netDir)) {
                $dirs = scandir($netDir);
                foreach ($dirs as $dir) {
                    if ($dir === '.' || $dir === '..' || $dir === 'lo') {
                        continue;
                    }
                    
                    $ifacePath = $netDir . '/' . $dir;
                    if (is_dir($ifacePath)) {
                        $info = $this->getInterfaceInfo($dir);
                        
                        $interfaces[$dir] = [
                            'interface' => $dir,
                            'name' => $dir,
                            'label' => $info['label'] ?? $dir,
                            'type' => $info['type'] ?? 'ethernet',
                            'state' => 'unknown',
                            'mac' => $info['mac'] ?? null,
                            'ip' => $info['ip'] ?? null
                        ];
                    }
                }
            }
        }
        
        // Sort by interface name
        ksort($interfaces);
        
        $result = array_values($interfaces);
        
        // If no interfaces found, return sample data for demonstration
        if (empty($result)) {
            return [
                [
                    'interface' => 'eth0',
                    'name' => 'eth0',
                    'label' => 'eth0 (192.168.1.100) [a1b2]',
                    'type' => 'ethernet',
                    'state' => 'up',
                    'mac' => '00:1a:2b:3c:4d:a1',
                    'ip' => '192.168.1.100',
                    'simulated' => true
                ],
                [
                    'interface' => 'enp6s18',
                    'name' => 'enp6s18',
                    'label' => 'enp6s18 (10.0.0.50) [c3d4]',
                    'type' => 'ethernet',
                    'state' => 'up',
                    'mac' => '00:1a:2b:3c:4d:c3',
                    'ip' => '10.0.0.50',
                    'simulated' => true
                ],
                [
                    'interface' => 'wlan0',
                    'name' => 'wlan0',
                    'label' => 'wlan0 (172.16.0.10) [e5f6]',
                    'type' => 'wireless',
                    'state' => 'up',
                    'mac' => '00:1a:2b:3c:4d:e5',
                    'ip' => '172.16.0.10',
                    'simulated' => true
                ],
                [
                    'interface' => 'eth1',
                    'name' => 'eth1',
                    'label' => 'eth1 (no IP) [g7h8]',
                    'type' => 'ethernet',
                    'state' => 'down',
                    'mac' => '00:1a:2b:3c:4d:g7',
                    'ip' => null,
                    'simulated' => true
                ]
            ];
        }
        
        return $result;
    }
    
    /**
     * Get additional information about a network interface
     * 
     * @param string $ifaceName Interface name
     * @return array Interface information
     */
    private function getInterfaceInfo(string $ifaceName): array
    {
        $info = [
            'label' => $ifaceName,
            'type' => 'ethernet',
            'mac' => null,
            'ip' => null
        ];
        
        // Try to get MAC address
        $macPath = '/sys/class/net/' . $ifaceName . '/address';
        if (file_exists($macPath)) {
            $mac = trim(@file_get_contents($macPath));
            if ($mac && $mac !== '00:00:00:00:00:00') {
                $info['mac'] = $mac;
            }
        }
        
        // Try to get IP address
        $ipOutput = [];
        exec("ip addr show {$ifaceName} 2>/dev/null | grep 'inet ' | awk '{print $2}' | cut -d/ -f1 | head -1", $ipOutput);
        if (!empty($ipOutput[0])) {
            $info['ip'] = trim($ipOutput[0]);
        } else {
            // Fallback to ifconfig
            exec("ifconfig {$ifaceName} 2>/dev/null | grep 'inet ' | awk '{print $2}' | head -1", $ipOutput);
            if (!empty($ipOutput[0])) {
                $info['ip'] = trim($ipOutput[0]);
            }
        }
        
        // Try to get interface type/description
        $typePath = '/sys/class/net/' . $ifaceName . '/type';
        if (file_exists($typePath)) {
            $type = trim(@file_get_contents($typePath));
            // Type 1 = ethernet, 772 = loopback, etc.
            if ($type === '1') {
                $info['type'] = 'ethernet';
            }
        }
        
        // Build a more descriptive label
        $labelParts = [$ifaceName];
        if ($info['ip']) {
            $labelParts[] = '(' . $info['ip'] . ')';
        }
        if ($info['mac']) {
            // Show last 4 digits of MAC for identification
            $macShort = substr(str_replace(':', '', $info['mac']), -4);
            $labelParts[] = '[' . $macShort . ']';
        }
        $info['label'] = implode(' ', $labelParts);
        
        return $info;
    }
}

