<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\SysHealth;

use Kova\Kams\Common\Config;

/**
 * SystemMonitorController
 * 
 * Handles System monitor page rendering for unified frontend
 */
class SystemMonitorController
{
    private Config $config;

    public function __construct(?Config $config = null)
    {
        $this->config = $config ?? new Config();
    }

    /**
     * Render the System monitor page
     * Returns HTML compatible with unified page structure
     */
    public function renderMonitorPage(): string
    {
        $monitorHtmlPath = __DIR__ . '/system-monitor.html';
        
        if (!file_exists($monitorHtmlPath)) {
            return '<div style="padding:20px;color:red;">System monitor page not found</div>';
        }
        
        $html = file_get_contents($monitorHtmlPath);
        
        // Get site name from config
        $siteName = $this->config->get('SiteName', 'System');
        
        // Replace PHP variables in HTML
        $html = str_replace('<?php echo htmlspecialchars($SiteName ?? \'System\'); ?>', htmlspecialchars($siteName), $html);
        
        // Update AJAX endpoint path to work when loaded via unified Dispatcher
        $html = preg_replace(
            "/(const|var)\s+SYSTEM_API\s*=\s*['\"][^'\"]*['\"];?/",
            "var SYSTEM_API = 'src/Modules/SysHealth/system-monitor-ajax.php';",
            $html
        );
        
        return $html;
    }
}

