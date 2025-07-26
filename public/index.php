<?php
require_once __DIR__ . '/../src/ProductService.php';

try {
    $products = ProductService::getProducts();
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
    exit;
}

include __DIR__ . '/templates/index.php';