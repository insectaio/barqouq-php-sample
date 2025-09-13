<?php
// Central helper for creating Barqouq gRPC clients and applying auth fields.
// Replaces previous ad-hoc inline client creation.

class BarqouqClient
{
    private static array $config;

    private static function loadConfig(): array
    {
        if (!isset(self::$config)) {
            self::$config = require __DIR__ . '/../config/config.php';
        }
        return self::$config;
    }

    public static function config(): array { return self::loadConfig(); }

    /**
     * Create a gRPC client for the given generated client class.
     * @param string $clientFqcn e.g. \Barqouq\Shopfront\Order\OrderServiceClient
     * @return object Created client instance
     */
    public static function create(string $clientFqcn)
    {
        $cfg = self::loadConfig();
        $host = (string)($cfg['barqouq_grpc_host'] ?? '');
        $tls = (bool)($cfg['barqouq_grpc_tls'] ?? true);
        $options = [];
        if (class_exists('\\Grpc\\ChannelCredentials')) {
            $options['credentials'] = $tls
                ? call_user_func(['\\Grpc\\ChannelCredentials','createSsl'])
                : call_user_func(['\\Grpc\\ChannelCredentials','createInsecure']);
        }
        return new $clientFqcn($host, $options);
    }

    /**
     * Apply mandatory authentication fields for requests that need subdomain & client key.
     * Supports any request object having setSubdomain/setClientKey.
     * @param object $request
     */
    public static function applyAuth($request): void
    {
        if (!is_object($request)) return;
        $cfg = self::loadConfig();
        $sub = $cfg['barqouq_subdomain'] ?? '';
        $key = $cfg['barqouq_secret_key'] ?? '';
        if ($sub && method_exists($request,'setSubdomain')) { $request->setSubdomain($sub); }
        if ($key && method_exists($request,'setClientKey')) { $request->setClientKey($key); }
    }
}
