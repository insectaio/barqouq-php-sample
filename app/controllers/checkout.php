<?php
// Controller: handles request processing for checkout page
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../src/ProductService.php';
require_once __DIR__ . '/../../src/OrderService.php';
require_once __DIR__ . '/../../src/LocationService.php';
require_once __DIR__ . '/../../src/CheckoutUtil.php';
require_once __DIR__ . '/../../src/BarqouqClient.php';

// gather products and cart
$products = (new ProductService())->getProducts();
$cartItems = \App\CheckoutUtil::buildCartItems($_SESSION['cart'] ?? [], $products);

// Collect customer/shipping fields from POST (or defaults)
$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$name = trim($_POST['name'] ?? ''); // legacy single name (fallback)
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$selectedCountry = $_POST['country'] ?? 'AE';
$city = trim($_POST['city'] ?? '');
$state = trim($_POST['state'] ?? '');
$address = trim($_POST['address'] ?? '');
$zip = trim($_POST['zip'] ?? '');
$customer = [
    'first_name' => $first_name,
    'last_name' => $last_name,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'city' => $city,
    'state' => $state,
    'address' => $address,
    'zip' => $zip,
];

// Prepare shipping/payment selection: default to first active option if none provided
$countries = LocationService::getCountries();
$shippingOptions = OrderService::getShippingOptions($selectedCountry, $cartItems);
$paymentOptions = OrderService::getPaymentOptions($selectedCountry, $cartItems);

$selectedShipping = $_POST['shipping'] ?? null;
$selectedPayment = $_POST['payment'] ?? null;
// Use shared helper to enforce GUID-only selection with fallback to first active
$prevShip = $selectedShipping; $prevPay = $selectedPayment;
$selectedShipping = \App\CheckoutUtil::selectGuidOption($shippingOptions, $selectedShipping);
$selectedPayment = \App\CheckoutUtil::selectGuidOption($paymentOptions, $selectedPayment);
if ($prevShip && $prevShip !== $selectedShipping) { try { error_log("[checkout] shipping guid '{$prevShip}' not found; fallback '{$selectedShipping}'"); } catch (\Throwable $e) {} }
if ($prevPay && $prevPay !== $selectedPayment) { try { error_log("[checkout] payment guid '{$prevPay}' not found; fallback '{$selectedPayment}'"); } catch (\Throwable $e) {} }

// Detect place order intent
$isPlaceOrder = isset($_POST['action']) && $_POST['action'] === 'place';
if (!$isPlaceOrder && isset($_POST['place_order'])) { $isPlaceOrder = true; }
// Handle return from payment redirect
$paymentSuccess = isset($_GET['success']);
$paymentFailed = isset($_GET['failed']);
$paymentToken = isset($_GET['token']) ? (string)$_GET['token'] : null;
// If token came via pretty route segment it may be percent-encoded; decode before use
if ($paymentToken !== null && $paymentToken !== '') {
    $paymentToken = urldecode($paymentToken);
}
// No longer rely on order_id in URL

// If we have enough info, call OrderService::Calculate to compute totals
$totals = null; // legacy simple total structure (kept for backward compatibility)
$breakdown = [
    'currency' => null,
    'subtotal' => null,
    'shipping_fee' => null,
    'payment_fee' => null,
    'discount' => null,
    'total' => null,
];

// Pre-calc local subtotal for quick UI feedback
[$sub, $subCur] = \App\CheckoutUtil::localSubtotal($cartItems);
if ($sub !== null) { $breakdown['subtotal'] = $sub; if ($breakdown['currency'] === null) $breakdown['currency'] = $subCur; }
try {
    // Build an OrderRequest similar to the one used in OrderService helpers
    $config = \BarqouqClient::config();
    $client = \BarqouqClient::create(\Barqouq\Shopfront\Order\OrderServiceClient::class);
    $request = new \Barqouq\Shopfront\Order\OrderRequest();
    \BarqouqClient::applyAuth($request);
    $order = \App\CheckoutUtil::buildOrder($cartItems, $selectedCountry, $selectedShipping, $selectedPayment, $customer);
    error_log('[checkout] using shipping=' . json_encode($selectedShipping) . ' payment=' . json_encode($selectedPayment));
    $request->setOrder($order);
    // Decide RPC: Calculate for preview, Add for final place
    if ($isPlaceOrder) {
        list($reply, $status) = $client->Add($request)->wait();
    } else {
        list($reply, $status) = $client->Calculate($request)->wait();
    }
    error_log('OrderService::Calculate status: ' . json_encode([
        'code' => $status->code ?? null,
        'details' => $status->details ?? null,
    ]));
    $placedOrderId = null;
    if (($status->code ?? 0) === 0 && $reply instanceof \Barqouq\Shared\OrderReply && $reply->hasOrder()) {
        $replyOrder = $reply->getOrder();
        // capture order id if available
        if (method_exists($replyOrder, 'getOrderId')) { try { $placedOrderId = $replyOrder->getOrderId(); } catch (\Throwable $e) {} }
    // Extract totals/breakdown via util
    [$breakdown, $totals] = \App\CheckoutUtil::populateTotalsFromOrder($replyOrder);
        // If this is a placement and succeeded, clear cart & set message
    if ($isPlaceOrder && $placedOrderId) {
            // Initiate payment
            $base = \App\CheckoutUtil::baseUrl();
            $pi = \App\CheckoutUtil::initiatePayment($client, $config, $replyOrder, $placedOrderId, $base);
            if (!empty($pi['redirect_url'])) { $_SESSION['cart'] = []; header('Location: '.$pi['redirect_url']); exit; }
            $_SESSION['cart'] = [];
            $paymentIntegrationData = $pi['integration'] ?? null;
            $paymentError = $pi['error'] ?? null;
            $message = $pi['message'] ? ($pi['message'].' Order ID: '.htmlspecialchars((string)$placedOrderId)) : ($message ?? null);
        }
    }
} catch (\Throwable $e) {
    error_log('OrderService::Calculate error: ' . $e->getMessage());
}

// If we're returning from a payment provider (success or failure), complete the payment via unified flow
if ($paymentSuccess || $paymentFailed) {
    // Prefer PSP payment id from common keys; fallback to pretty-route token
    $paymentGatewayId = null;
    foreach (['cko-payment-id', 'id', 'payment_id', 'paymentId'] as $k) {
        if (!empty($_GET[$k])) { $paymentGatewayId = (string)$_GET[$k]; break; }
    }

    // Optional: try to read order session if available
    $orderSession = null;
    foreach (['order_session', 'orderSession', 'session', 'session_id', 'os'] as $k) {
        if (!empty($_GET[$k])) { $orderSession = (string)$_GET[$k]; break; }
    }
    // If not provided via query, use the pretty-route token as order session
    if (!$orderSession && $paymentToken) { $orderSession = $paymentToken; }

    $cp = \App\CheckoutUtil::completePaymentFlow($orderSession, $paymentGatewayId);
    if (empty($cp['error'])) {
        // Redirect to order result page (prefer orderSession; fallback to order_id)
        $oid = $cp['order_id'] ?? null; $os = $cp['order_session'] ?? $orderSession;
        $dest = '/order';
        if ($os) { $dest .= '/session/'.rawurlencode($os); }
        elseif ($oid) { $dest .= '/'.$oid; }
        header('Location: '.$dest);
        exit;
    }
    if (!empty($cp['message'])) { $message = $cp['message']; }
    if (!empty($cp['error'])) { $paymentError = $cp['error']; }
}

// Prepare data for view (options already computed above)

// Provide variables to view
$ajax = isset($_POST['ajax']) ? (bool)$_POST['ajax'] : (isset($_GET['ajax']) ? (bool)$_GET['ajax'] : false);
if($ajax){
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => true,
        'is_place' => $isPlaceOrder,
        'order_id' => $placedOrderId ?? null,
        'breakdown' => $breakdown,
        'payment_integration' => $paymentIntegrationData ?? null,
        'payment_error' => $paymentError ?? null,
        'message' => $message ?? null,
    ]);
    return;
}
return [
    'cartItems' => $cartItems,
    'countries' => $countries,
    'selectedCountry' => $selectedCountry,
    'shippingOptions' => $shippingOptions,
    'paymentOptions' => $paymentOptions,
    'selectedShipping' => $selectedShipping,
    'selectedPayment' => $selectedPayment,
    'customer' => [
    'first_name' => $first_name,
    'last_name' => $last_name,
    'name' => $name,
        'email' => $email,
        'phone' => $phone,
    'city' => $city,
    'state' => $state,
        'address' => $address,
        'zip' => $zip,
    ],
    'totals' => $totals,
    'breakdown' => $breakdown,
    'message' => $message ?? null,
    'placedOrderId' => $placedOrderId ?? null,
    'isPlaceOrder' => $isPlaceOrder,
    'paymentIntegration' => $paymentIntegrationData ?? null,
    'paymentError' => $paymentError ?? null,
];
