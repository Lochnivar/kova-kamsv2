<?php
// kova/app/bootstrap.php
// Minimal bootstrap for consolidated kova project

// Define KOVA_ROOT once; file lives at kova/app/bootstrap.php so dirname(__DIR__) -> kova/
if (!defined('KOVA_ROOT')) {
    define('KOVA_ROOT', dirname(__DIR__));
}

// Optional: define environment (fallback to production)
if (!defined('KOVA_ENV')) {
    define('KOVA_ENV', getenv('KOVA_ENV') ?: 'production');
}

// Require Composer autoload; fail loudly if it is missing in non-dev
$vendorAutoload = KOVA_ROOT . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    // Helpful error for deployments where vendor is not installed
    // In CLI allow continued execution in some cases; in web, produce a 500 page.
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Warning: Composer autoload missing at {$vendorAutoload}\n");
    } else {
        http_response_code(500);
        echo "<h1>Application configuration error</h1>\n";
        echo "<p>Missing Composer autoload. Run <code>composer install</code> in the project root.</p>\n";
        exit(1);
    }
}

// Simple .env loader support (if project uses dotenv, this will be a no-op once composer provides vlucas/phpdotenv)
$envFile = KOVA_ROOT . '/.env';
if (file_exists($envFile) && !class_exists('\Dotenv\Dotenv')) {
    // lightweight loader: parse KEY=VALUE lines into $_ENV and putenv
    $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            if (strpos($line, '=') === false) {
                continue;
            }
            list($key, $val) = array_map('trim', explode('=', $line, 2));
            $val = trim($val, "\"'");
            if ($key !== '') {
                $_ENV[$key] = $val;
                putenv("{$key}={$val}");
            }
        }
    }
}

// Helper to get path under KOVA_ROOT
if (!function_exists('kova_path')) {
    function kova_path(string $subpath = ''): string {
        return rtrim(KOVA_ROOT . '/' . ltrim($subpath, '/'), '/');
    }
}
