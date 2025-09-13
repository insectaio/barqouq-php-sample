<?php
// public/cart.php - MVC bootstrap using View renderer
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
$data = require_once __DIR__ . '/../app/controllers/cart.php';
if (!is_array($data)) { $data = []; }
$data['title'] = 'Cart';
\App\Core\View::render('cart', $data, 'layout');
