<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Motorola;

use Kova\Kams\Common\Config;

/**
 * MotorolaController
 * 
 * Handles Motorola channel status page rendering for unified frontend
 */
class MotorolaController
{
    private Config $config;

    public function __construct(?Config $config = null)
    {
        $this->config = $config ?? new Config();
    }

    /**
     * Render the Motorola channels status page
     * Returns HTML compatible with unified page structure
     */
    public function renderChannelsPage(): string
    {
        $channelsHtmlPath = __DIR__ . '/motorola-channels.html';
        
        if (!file_exists($channelsHtmlPath)) {
            return '<div style="padding:20px;color:red;">Motorola channels page not found</div>';
        }
        
        $html = file_get_contents($channelsHtmlPath);
        
        // Update AJAX endpoint path to work when loaded via unified Dispatcher
        $html = preg_replace(
            "/(const|var)\s+CHANNELS_API\s*=\s*['\"][^'\"]*['\"];?/",
            "var CHANNELS_API = 'src/Modules/Motorola/channels-check-ajax.php';",
            $html
        );
        
        return $html;
    }
}

