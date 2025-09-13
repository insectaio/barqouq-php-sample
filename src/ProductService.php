<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/BarqouqClient.php';

use Barqouq\Shopfront\Product\ProductServiceClient;
use Barqouq\Shopfront\Product\SearchRequest;
use Barqouq\Shopfront\Product\FindRequest;

class ProductService
{
    private static function cfg(): array
    {
        static $cfg = null;
        if ($cfg === null) {
            $cfg = require __DIR__ . '/../config/config.php';
        }
        return $cfg;
    }

    /**
     * Fetch a page of products for listing.
     * @return array<int, mixed> Generated Product objects
     * @throws Exception when required config is missing or gRPC call fails
     */
    public static function getProducts(): array
    {
        $config = self::cfg();
        // Validate required configuration
        $host = (string)($config['barqouq_grpc_host'] ?? '');
        $secret = (string)($config['barqouq_secret_key'] ?? '');
        $subdomain = (string)($config['barqouq_subdomain'] ?? '');
        $useTls = (bool)($config['barqouq_grpc_tls'] ?? true);

        if ($host === '' || $secret === '' || $subdomain === '') {
            // Return empty list and let the UI handle showing a message
            // Alternatively, throw a clearer exception
            throw new Exception('Missing Barqouq config. Please set BARQOUQ_GRPC_HOST, BARQOUQ_SECRET_KEY, BARQOUQ_SUBDOMAIN in .env');
        }

    $client = \BarqouqClient::create(ProductServiceClient::class);
        $request = new SearchRequest();
        $request->setClientKey($secret);
        $request->setPage(1);
        $request->setSize(100);
        $request->setSubdomain($subdomain);
        list($response, $status) = $client->Search($request)->wait();

        if (($status->code ?? 0) !== 0) {
            throw new Exception("gRPC error: {$status->details}", $status->code);
        }

        // Extract products safely across potential generated API differences
        $result = [];
        if (is_object($response)) {
            if (method_exists($response, 'getProducts')) {
                $products = call_user_func([$response, 'getProducts']);
                if (is_array($products)) {
                    $result = $products;
                } elseif (is_object($products) && method_exists($products, 'getIterator')) {
                    $result = iterator_to_array(call_user_func([$products, 'getIterator']));
                }
            } elseif (method_exists($response, 'getProductsList')) {
                $list = call_user_func([$response, 'getProductsList']);
                if (is_array($list)) {
                    $result = $list;
                }
            }
        }
        return $result;
    }

    /**
     * Fetch a product with full details (including variants) by ID.
     * @param int $productId
     * @return mixed|null Generated Product object or null if not found
     * @throws Exception when required config is missing or gRPC call fails
     */
    public static function findProductWithVariants(int $productId)
    {
        $config = self::cfg();
        $host = (string)($config['barqouq_grpc_host'] ?? '');
        $secret = (string)($config['barqouq_secret_key'] ?? '');
        $subdomain = (string)($config['barqouq_subdomain'] ?? '');
        $useTls = (bool)($config['barqouq_grpc_tls'] ?? true);
        if ($host === '' || $secret === '' || $subdomain === '') {
            throw new Exception('Missing Barqouq config. Please set BARQOUQ_GRPC_HOST, BARQOUQ_SECRET_KEY, BARQOUQ_SUBDOMAIN in .env');
        }
    $client = \BarqouqClient::create(ProductServiceClient::class);
        $request = new FindRequest();
        $request->setClientKey($secret);
        $request->setSubdomain($subdomain);
        $request->setProductId($productId);
        list($response, $status) = $client->FindById($request)->wait();
        if (($status->code ?? 0) !== 0) {
            throw new Exception('gRPC FindById error: ' . ($status->details ?? 'unknown'), $status->code ?? 2);
        }
        if (is_object($response)) {
            if (method_exists($response, 'hasProduct') && call_user_func([$response, 'hasProduct'])) {
                return call_user_func([$response, 'getProduct']);
            }
            if (method_exists($response, 'getProduct')) {
                $p = call_user_func([$response, 'getProduct']);
                if ($p) {
                    return $p;
                }
            }
        }
        return null;
    }
}
