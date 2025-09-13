<?php
// public/order.php - MVC bootstrap for order result
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

// Support path-based params via router (sets $_GET)
$data = require __DIR__ . '/../app/controllers/order.php';
if (!is_array($data)) { $data = []; }
$data['title'] = 'Order Result';
\App\Core\View::render('order', $data, 'layout');
