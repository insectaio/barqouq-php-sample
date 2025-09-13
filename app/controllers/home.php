<?php
// Controller for home page
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/ProductService.php';

$products = [];
$error = null;
try {
    $products = (new ProductService())->getProducts();
} catch (Throwable $e) {
    $error = $e->getMessage();
    $products = [];
}

return [
    'products' => $products,
    'error' => $error,
];
