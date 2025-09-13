<?php
// public/checkout.php - MVC bootstrap using View renderer
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
$data = require __DIR__ . '/../app/controllers/checkout.php';
if (!is_array($data)) { $data = []; }
// Provide a title
$data['title'] = 'Checkout';
\App\Core\View::render('checkout', $data, 'layout');

