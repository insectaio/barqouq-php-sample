<?php
// public/router.php - Router for PHP built-in server to enable pretty URLs
// Usage: php -S localhost:8000 -t public public/router.php

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$path = rtrim($uri, '/') ?: '/';

// Serve existing files (assets) directly
$file = __DIR__ . $uri;
if ($uri !== '/' && is_file($file)) {
    return false; // let built-in server handle it
}

// Helper to include a script safely
function _include_script($script){
    $target = __DIR__ . '/' . ltrim($script, '/');
    if (is_file($target)) { include $target; } else { http_response_code(404); echo 'Not Found'; }
}

switch ($path) {
    case '/':
    case '/home':
        _include_script('home.php');
        break;
    case '/cart':
        _include_script('cart.php');
        break;
    case '/checkout':
        _include_script('checkout.php');
        break;
    case '/checkout/success':
        $_GET['success'] = '1';
        _include_script('checkout.php');
        break;
    case '/checkout/failure':
        $_GET['failed'] = '1';
        _include_script('checkout.php');
        break;
    default:
        // Optional pretty API mapping (keep legacy .php endpoints working too)
        if ($path === '/checkout/calculate') { _include_script('checkout_calculate.php'); break; }
        if ($path === '/checkout/place') { _include_script('checkout_place.php'); break; }
    if ($path === '/product/variants') { _include_script('product_variants.php'); break; }
        // Order result pages
        if (preg_match('#^/order/(\d+)$#', $path, $m)) {
            $_GET['id'] = (int)$m[1];
            _include_script('order.php');
            break;
        }
        if (preg_match('#^/order/session/([^/]+)$#', $path, $m)) {
            $_GET['session'] = urldecode($m[1]);
            _include_script('order.php');
            break;
        }
        // Handle success/failure with token segment e.g., /checkout/success/{token}
        if (preg_match('#^/checkout/(success|failure)/([^/]+)$#', $path, $m)) {
            if ($m[1] === 'success') { $_GET['success'] = '1'; }
            if ($m[1] === 'failure') { $_GET['failed'] = '1'; }
            $_GET['token'] = $m[2];
            // Propagate order_id if present in original query
            if (!isset($_GET['order_id']) && isset($_SERVER['QUERY_STRING'])) {
                parse_str($_SERVER['QUERY_STRING'], $qs);
                if (!empty($qs['order_id'])) { $_GET['order_id'] = (int)$qs['order_id']; }
            }
            _include_script('checkout.php');
            break;
        }
        http_response_code(404);
        echo 'Not Found';
}
