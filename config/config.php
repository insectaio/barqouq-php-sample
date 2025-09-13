<?php
// config.php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
// Load .env if present; don't error if it's missing
if (method_exists($dotenv, 'safeLoad')) {
    $dotenv->safeLoad();
} else {
    // Fallback for older versions
    try { $dotenv->load(); } catch (Throwable $e) { /* ignore */ }
}

// Helper to fetch env values robustly
if (!function_exists('barq_env')) {
    function barq_env(string $key, $default = null) {
        if (array_key_exists($key, $_ENV)) {
            $v = $_ENV[$key];
            return is_string($v) ? trim($v) : $v;
        }
        if (array_key_exists($key, $_SERVER)) {
            $v = $_SERVER[$key];
            return is_string($v) ? trim($v) : $v;
        }
        $v = getenv($key);
        if ($v !== false) {
            return is_string($v) ? trim($v) : $v;
        }
        return $default;
    }
}

return [
    'barqouq_grpc_host'  => (string) (barq_env('BARQOUQ_GRPC_HOST', 'api.barqouq.shop:443') ?? 'api.barqouq.shop:443'),
    'barqouq_grpc_tls'   => strtolower((string) (barq_env('BARQOUQ_GRPC_TLS', 'true') ?? 'true')) !== 'false',
    'barqouq_secret_key' => (string) (barq_env('BARQOUQ_SECRET_KEY', '') ?? ''),
    'barqouq_subdomain'  => (string) (barq_env('BARQOUQ_SUBDOMAIN', '') ?? ''),
];