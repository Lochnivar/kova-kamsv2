<?php

namespace Kova\Kams\Kcm\Modules\Common;

use Kova\Kams\Core\Common as CoreCommon;

/**
 * KCM Common class - uses Core\Common directly.
 * KCM-specific extensions can be added here if needed.
 */
class Common extends CoreCommon
{
    /**
     * Build message line and echo it (for CLI output).
     */
    public function buildAndEchoMsgLine(array $valArray): string
    {
        $msgLine = $this->buildMsgLine($valArray);
        echo $msgLine . PHP_EOL;
        return $msgLine;
    }
}
