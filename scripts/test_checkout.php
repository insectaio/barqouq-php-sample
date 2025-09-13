<?php
// Quick test runner: set a cart item in session and render checkout
session_start();
// pick a product id from ProductService
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/ProductService.php';
$products = (new ProductService())->getProducts();
$pid = null;
foreach ($products as $p) {
    try { $pid = $p->getProductId(); break; } catch (Throwable $e) { }
}
if ($pid === null) {
    echo "No products found to test.\n";
    exit(1);
}
$_SESSION['cart'] = [ $pid => 1 ];
// render checkout page
require __DIR__ . '/../public/checkout.php';
