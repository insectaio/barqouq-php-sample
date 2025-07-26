<?php
require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/../config/config.php';

use Barqouq\Shopfront\Product\ProductServiceClient;
use Barqouq\Shopfront\Product\SearchRequest;

class ProductService
{
    public static function getProducts(): array
    {
        global $config;

        $options = [];
        $options['credentials'] = $config['barqouq_grpc_tls']
            ? \Grpc\ChannelCredentials::createSsl()
            : \Grpc\ChannelCredentials::createInsecure();

        $client = new ProductServiceClient($config['barqouq_grpc_host'], $options);
        $request = new SearchRequest();
        $request->setClientKey($config['barqouq_secret_key']);
        $request->setPage(1);
        $request->setSize(10);
        $request->setSubdomain($config['barqouq_subdomain']);

        list($response, $status) = $client->Search($request)->wait();

        if ($status->code !== 0) {
            throw new Exception("gRPC error: {$status->details}", $status->code);
        }

        return iterator_to_array($response->getProducts()->getIterator());
    }
}