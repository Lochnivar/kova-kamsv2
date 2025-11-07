<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Utilities\Tests;

/**
 * TestDataGenerator
 * 
 * Generates synthetic test data for UDP, Serial, and Motorola modules
 */
class TestDataGenerator
{
    /**
     * Generate synthetic tcpdump-like lines for UDP testing
     * 
     * @param int $count Number of lines to generate
     * @return array Array of tcpdump-like lines
     */
    public function generateUdpTcpdumpLines(int $count): array
    {
        $lines = [];
        $baseTime = microtime(true);
        
        for ($i = 0; $i < $count; $i++) {
            $timestamp = $baseTime + ($i * 0.001); // Increment by 1ms
            $srcIp = '192.168.' . rand(1, 254) . '.' . rand(1, 254);
            $srcPort = rand(1000, 65535);
            $dstIp = '192.168.' . rand(1, 254) . '.' . rand(1, 254);
            $dstPort = rand(1000, 65535);
            $length = rand(50, 1500);
            
            $lines[] = sprintf(
                "%.6f IP %s.%d > %s.%d: UDP, length %d",
                $timestamp,
                $srcIp,
                $srcPort,
                $dstIp,
                $dstPort,
                $length
            );
        }
        
        return $lines;
    }
    
    /**
     * Generate synthetic serial data lines
     * 
     * @param int $count Number of lines to generate
     * @param string $prefix Optional prefix for test data
     * @return array Array of text lines
     */
    public function generateSerialLines(int $count, string $prefix = 'TEST'): array
    {
        $lines = [];
        
        for ($i = 0; $i < $count; $i++) {
            $timestamp = date('Y-m-d H:i:s');
            $lines[] = sprintf(
                "%s_SERIAL_DATA_%d_%s",
                $prefix,
                $i + 1,
                $timestamp
            );
        }
        
        return $lines;
    }
    
    /**
     * Generate synthetic Motorola XML packet
     * 
     * @param string $deviceId Device ID to use
     * @return string tcpdump-like line with Motorola XML
     */
    public function generateMotorolaPacket(string $deviceId): string
    {
        $timestamp = microtime(true);
        
        $xml = sprintf(
            '<AstroEvent><CallStatusEventArgs><CallStatus><DeviceID>%s</DeviceID></CallStatus></CallStatusEventArgs></AstroEvent>',
            htmlspecialchars($deviceId, ENT_XML1)
        );
        
        // Wrap in tcpdump-like format
        return sprintf(
            "%.6f IP 192.168.1.100.5000 > 192.168.1.200.5000: UDP, length 256 %s",
            $timestamp,
            $xml
        );
    }
    
    /**
     * Generate multiple Motorola packets for testing
     * 
     * @param array $deviceIds Array of device IDs to generate packets for
     * @return array Array of tcpdump-like lines with Motorola XML
     */
    public function generateMotorolaPackets(array $deviceIds): array
    {
        $lines = [];
        foreach ($deviceIds as $deviceId) {
            $lines[] = $this->generateMotorolaPacket($deviceId);
        }
        return $lines;
    }
}

