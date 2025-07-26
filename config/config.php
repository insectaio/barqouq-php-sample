<?php
// config.php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load(); // loads .env into $_ENV and $_SERVER

return [
    'barqouq_grpc_host'  => $_ENV['BARQOUQ_GRPC_HOST'] ?? 'api.barqouq.shop:443',
    'barqouq_grpc_tls'   => ($_ENV['BARQOUQ_GRPC_TLS'] ?? 'true') !== 'false',
    'barqouq_secret_key' => $_ENV['BARQOUQ_SECRET_KEY'] ?? '',
    'barqouq_subdomain'  => $_ENV['BARQOUQ_SUBDOMAIN'] ?? '',
];