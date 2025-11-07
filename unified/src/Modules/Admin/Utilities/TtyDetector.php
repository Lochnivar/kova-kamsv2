<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Utilities;

/**
 * TtyDetector
 * 
 * Detects active TTY devices from the system using dmesg and /dev scanning
 */
class TtyDetector
{
    /**
     * Detect active TTY devices from the system
     * 
     * @return array Array of detected TTY devices with their information
     */
    public function detectTtyDevices(): array
    {
        $devices = [];
        
        // Method 1: Scan /dev for TTY devices
        $ttyPatterns = ['/dev/ttyS*', '/dev/ttyUSB*', '/dev/ttyACM*'];
        
        foreach ($ttyPatterns as $pattern) {
            $files = glob($pattern);
            if ($files) {
                foreach ($files as $device) {
                    // Check if device is readable/writable (exists and is a character device)
                    if (file_exists($device) && is_readable($device)) {
                        $deviceName = basename($device);
                        $devices[$deviceName] = [
                            'interface' => $deviceName,
                            'name' => $deviceName,
                            'label' => $this->getDeviceLabel($deviceName, $device),
                            'path' => $device,
                            'type' => $this->getDeviceType($deviceName)
                        ];
                    }
                }
            }
        }
        
        // Method 2: Parse dmesg for USB serial devices
        $dmesgDevices = $this->parseDmesgForTtyDevices();
        foreach ($dmesgDevices as $deviceName => $info) {
            if (!isset($devices[$deviceName])) {
                $devices[$deviceName] = [
                    'interface' => $deviceName,
                    'name' => $deviceName,
                    'label' => $info['label'] ?? $deviceName,
                    'path' => '/dev/' . $deviceName,
                    'type' => $info['type'] ?? 'unknown',
                    'vendor' => $info['vendor'] ?? null,
                    'product' => $info['product'] ?? null
                ];
            } else {
                // Merge dmesg info if available
                if (isset($info['vendor'])) {
                    $devices[$deviceName]['vendor'] = $info['vendor'];
                }
                if (isset($info['product'])) {
                    $devices[$deviceName]['product'] = $info['product'];
                }
                if (isset($info['label']) && $info['label'] !== $deviceName) {
                    $devices[$deviceName]['label'] = $info['label'];
                }
            }
        }
        
        // Sort by device name
        ksort($devices);
        
        $result = array_values($devices);
        
        // If no devices found, return sample data for demonstration
        if (empty($result)) {
            return [
                [
                    'interface' => 'ttyUSB0',
                    'name' => 'ttyUSB0',
                    'label' => 'FTDI USB Serial Device (ttyUSB0)',
                    'path' => '/dev/ttyUSB0',
                    'type' => 'usb',
                    'simulated' => true
                ],
                [
                    'interface' => 'ttyUSB1',
                    'name' => 'ttyUSB1',
                    'label' => 'Prolific USB-to-Serial (ttyUSB1)',
                    'path' => '/dev/ttyUSB1',
                    'type' => 'usb',
                    'simulated' => true
                ],
                [
                    'interface' => 'ttyS0',
                    'name' => 'ttyS0',
                    'label' => 'Serial Port 0 (ttyS0)',
                    'path' => '/dev/ttyS0',
                    'type' => 'serial',
                    'simulated' => true
                ],
                [
                    'interface' => 'ttyS4',
                    'name' => 'ttyS4',
                    'label' => 'Serial Port 4 (ttyS4)',
                    'path' => '/dev/ttyS4',
                    'type' => 'serial',
                    'simulated' => true
                ],
                [
                    'interface' => 'ttyACM0',
                    'name' => 'ttyACM0',
                    'label' => 'USB CDC ACM Device (ttyACM0)',
                    'path' => '/dev/ttyACM0',
                    'type' => 'acm',
                    'simulated' => true
                ]
            ];
        }
        
        return $result;
    }
    
    /**
     * Parse dmesg output for TTY device information
     * 
     * @return array Array of device information keyed by device name
     */
    private function parseDmesgForTtyDevices(): array
    {
        $devices = [];
        
        // Get dmesg output
        $dmesgOutput = [];
        $exitCode = 0;
        exec('dmesg 2>/dev/null', $dmesgOutput, $exitCode);
        
        if ($exitCode !== 0 || empty($dmesgOutput)) {
            return $devices;
        }
        
        $dmesgText = implode("\n", $dmesgOutput);
        
        // Pattern for USB serial devices: "usb 1-1.2: FTDI USB Serial Device converter now attached to ttyUSB0"
        // Pattern: "ttyUSB0: USB Serial support registered"
        // Pattern: "ttyS0 at I/O 0x3f8 (irq = 4) is a 16550A"
        
        // Match USB serial devices
        if (preg_match_all('/now attached to (tty(?:USB|ACM)\d+)/i', $dmesgText, $matches)) {
            foreach ($matches[1] as $deviceName) {
                // Try to extract vendor/product info
                $vendor = null;
                $product = null;
                
                // Look for vendor/product info before the attachment message
                $pattern = '/([^:]+):\s+([^:]+)\s+converter now attached to ' . preg_quote($deviceName, '/') . '/i';
                if (preg_match($pattern, $dmesgText, $vendorMatch)) {
                    $vendor = trim($vendorMatch[1] ?? '');
                    $product = trim($vendorMatch[2] ?? '');
                }
                
                $devices[$deviceName] = [
                    'type' => strpos($deviceName, 'USB') !== false ? 'usb' : 'acm',
                    'label' => $product ?: $deviceName,
                    'vendor' => $vendor,
                    'product' => $product
                ];
            }
        }
        
        // Match standard serial ports (ttyS*)
        if (preg_match_all('/ttyS(\d+)\s+at\s+I\/O/i', $dmesgText, $matches)) {
            foreach ($matches[1] as $portNum) {
                $deviceName = 'ttyS' . $portNum;
                if (!isset($devices[$deviceName])) {
                    $devices[$deviceName] = [
                        'type' => 'serial',
                        'label' => 'Serial Port ' . $portNum
                    ];
                }
            }
        }
        
        return $devices;
    }
    
    /**
     * Get a human-readable label for a device
     * 
     * @param string $deviceName Device name (e.g., "ttyUSB0")
     * @param string $devicePath Full device path (e.g., "/dev/ttyUSB0")
     * @return string Human-readable label
     */
    private function getDeviceLabel(string $deviceName, string $devicePath): string
    {
        // Try to get USB device info using udev or sysfs
        if (strpos($deviceName, 'USB') !== false || strpos($deviceName, 'ACM') !== false) {
            // Try to get info from sysfs
            $sysfsPath = '/sys/class/tty/' . $deviceName . '/device';
            if (file_exists($sysfsPath)) {
                $realPath = realpath($sysfsPath);
                if ($realPath) {
                    // Navigate to USB device info
                    $usbPath = $realPath;
                    while ($usbPath && !file_exists($usbPath . '/idVendor')) {
                        $usbPath = dirname($usbPath);
                        if ($usbPath === '/' || $usbPath === '.') {
                            break;
                        }
                    }
                    
                    if ($usbPath && file_exists($usbPath . '/idVendor')) {
                        $vendor = @file_get_contents($usbPath . '/idVendor');
                        $product = @file_get_contents($usbPath . '/idProduct');
                        $manufacturer = @file_get_contents($usbPath . '/manufacturer');
                        $productName = @file_get_contents($usbPath . '/product');
                        
                        if ($manufacturer || $productName) {
                            return trim(($manufacturer ?: '') . ' ' . ($productName ?: ''));
                        }
                    }
                }
            }
        }
        
        // Fallback to device name
        return $deviceName;
    }
    
    /**
     * Determine device type from name
     * 
     * @param string $deviceName Device name
     * @return string Device type
     */
    private function getDeviceType(string $deviceName): string
    {
        if (strpos($deviceName, 'USB') !== false) {
            return 'usb';
        } elseif (strpos($deviceName, 'ACM') !== false) {
            return 'acm';
        } elseif (strpos($deviceName, 'ttyS') === 0) {
            return 'serial';
        }
        return 'unknown';
    }
}

