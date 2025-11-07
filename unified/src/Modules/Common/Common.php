<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Common;

use Kova\Kams\Core\Common as CoreCommon;

/**
 * Common class for Unified module
 * Extends Core\Common and adds Unified-specific methods
 */
class Common extends CoreCommon
{
    /**
     * Get navigation bar HTML (echoes for web output)
     * Uses type-safe boolean checks to determine active modules
     */
    public function getNavBar(): string
    {
        $navBar = "";
        
        // Use type-safe boolean checks - handles "yes", "1", true, etc.
        $motorolaPage = $this->isModuleEnabled('MotorolaPage');
        $udpMonitorPage = $this->isModuleEnabled('UDPMonitorPage');
        $serialMonitorPage = $this->isModuleEnabled('SerialMonitorPage');
        $zabbixPage = $this->isModuleEnabled('ZabbixPage');

        // Motorola-Channels: Show if Motorola is enabled AND at least one other module is enabled
        if ($motorolaPage && ($udpMonitorPage || $serialMonitorPage || $zabbixPage)) {
            $navBar .= '<button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" class="active" href="#" onclick="getMotorolaChannels(); return false;">Motorola-Channels</a></button>';
        }
        
        // UDP-Monitor: Show if enabled
        if ($udpMonitorPage) {
            $navBar .= '<button style="background-color:yellow;border-radius: 6px;color:black"><a style="color:black;" href="#" onclick="getUdpMonitor(); return false;">UDP-Monitor</a></button>';
        }
        
        // Serial-Monitor: Show if enabled
        if ($serialMonitorPage) {
            $navBar .= '<button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="#" onclick="getSerialMonitor(); return false;">Serial-Monitor</a></button>';
        }
        
        // Server-Monitor (Zabbix): Show if enabled
        if ($zabbixPage) {
            $navBar .= '<button style="background-color:gray;border-radius: 6px;color:black"><a style="color:black;" href="#" onclick="getSystemMonitor(); return false;">Server-Monitor</a></button>';
        }

        // Don't echo here - Dispatcher handles output
        return $navBar;
    }
    
    /**
     * Check if a module is enabled
     * Handles various boolean representations: "yes", "1", true, 1, etc.
     * 
     * @param string $key Setting key (e.g., "MotorolaPage")
     * @return bool True if module is enabled
     */
    private function isModuleEnabled(string $key): bool
    {
        $value = $this->config[$key] ?? null;
        
        if ($value === null) {
            return false;
        }
        
        // If we have a Config object, use type-safe getter
        if ($this->configObj !== null && method_exists($this->configObj, 'getBool')) {
            return $this->configObj->getBool($key, false);
        }
        
        // Otherwise, normalize manually
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_int($value)) {
            return $value !== 0;
        }
        
        if (is_string($value)) {
            $normalized = strtolower(trim($value));
            return in_array($normalized, ['1', 'yes', 'true', 'on'], true);
        }
        
        return false;
    }

    /**
     * Get site name (returns string only, no echo)
     * Note: Dispatcher will handle the output
     */
    public function getSiteName(): string
    {
        $siteName = $this->config['SiteName'] ?? '';
        // Don't echo here - let the Dispatcher handle output
        return $siteName;
    }

    /**
     * Get interfaces for a module
     * Uses parent implementation but can override if needed
     */
    public function getIfaces(string $mod): array
    {
        return parent::getIfaces($mod);
    }
}
