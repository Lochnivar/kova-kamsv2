<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\UDP;

use Kova\Kams\Common\Config;

/**
 * UdpController
 * 
 * Handles UDP monitor page rendering for unified frontend
 */
class UdpController
{
    private Config $config;

    public function __construct(?Config $config = null)
    {
        $this->config = $config ?? new Config();
    }

    /**
     * Render the UDP monitor page
     * Returns HTML compatible with unified page structure
     */
    public function renderMonitorPage(): string
    {
        $monitorHtmlPath = __DIR__ . '/udp-monitor.html';
        
        if (!file_exists($monitorHtmlPath)) {
            return '<div style="padding:20px;color:red;">UDP monitor page not found</div>';
        }
        
        $html = file_get_contents($monitorHtmlPath);
        
        // Update AJAX endpoint path to work when loaded via unified Dispatcher
        $html = preg_replace(
            "/(const|var)\s+UDP_API\s*=\s*['\"][^'\"]*['\"];?/",
            "var UDP_API = 'src/Modules/UDP/udp-monitor-ajax.php';",
            $html
        );
        
        return $html;
    }
}

