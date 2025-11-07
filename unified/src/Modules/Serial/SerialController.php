<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Serial;

use Kova\Kams\Common\Config;

/**
 * SerialController
 * 
 * Handles Serial monitor page rendering for unified frontend
 */
class SerialController
{
    private Config $config;

    public function __construct(?Config $config = null)
    {
        $this->config = $config ?? new Config();
    }

    /**
     * Render the Serial monitor page
     * Returns HTML compatible with unified page structure
     */
    public function renderMonitorPage(): string
    {
        $monitorHtmlPath = __DIR__ . '/serial-monitor.html';
        
        if (!file_exists($monitorHtmlPath)) {
            return '<div style="padding:20px;color:red;">Serial monitor page not found</div>';
        }
        
        $html = file_get_contents($monitorHtmlPath);
        
        // Update AJAX endpoint path to work when loaded via unified Dispatcher
        $html = preg_replace(
            "/(const|var)\s+SERIAL_API\s*=\s*['\"][^'\"]*['\"];?/",
            "var SERIAL_API = 'src/Modules/Serial/serial-monitor-ajax.php';",
            $html
        );
        
        return $html;
    }
}

