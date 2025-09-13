<?php
// public/home.php - MVC bootstrap using View renderer
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
$data = require_once __DIR__ . '/../app/controllers/home.php';
if (!is_array($data)) { $data = []; }
$data['title'] = 'Home';
\App\Core\View::render('home', $data, 'layout');
