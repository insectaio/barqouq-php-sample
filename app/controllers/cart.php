<?php
// Controller for cart page
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/ProductService.php';
require_once __DIR__ . '/../../src/CheckoutUtil.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Migration: legacy format was [product_id => qty]
// New format: keyed by composite product_id:variant_id storing associative arrays
foreach ($_SESSION['cart'] as $k => $v) {
    if (is_int($k) || ctype_digit((string)$k)) {
        // convert to new structure with null variant
        $qty = $v;
        unset($_SESSION['cart'][$k]);
        $_SESSION['cart'][$k . ':'] = [
            'product_id' => (int)$k,
            'variant_id' => null,
            'qty' => (int)$qty,
            'currency' => null,
            'unit_price' => null,
            'total' => null,
        ];
    }
}
// Migration: ensure new pricing fields exist and recompute total if missing
foreach ($_SESSION['cart'] as $k => &$lineRef) {
    if (!is_array($lineRef)) continue;
    if (!array_key_exists('currency', $lineRef)) $lineRef['currency'] = null;
    if (!array_key_exists('unit_price', $lineRef)) $lineRef['unit_price'] = null;
    if (!array_key_exists('total', $lineRef)) $lineRef['total'] = ($lineRef['unit_price'] !== null && $lineRef['qty']) ? ($lineRef['unit_price'] * $lineRef['qty']) : null;
}
unset($lineRef);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['product_id']) && isset($_POST['action'])) {
        $productId = (int)$_POST['product_id'];
        $variantId = isset($_POST['variant_id']) && $_POST['variant_id'] !== '' ? (int)$_POST['variant_id'] : null;
        $compositeKey = $productId . ':' . ($variantId !== null ? $variantId : '');
    if ($_POST['action'] === 'add') {
            // Lazy load product (with variants) to capture correct price (variant overrides product price)
            $productObj = null;
            try {
                $searchList = ProductService::getProducts();
                foreach ($searchList as $sp) { if ($sp->getProductId() == $productId) { $productObj = $sp; break; } }
            } catch (Throwable $e) {}
            $detailedTried = false;
            // If product likely has variants but variant not provided, fetch detailed
            if (!$productObj) {
                try { $productObj = ProductService::findProductWithVariants($productId); $detailedTried = true; } catch (Throwable $e) {}
            }
            // Guard: if product has variants and no variant_id provided, reject add
            $hasVariants = false;
            if ($productObj && method_exists($productObj, 'getVariants')) {
                try { foreach ($productObj->getVariants() as $_tmp) { $hasVariants = true; break; } } catch (\Throwable $e) {}
            }
            if ($hasVariants && $variantId === null) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'variant_required', 'message' => 'Please choose a variant before adding to cart.']);
                exit;
            }
            if ($variantId !== null) {
                // We definitely need variants – fetch detailed product if search list lacks them
                $hasVariantList = false;
                if ($productObj && method_exists($productObj, 'getVariants')) {
                    foreach ($productObj->getVariants() as $_tmp) { $hasVariantList = true; break; }
                }
                if (!$hasVariantList) {
                    try { $productObj = ProductService::findProductWithVariants($productId); $detailedTried = true; } catch (Throwable $e) {}
                }
            }
            if (!$productObj && !$detailedTried) {
                try { $productObj = ProductService::findProductWithVariants($productId); } catch (Throwable $e) {}
            }
            $currency = null; $unitPrice = null;
            $variantPriceApplied = false;
            // Try variant price first
            if ($productObj && $variantId !== null && method_exists($productObj, 'getVariants')) {
                foreach ($productObj->getVariants() as $v) {
                    if ($v instanceof \Barqouq\Shared\Variant && $v->getVariantId() == $variantId) {
                        if (method_exists($v, 'getPrice') && $v->getPrice()) {
                            $vPrice = $v->getPrice();
                            if (method_exists($vPrice, 'getCurrencyCode')) $currency = $vPrice->getCurrencyCode();
                            $units = method_exists($vPrice, 'getUnits') ? $vPrice->getUnits() : 0;
                            $nanos = method_exists($vPrice, 'getNanos') ? $vPrice->getNanos() : 0;
                            $unitPrice = $units + $nanos / 1e9;
                            $variantPriceApplied = true;
                        }
                        break;
                    }
                }
            }
            // Fallback to product price if variant price missing
            if (!$variantPriceApplied && $productObj && method_exists($productObj, 'getPrice') && $productObj->getPrice()) {
                $priceObj = $productObj->getPrice();
                if (method_exists($priceObj, 'getCurrencyCode')) $currency = $priceObj->getCurrencyCode();
                $units = method_exists($priceObj, 'getUnits') ? $priceObj->getUnits() : 0;
                $nanos = method_exists($priceObj, 'getNanos') ? $priceObj->getNanos() : 0;
                $unitPrice = $units + $nanos / 1e9;
            }
            if (!isset($_SESSION['cart'][$compositeKey])) {
                $_SESSION['cart'][$compositeKey] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'qty' => 0,
                    'currency' => $currency,
                    'unit_price' => $unitPrice,
                    'total' => 0.0,
                    'variant_label' => isset($_POST['variant_label']) ? (string)$_POST['variant_label'] : null,
                ];
            } else {
                // Update variant label if provided
                if (isset($_POST['variant_label']) && $_POST['variant_label'] !== '') {
                    $_SESSION['cart'][$compositeKey]['variant_label'] = (string)$_POST['variant_label'];
                }
            }
            $_SESSION['cart'][$compositeKey]['qty'] += 1;
            // If we just added and price fields are null, attempt to fill
            if ($_SESSION['cart'][$compositeKey]['unit_price'] === null && $unitPrice !== null) {
                $_SESSION['cart'][$compositeKey]['unit_price'] = $unitPrice;
                $_SESSION['cart'][$compositeKey]['currency'] = $currency;
            }
            if ($_SESSION['cart'][$compositeKey]['unit_price'] !== null) {
                $_SESSION['cart'][$compositeKey]['total'] = $_SESSION['cart'][$compositeKey]['unit_price'] * $_SESSION['cart'][$compositeKey]['qty'];
            }
        } elseif ($_POST['action'] === 'remove') {
            unset($_SESSION['cart'][$compositeKey]);
        }
    header('Location: /cart');
        exit;
    }
}

// Build cart items using shared utility for consistency and readability
$products = (new ProductService())->getProducts();
$cartItems = \App\CheckoutUtil::buildCartItems($_SESSION['cart'], $products);

return [
    'cartItems' => $cartItems,
];
