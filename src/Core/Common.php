<?php

declare(strict_types=1);

namespace Kova\Kams\Core;

use Kova\Kams\Common\Config;

/**
 * Core Common class
 * 
 * Provides shared utility methods for both Unified and KCM modules.
 * Consolidated from Unified\Common and KCM\Common.
 */
class Common
{
    /** @var array<string, mixed> */
    public array $config;

    protected ?Config $configObj;

    public function __construct($config = null)
    {
        if ($config instanceof Config) {
            $this->configObj = $config;
            $this->config = $config->config;
        } elseif (is_array($config)) {
            $this->config = $config;
            $this->configObj = null;
        } else {
            // Default: load config
            $this->configObj = new Config();
            $this->config = $this->configObj->config;
        }
    }

    /**
     * Get navigation bar HTML
     * Overridden by child classes for specific implementations
     */
    public function getNavBar(): string
    {
        // Default implementation - child classes should override
        return '';
    }

    /**
     * Get site name
     * Overridden by child classes for specific implementations
     */
    public function getSiteName(): string
    {
        return $this->config['SiteName'] ?? '';
    }

    /**
     * Get interfaces for a module
     * 
     * @param string $mod Module name (e.g., "udp", "serial", "moto")
     * @return array<string, array{name: string, alias?: string, threshold?: string|int}>
     */
    public function getIfaces(string $mod): array
    {
        $configMod = "";
        switch ($mod) {
            case "udp":
                $configMod = "UDPInterfaceName";
                break;
            case "serial":
                $configMod = "SerialInterfaceName";
                break;
            case "moto":
                $configMod = "MotorolaInterfaceName";
                break;
            case "zbx":
                $configMod = "zbx";
                break;
            default:
                return [];
        }

        if (!isset($this->config[$configMod])) {
            return [];
        }

        $ifaces = $this->config[$configMod] ?? null;

        // Return empty array if config value doesn't exist
        if ($ifaces === null) {
            return [];
        }

        // Handle string format: "interface1|alias1|threshold1~interface2|alias2|threshold2"
        if (is_string($ifaces)) {
            $ifaces = explode("~", $ifaces);
        }

        // Ensure $ifaces is an array
        if (!is_array($ifaces)) {
            return [];
        }

        // Return empty array if no interfaces
        if (empty($ifaces)) {
            return [];
        }

        // Handle array of arrays (already formatted)
        // Check if first element exists and is an array with 'name' key (already formatted)
        if (isset($ifaces[0]) && is_array($ifaces[0]) && isset($ifaces[0]['name'])) {
            $result = [];
            foreach ($ifaces as $iface) {
                if (isset($iface['name'])) {
                    $result[$iface['name']] = $iface;
                }
            }
            return $result;
        }

        // Parse string format
        $ifArray = [];
        foreach ($ifaces as $iface) {
            // If already a properly formatted array, use it directly
            if (is_array($iface) && isset($iface['name'])) {
                $ifArray[$iface['name']] = $iface;
                continue;
            }

            // Otherwise, treat as string and parse it
            if (is_string($iface)) {
                $iArray = explode("|", $iface);
                if (isset($iArray[0]) && !empty($iArray[0])) {
                    $ifArray[$iArray[0]]['name'] = $iArray[0];
                    $ifArray[$iArray[0]]['alias'] = $iArray[1] ?? '';
                    $ifArray[$iArray[0]]['threshold'] = $iArray[2] ?? '';
                }
            }
        }

        return $ifArray;
    }

    /**
     * Get file contents
     */
    public function getContents(string $file): string
    {
        if (!file_exists($file)) {
            return '';
        }
        return file_get_contents($file);
    }

    /**
     * Clean string
     */
    public function clean(string $string): string
    {
        return trim(strip_tags($string));
    }

    /**
     * Build message line from array
     */
    public function buildMsgLine(array $valArray): string
    {
        return implode(' | ', array_map(function($key, $value) {
            return $key . ': ' . $value;
        }, array_keys($valArray), $valArray));
    }

    /**
     * Get string between two strings
     */
    public function get_string_between(string $string, string $start, string $end): string
    {
        $string = ' ' . $string;
        $ini = strpos($string, $start);
        if ($ini == 0) return '';
        $ini += strlen($start);
        $len = strpos($string, $end, $ini) - $ini;
        return substr($string, $ini, $len);
    }
}

