<?php

declare(strict_types=1);

namespace Kova\Kams\Unified\Modules\Admin\Http;

class CorsHandler
{
    public function handle(): void
    {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
        }
    }
}

