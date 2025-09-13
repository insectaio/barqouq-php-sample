<?php
// public/cart_count.php - returns JSON cart item count (sum of quantities)
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    echo json_encode(['count' => 0]);
    exit;
}
$total = 0;
foreach ($_SESSION['cart'] as $line) {
    if (is_array($line) && isset($line['qty'])) {
        $q = (int)$line['qty'];
        if ($q > 0) $total += $q;
    } elseif (is_int($line)) { // legacy
        if ($line > 0) $total += $line;
    }
}
echo json_encode(['count' => $total]);
