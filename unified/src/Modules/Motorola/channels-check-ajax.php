<?php

declare(strict_types=1);

require_once(__DIR__ . '/../../../../app/bootstrap.php');

use Kova\Kams\Unified\Modules\Motorola\ChannelStatus;

$channelStatus = new ChannelStatus();
echo $channelStatus->renderStatus();
