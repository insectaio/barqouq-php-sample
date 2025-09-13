<?php
// public/product_variants.php - returns JSON list of variants for a product
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/ProductService.php';

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'invalid product_id']);
    exit;
}
try {
    $product = ProductService::findProductWithVariants($productId);
    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'not found']);
        exit;
    }
    $variants = [];
    if (method_exists($product, 'getVariants')) {
        foreach ($product->getVariants() as $v) {
            if (!$v instanceof \Barqouq\Shared\Variant) { continue; }
            $priceCurrency = null; $priceFloat = null; $priceUnits = null; $priceNanos = null;
            if (method_exists($v, 'getPrice') && $v->getPrice()) {
                $vp = $v->getPrice();
                if (method_exists($vp, 'getCurrencyCode')) { $priceCurrency = $vp->getCurrencyCode(); }
                $u = method_exists($vp, 'getUnits') ? $vp->getUnits() : 0;
                $n = method_exists($vp, 'getNanos') ? $vp->getNanos() : 0;
                $priceUnits = $u; $priceNanos = $n; $priceFloat = $u + $n / 1e9;
            }
            $variants[] = [
                'variant_id' => $v->getVariantId(),
                'name' => $v->getName() ?: ('Variant #' . $v->getVariantId()),
                'name2' => method_exists($v, 'getName2') ? $v->getName2() : null,
                'name3' => method_exists($v, 'getName3') ? $v->getName3() : null,
                'group_id' => method_exists($v, 'getGroupId') ? $v->getGroupId() : null,
                'group2_id' => method_exists($v, 'getGroup2Id') ? $v->getGroup2Id() : null,
                'group3_id' => method_exists($v, 'getGroup3Id') ? $v->getGroup3Id() : null,
                'group_name' => method_exists($v, 'getGroupName') ? $v->getGroupName() : null,
                'group2_name' => method_exists($v, 'getGroup2Name') ? $v->getGroup2Name() : null,
                'group3_name' => method_exists($v, 'getGroup3Name') ? $v->getGroup3Name() : null,
                'stock' => $v->getCurrentStock(),
                'price_currency' => $priceCurrency,
                'price_units' => $priceUnits,
                'price_nanos' => $priceNanos,
                'price' => $priceFloat,
            ];
        }
    }
    echo json_encode([
        'product_id' => $productId,
        'variants' => $variants,
        'meta' => [
            'has_group2' => array_reduce($variants, fn($c,$x)=>$c || !empty($x['group2_id']), false),
            'has_group3' => array_reduce($variants, fn($c,$x)=>$c || !empty($x['group3_id']), false),
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'server error']);
}
