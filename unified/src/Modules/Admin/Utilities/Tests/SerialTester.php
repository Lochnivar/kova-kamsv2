<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Utilities\Tests;

/**
 * SerialTester
 * 
 * Writes test data to serial devices
 */
class SerialTester
{
    /**
     * Write test data to serial device
     * 
     * @param string $device Device path (e.g., "ttyS4" or "/dev/ttyS4")
     * @param string|null $data Optional test data prefix
     * @param int $lines Number of lines to send
     * @return array{success: bool, message: string, linesSent: int}
     */
    public function writeToSerial(string $device, ?string $data = null, int $lines = 10): array
    {
        try {
            // Ensure device path is correct
            $fullPath = strpos($device, '/dev/') === 0 ? $device : "/dev/{$device}";
            
            if (!file_exists($fullPath)) {
                return [
                    'success' => false,
                    'message' => "Device {$fullPath} does not exist",
                    'linesSent' => 0
                ];
            }
            
            // Check if device is writable
            if (!is_writable($fullPath)) {
                return [
                    'success' => false,
                    'message' => "Device {$fullPath} is not writable (may require permissions or proper device setup)",
                    'linesSent' => 0
                ];
            }
            
            // Open device for writing (simulates external device sending data)
            $handle = @fopen($fullPath, 'w');
            if (!$handle) {
                return [
                    'success' => false,
                    'message' => "Could not open {$fullPath} for writing: " . error_get_last()['message'] ?? 'Unknown error',
                    'linesSent' => 0
                ];
            }
            
            $generator = new TestDataGenerator();
            $testLines = $generator->generateSerialLines($lines, $data ?: 'TEST');
            
            $linesSent = 0;
            foreach ($testLines as $line) {
                // Write to device (simulates external device transmitting)
                $written = @fwrite($handle, $line . "\n");
                if ($written !== false) {
                    $linesSent++;
                }
                // Small delay between lines
                usleep(50000); // 50ms
            }
            
            fclose($handle);
            
            return [
                'success' => true,
                'message' => "Sent {$linesSent} lines to {$fullPath}",
                'linesSent' => $linesSent
            ];
            
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error writing to serial device: ' . $e->getMessage(),
                'linesSent' => 0
            ];
        }
    }
}

