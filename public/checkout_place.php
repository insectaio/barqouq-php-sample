<?php
// public/checkout_place.php - AJAX endpoint to place order & initiate payment
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/ProductService.php';
require_once __DIR__ . '/../src/OrderService.php';
require_once __DIR__ . '/../src/LocationService.php';
require_once __DIR__ . '/../src/CheckoutUtil.php';
require_once __DIR__ . '/../src/BarqouqClient.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'method_not_allowed']);
    exit;
}

$raw = file_get_contents('php://input');
$input = [];
if ($raw) { $j = json_decode($raw, true); if (is_array($j)) $input = $j; }
if (empty($input)) { $input = $_POST; }

$selectedCountry = $input['country'] ?? 'AE';
$selectedShipping = $input['shipping'] ?? null;
$selectedPayment = $input['payment'] ?? null;

$products = (new ProductService())->getProducts();
$cartItems = \App\CheckoutUtil::buildCartItems($_SESSION['cart'] ?? [], $products);
if (empty($cartItems)) {
    echo json_encode(['error' => 'empty_cart']);
    exit;
}

$shippingOptions = OrderService::getShippingOptions($selectedCountry, $cartItems);
$paymentOptions = OrderService::getPaymentOptions($selectedCountry, $cartItems);
$fieldErrors = [];
// Basic server-side validation to prevent invalid submissions
$firstName = trim((string)($input['first_name'] ?? ''));
$lastName = trim((string)($input['last_name'] ?? ''));
$name = trim((string)($input['name'] ?? '')); // legacy fallback
$email = trim((string)($input['email'] ?? ''));
$phone = trim((string)($input['phone'] ?? ''));
$address = trim((string)($input['address'] ?? ''));
$city = trim((string)($input['city'] ?? ''));
$state = trim((string)($input['state'] ?? ''));
$zip = trim((string)($input['zip'] ?? ''));
$country = strtoupper(trim((string)($input['country'] ?? '')));
if ($firstName === '' || mb_strlen($firstName) < 2) { $fieldErrors['first_name'] = 'Please enter your first name.'; }
if ($lastName === '' || mb_strlen($lastName) < 2) { $fieldErrors['last_name'] = 'Please enter your last name.'; }
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $fieldErrors['email'] = 'Please enter a valid email address.'; }
$digitsPhone = preg_replace('/\D+/', '', $phone);
if ($digitsPhone === '' || strlen($digitsPhone) < 7) { $fieldErrors['phone'] = 'Please enter a valid phone number.'; }
if ($address === '' || mb_strlen($address) < 5) { $fieldErrors['address'] = 'Please enter your street address.'; }
if ($city === '' || mb_strlen($city) < 2) { $fieldErrors['city'] = 'Please enter your city.'; }
if ($country === '' || !preg_match('/^[A-Z]{2}$/', $country)) { $fieldErrors['country'] = 'Please select a valid country.'; }
if ($zip !== '' && mb_strlen($zip) < 3) { $fieldErrors['zip'] = 'ZIP/Postal code looks too short.'; }
if (!empty($fieldErrors)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'validation_error', 'message' => 'Validation failed. Please fix the highlighted fields.', 'field_errors' => $fieldErrors]);
    exit;
}
$selectedShipping = \App\CheckoutUtil::selectGuidOption($shippingOptions, $selectedShipping);
$selectedPayment = \App\CheckoutUtil::selectGuidOption($paymentOptions, $selectedPayment);

try {
    $config = \BarqouqClient::config();
    $client = \BarqouqClient::create(\Barqouq\Shopfront\Order\OrderServiceClient::class);
    $request = new \Barqouq\Shopfront\Order\OrderRequest();
    \BarqouqClient::applyAuth($request);
    $customer = [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'zip' => $zip,
    ];
    $order = \App\CheckoutUtil::buildOrder($cartItems, $selectedCountry, $selectedShipping, $selectedPayment, $customer);
    try { error_log('[checkout_place_map] country='.$selectedCountry.' shipping='.$selectedShipping.' payment='.$selectedPayment); } catch(\Throwable $e){}
    $request->setOrder($order);
    list($reply,$status) = $client->Add($request)->wait();
    if (($status->code ?? 0)!==0 || !($reply instanceof \Barqouq\Shared\OrderReply) || !$reply->hasOrder()) {
        $msg = $status->details ?? 'Unable to place order';
        http_response_code(502);
        echo json_encode(['ok'=>false,'error'=>'add_failed','message'=>$msg,'status'=>$status]);
        exit; }
    $replyOrder = $reply->getOrder();
    $orderId = null; if(method_exists($replyOrder,'getOrderId')){ try{ $orderId=$replyOrder->getOrderId(); }catch(\Throwable $e){} }
    // Initiate payment via shared util
    $paymentIntegrationOut = null; $paymentError=null; $message=null;
    $baseUrl = \App\CheckoutUtil::baseUrl();
    $pi = \App\CheckoutUtil::initiatePayment($client, $config, $replyOrder, $orderId, $baseUrl);
    if (!empty($pi['error'])) {
        // Don't clear the cart so the customer can adjust inputs and retry (new order will be created)
        $paymentError = $pi['error'];
        $message = $pi['message'] ?? 'Payment initialization failed. Please review your details and try again.';
    } else {
        $paymentIntegrationOut = $pi['integration'];
        // Clear cart only on successful payment initiation
        $_SESSION['cart']=[];
    }
    echo json_encode([
        'ok'=>true,
        'order_id'=>$orderId,
    'payment_integration'=>$paymentIntegrationOut,
    'payment_error'=>$paymentError,
    'message'=>$message,
    ]);
} catch (\Throwable $e){
    echo json_encode(['error'=>'unexpected','message'=>$e->getMessage()]);
}
