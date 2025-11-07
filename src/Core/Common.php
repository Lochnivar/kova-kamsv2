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

        // Try to get normalized value using Config object if available
        // This ensures JSON strings are properly decoded based on the 'object' type
        if ($this->configObj !== null && method_exists($this->configObj, 'getObject')) {
            // First, get the RAW value from config before normalization
            $rawValue = $this->config[$configMod] ?? null;
            error_log("getIfaces({$mod}): RAW value from config array (before getObject): type=" . gettype($rawValue));
            if (is_string($rawValue)) {
                error_log("getIfaces({$mod}): RAW string value: " . $rawValue);
            } elseif (is_array($rawValue)) {
                error_log("getIfaces({$mod}): RAW array value: " . json_encode($rawValue));
            }
            
            // Now get the normalized value
            $ifaces = $this->configObj->getObject($configMod, []);
            error_log("getIfaces({$mod}): Value AFTER getObject() normalization: type=" . gettype($ifaces));
            if (is_string($ifaces)) {
                error_log("getIfaces({$mod}): Normalized string: " . substr($ifaces, 0, 500));
            } elseif (is_array($ifaces)) {
                error_log("getIfaces({$mod}): Normalized array: " . json_encode($ifaces));
            }
        } else {
            $ifaces = $this->config[$configMod] ?? null;
            error_log("getIfaces({$mod}): Direct config access (no getObject): type=" . gettype($ifaces));
            if (is_string($ifaces)) {
                error_log("getIfaces({$mod}): Direct string value: " . substr($ifaces, 0, 500));
            } elseif (is_array($ifaces)) {
                error_log("getIfaces({$mod}): Direct array value: " . json_encode($ifaces));
            }
        }

        // Return empty array if config value doesn't exist
        if ($ifaces === null) {
            error_log("getIfaces({$mod}): Config value is null, returning empty array");
            return [];
        }

        // If config value is a JSON string, decode it first
        if (is_string($ifaces)) {
            // Try to decode as JSON first
            $decoded = json_decode($ifaces, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $ifaces = $decoded;
                error_log("getIfaces({$mod}): Successfully decoded JSON, count: " . count($ifaces));
            } else {
                error_log("getIfaces({$mod}): JSON decode failed: " . json_last_error_msg());
                // Fallback: treat as pipe-delimited string format
                $ifaces = explode("~", $ifaces);
            }
        }

        // Ensure $ifaces is an array
        if (!is_array($ifaces)) {
            return [];
        }

        // Return empty array if no interfaces
        if (empty($ifaces)) {
            return [];
        }

        // Handle single object format: {"interface":"enp6s18","label":"Viper 1","threshold":100}
        // When JSON decodes a single object, it becomes an associative array with string keys
        // Check if it's a single object (has 'interface' or 'name' key but no numeric index 0)
        $hasInterfaceKey = isset($ifaces['interface']) || isset($ifaces['name']);
        $hasNumericIndex = isset($ifaces[0]);
        
        if ($hasInterfaceKey && !$hasNumericIndex) {
            // Single object - wrap it in an array and process
            $ifaces = [$ifaces];
            error_log("getIfaces({$mod}): Detected single object format, wrapped in array. Object: " . json_encode($ifaces[0]));
        }

        // Handle array of objects (new format: interface/label/threshold or old format: name/alias)
        // Check if first element exists and is an array with 'interface' or 'name' key
        if (isset($ifaces[0]) && is_array($ifaces[0])) {
            $result = [];
            foreach ($ifaces as $iface) {
                if (!is_array($iface)) {
                    continue;
                }
                // New format: interface, label, threshold
                if (isset($iface['interface'])) {
                    $result[$iface['interface']] = $iface;
                    error_log("getIfaces({$mod}): Added interface from array: " . $iface['interface'] . " with label: " . ($iface['label'] ?? 'missing'));
                }
                // Old format: name, alias
                elseif (isset($iface['name'])) {
                    $result[$iface['name']] = $iface;
                    error_log("getIfaces({$mod}): Added interface from array (old format): " . $iface['name']);
                }
            }
            if (!empty($result)) {
                error_log("getIfaces({$mod}): Returning " . count($result) . " interfaces from array format");
                return $result;
            }
        }

        // Handle associative array already keyed by interface name
        // Check if it's an associative array where values are interface objects
        $firstValue = reset($ifaces);
        if (is_array($firstValue) && (isset($firstValue['interface']) || isset($firstValue['name']))) {
            // Already in the correct format - return as-is, but ensure we only have valid interface objects
            $result = [];
            foreach ($ifaces as $key => $iface) {
                if (!is_array($iface)) {
                    continue;
                }
                // Only include if it has interface or name field
                if (isset($iface['interface'])) {
                    $result[$iface['interface']] = $iface;
                } elseif (isset($iface['name'])) {
                    $result[$iface['name']] = $iface;
                }
            }
            return $result;
        }

        // Parse string format or handle other formats
        $ifArray = [];
        foreach ($ifaces as $key => $iface) {
            // If already a properly formatted array with interface field
            if (is_array($iface) && isset($iface['interface'])) {
                $ifArray[$iface['interface']] = $iface;
                continue;
            }
            // If already a properly formatted array with name field (old format)
            if (is_array($iface) && isset($iface['name'])) {
                $ifArray[$iface['name']] = $iface;
                continue;
            }

            // Otherwise, treat as string and parse it
            if (is_string($iface)) {
                $iArray = explode("|", $iface);
                if (isset($iArray[0]) && !empty($iArray[0])) {
                    $ifArray[$iArray[0]]['interface'] = $iArray[0];
                    $ifArray[$iArray[0]]['name'] = $iArray[0]; // Backward compatibility
                    $ifArray[$iArray[0]]['label'] = $iArray[1] ?? '';
                    $ifArray[$iArray[0]]['alias'] = $iArray[1] ?? ''; // Backward compatibility
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

