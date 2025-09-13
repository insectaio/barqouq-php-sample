<?php
// public/checkout_calculate.php - AJAX endpoint to recalculate checkout totals & options
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/ProductService.php';
require_once __DIR__ . '/../src/OrderService.php';
require_once __DIR__ . '/../src/LocationService.php';
require_once __DIR__ . '/../src/CheckoutUtil.php';
require_once __DIR__ . '/../src/BarqouqClient.php';

// Collect input (POST JSON or form)
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $j = json_decode($raw, true);
        if (is_array($j)) {
            $input = $j;
        }
    }
}

$selectedCountry = $input['country'] ?? 'AE';
$selectedShipping = $input['shipping'] ?? null;
$selectedPayment = $input['payment'] ?? null;

// Build cart items via shared util
$products = (new ProductService())->getProducts();
$cartItems = \App\CheckoutUtil::buildCartItems($_SESSION['cart'] ?? [], $products);

// Shipping & payment options
$shippingOptions = OrderService::getShippingOptions($selectedCountry, $cartItems);
$paymentOptions = OrderService::getPaymentOptions($selectedCountry, $cartItems);

$selectedShippingPrev = $selectedShipping;
$selectedPaymentPrev = $selectedPayment;
$selectedShipping = \App\CheckoutUtil::selectGuidOption($shippingOptions, $selectedShipping);
$selectedPayment = \App\CheckoutUtil::selectGuidOption($paymentOptions, $selectedPayment);
if ($selectedShippingPrev && $selectedShippingPrev !== $selectedShipping) { try { error_log("[checkout_ajax] shipping fallback to {$selectedShipping}"); } catch(\Throwable $e){} }
if ($selectedPaymentPrev && $selectedPaymentPrev !== $selectedPayment) { try { error_log("[checkout_ajax] payment fallback to {$selectedPayment}"); } catch(\Throwable $e){} }

// Totals breakdown (quick local subtotal first)
$breakdown = ['currency' => null, 'subtotal' => null, 'shipping_fee' => null, 'payment_fee' => null, 'discount' => null, 'total' => null];
[$sub,$subCur] = \App\CheckoutUtil::localSubtotal($cartItems);
if ($sub !== null) { $breakdown['subtotal']=$sub; if($breakdown['currency']===null) $breakdown['currency']=$subCur; }

// Call calculate
try {
    $config = \BarqouqClient::config();
    $client = \BarqouqClient::create(\Barqouq\Shopfront\Order\OrderServiceClient::class);
    $request = new \Barqouq\Shopfront\Order\OrderRequest();
    \BarqouqClient::applyAuth($request);
    $order = \App\CheckoutUtil::buildOrder($cartItems, $selectedCountry, $selectedShipping, $selectedPayment, [
        'first_name' => $input['first_name'] ?? '',
        'last_name' => $input['last_name'] ?? '',
        'name' => $input['name'] ?? '',
        'email' => $input['email'] ?? '',
        'phone' => $input['phone'] ?? '',
        'city' => $input['city'] ?? '',
        'state' => $input['state'] ?? '',
        'address' => $input['address'] ?? '',
        'zip' => $input['zip'] ?? '',
    ]);
    error_log('[checkout_ajax] using shipping=' . json_encode($selectedShipping) . ' payment=' . json_encode($selectedPayment));
    $request->setOrder($order);
    list($reply, $status) = $client->Calculate($request)->wait();
    error_log('[checkout_ajax] Calculate status=' . json_encode($status));
    if (($status->code ?? 0) === 0 && $reply instanceof \Barqouq\Shared\OrderReply && $reply->hasOrder()) {
        [$breakdown, $totals] = \App\CheckoutUtil::populateTotalsFromOrder($reply->getOrder());
    }
} catch (\Throwable $e) {
    echo json_encode(['error' => 'calculation_failed', 'message' => $e->getMessage()]);
    exit;
}

// Prepare response structures
$itemsOut = [];
foreach ($cartItems as $it) {
    $variantName = '';
    if (!empty($it['variant']) && is_object($it['variant'])) {
        try {
            $parts = [];
            foreach (['getName', 'getName2', 'getName3'] as $nm) {
                if (method_exists($it['variant'], $nm)) {
                    $val = $it['variant']->{$nm}();
                    if ($val) $parts[] = $val;
                }
            }
            $variantName = implode(' / ', $parts);
        } catch (\Throwable $e) {
        }
    } elseif (!empty($it['variant_label'])) {
        $variantName = (string)$it['variant_label'];
    } elseif (!empty($it['variant_id'])) {
        $variantName = 'Variant #' . $it['variant_id'];
    }
    $itemsOut[] = [
        'product_id' => method_exists($it['product'], 'getProductId') ? $it['product']->getProductId() : null,
        'name' => method_exists($it['product'], 'getName') ? $it['product']->getName() : '',
    'variant' => $variantName,
        'qty' => $it['qty'],
        'currency' => $it['currency'],
        'unit_price' => $it['unit_price'],
        'total' => $it['total'],
    ];
}

$fmtOptions = function ($list) {
    $out = [];
    foreach ($list as $o) {
        $guid = isset($o['guid']) ? (string)$o['guid'] : '';
        $optName = $o['name'] ?? ($o['label'] ?? ($o['code'] ?? ''));
        $price = null; $currency = '';
        if (isset($o['fee'])) {
            $f = $o['fee'];
            if (is_numeric($f)) { $price = (float)$f; }
            elseif (is_array($f) && isset($f['units'])) { $units = $f['units'] ?? 0; $nanos=$f['nanos'] ?? 0; $price = $units + $nanos/1e9; $currency = $f['currency_code'] ?? ''; }
            elseif (is_object($f)) { try { $u = method_exists($f,'getUnits')?$f->getUnits():0; $n=method_exists($f,'getNanos')?$f->getNanos():0; $price=$u+$n/1e9; if(method_exists($f,'getCurrencyCode')) $currency=$f->getCurrencyCode()??''; } catch(\Throwable $e){} }
        } elseif (isset($o['price']) && is_numeric($o['price'])) { $price = (float)$o['price']; }
    $out[] = ['id' => $guid, 'guid' => $guid, 'name' => $optName, 'price' => $price, 'currency' => $currency, 'is_active' => $o['is_active'] ?? true];
    }
    return $out;
};

$response = [
    'breakdown' => $breakdown,
    'items' => $itemsOut,
    'shipping_options' => $fmtOptions($shippingOptions),
    'payment_options' => $fmtOptions($paymentOptions),
    'selected_shipping' => $selectedShipping,
    'selected_payment' => $selectedPayment,
];

echo json_encode($response);
