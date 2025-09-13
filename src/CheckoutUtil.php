<?php
// src/CheckoutUtil.php - shared helpers for checkout & recalculation endpoints
namespace App;

class CheckoutUtil
{
    /**
     * Lightweight UTF-8 sanitizer that drops invalid bytes.
     * @param mixed $v Any scalar convertible to string
     * @return string UTF-8 sanitized string
     */
    public static function s($v): string
    {
        return iconv('UTF-8', 'UTF-8//IGNORE', (string)$v);
    }

    /**
     * Build absolute base URL from current request.
     * @return string e.g. https://example.com
     */
    public static function baseUrl(): string
    {
        $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http');
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
        return $scheme . '://' . $host;
    }
    /**
     * Build cart items from session cart and product list with pricing snapshot logic.
     * Inputs: sessionCart mixed schema (legacy int qty or structured array), products from ProductService
     * Output: array of normalized cart lines: [product, variant, variant_id, qty, currency, unit_price, total]
     * Notes: Prefers snapshot prices if provided; otherwise falls back to variant then product price.
     */
    /**
     * Normalize session cart into enriched items with product/variant objects and price snapshots.
     * @param array $sessionCart Session cart lines (legacy or structured)
     * @param array $products Product list from ProductService::getProducts()
     * @return array[] Array of lines: [product, variant, variant_id, qty, currency, unit_price, total]
     */
    public static function buildCartItems(array $sessionCart, array $products): array
    {
        $index = [];
        foreach ($products as $p) {
            if (is_object($p) && method_exists($p, 'getProductId')) {
                $index[(int)$p->getProductId()] = $p;
            }
        }
        $cartItems = [];
        foreach ($sessionCart as $key => $line) {
            $pid = null;
            $vid = null;
            $qty = 0;
            $snapCurrency = null;
            $snapUnitPrice = null;
            $snapTotal = null;
            $variantLabel = null;
            if (is_int($key) || ctype_digit((string)$key)) {
                $pid = (int)$key;
                $qty = (int)$line;
            } elseif (is_array($line)) {
                $pid = isset($line['product_id']) ? (int)$line['product_id'] : null;
                $vid = isset($line['variant_id']) ? (int)$line['variant_id'] : null;
                $qty = (int)($line['qty'] ?? 0);
                $snapCurrency = $line['currency'] ?? null;
                $snapUnitPrice = $line['unit_price'] ?? null;
                $snapTotal = $line['total'] ?? null;
                $variantLabel = $line['variant_label'] ?? null;
            }
            if ($pid === null || $qty <= 0) continue;
            $product = $index[$pid] ?? null;
            if (!$product) continue;
            $variantObj = null;
            if ($vid !== null) {
                // Ensure variants loaded
                $needLoad = true;
                if (method_exists($product, 'getVariants')) {
                    foreach ($product->getVariants() as $_v) {
                        $needLoad = false;
                        break;
                    }
                }
                if ($needLoad) {
                    try {
                        $detailed = \ProductService::findProductWithVariants($pid);
                        if ($detailed) $product = $detailed;
                    } catch (\Throwable $e) {
                    }
                }
                if (method_exists($product, 'getVariants')) {
                    foreach ($product->getVariants() as $v) {
                        if ($v instanceof \Barqouq\Shared\Variant && $v->getVariantId() == $vid) {
                            $variantObj = $v;
                            break;
                        }
                    }
                }
            }
            [$currency, $unitPrice] = self::deriveUnitPrice($snapCurrency, $snapUnitPrice, $product, $variantObj);
            $lineTotal = $snapTotal !== null ? $snapTotal : (($unitPrice !== null) ? $unitPrice * $qty : null);
            $cartItems[] = ['product' => $product, 'variant' => $variantObj, 'variant_id' => $vid, 'qty' => $qty, 'currency' => $currency, 'unit_price' => $unitPrice, 'total' => $lineTotal, 'variant_label' => $variantLabel ?? null];
        }
        return $cartItems;
    }

    /**
     * Derive currency & unit price with precedence: snapshot -> variant.price -> product.price
     * Returns [currency(string|null), unitPrice(float|null)]
     */
    /**
     * Derive currency & unit price with precedence: snapshot → variant.price → product.price
     * @return array{0: string|null, 1: float|null} [currency, unitPrice]
     */
    private static function deriveUnitPrice($snapCurrency, $snapUnitPrice, $product, $variant)
    {
        $currency = $snapCurrency;
        $unit = $snapUnitPrice;
        if (($currency === null || $unit === null) && $variant && method_exists($variant, 'getPrice') && $variant->getPrice()) {
            try {
                $vp = $variant->getPrice();
                if ($currency === null && method_exists($vp, 'getCurrencyCode')) $currency = $vp->getCurrencyCode();
                if ($unit === null) {
                    $u = method_exists($vp, 'getUnits') ? $vp->getUnits() : 0;
                    $n = method_exists($vp, 'getNanos') ? $vp->getNanos() : 0;
                    $unit = $u + $n / 1e9;
                }
            } catch (\Throwable $e) {
            }
        }
        if (($currency === null || $unit === null) && $product && method_exists($product, 'getPrice') && $product->getPrice()) {
            try {
                $pp = $product->getPrice();
                if ($currency === null && method_exists($pp, 'getCurrencyCode')) $currency = $pp->getCurrencyCode();
                if ($unit === null) {
                    $u = method_exists($pp, 'getUnits') ? $pp->getUnits() : 0;
                    $n = method_exists($pp, 'getNanos') ? $pp->getNanos() : 0;
                    $unit = $u + $n / 1e9;
                }
            } catch (\Throwable $e) {
            }
        }
        return [$currency, $unit];
    }

    /**
     * Extract (currency,value) from Money proto safely.
     * Returns [currency(string|null), value(float)] or null on failure.
     */
    /**
     * Extract [currency, value] from a Money proto. Returns null on failure.
     * @param mixed $m Money-like object
     * @return array{0: string|null, 1: float}|null
     */
    public static function extractMoney($m): ?array
    {
        if (!$m) return null;
        try {
            $c = method_exists($m, 'getCurrencyCode') ? $m->getCurrencyCode() : null;
            $u = method_exists($m, 'getUnits') ? $m->getUnits() : 0;
            $n = method_exists($m, 'getNanos') ? $m->getNanos() : 0;
            return [$c, $u + $n / 1e9];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Pick the selected GUID from options; if not provided or invalid, pick the first active option.
     * Options shape: [{ guid: string, is_active?: bool, ... }]
     */
    /**
     * Pick selected GUID; fallback to first active option if invalid or empty.
     * @param array $options [{ guid: string, is_active?: bool, ... }]
     * @param string|null $selected Input GUID
     * @return string|null Effective GUID or null when no options exist
     */
    public static function selectGuidOption(array $options, ?string $selected): ?string
    {
        $selectedNorm = $selected !== null ? strtolower(trim($selected)) : '';
        $firstActive = null;
        foreach ($options as $opt) {
            $guid = isset($opt['guid']) ? (string)$opt['guid'] : null;
            if ($guid) {
                if ($firstActive === null && (!isset($opt['is_active']) || $opt['is_active'])) $firstActive = $guid;
                if ($selectedNorm !== '' && strtolower($guid) === $selectedNorm) return $guid;
            }
        }
        return $selectedNorm === '' ? $firstActive : $selected;
    }

    /**
     * Create a Money proto (Insecta\Common\Money) from currency and decimal value.
     */
    /**
     * Create a Money proto from currency and decimal value.
     * @return \Insecta\Common\Money|null
     */
    public static function money(?string $currency, ?float $value): ?\Insecta\Common\Money
    {
        if ($currency === null || $value === null) return null;
        try {
            $m = new \Insecta\Common\Money();
            $units = (int)floor($value);
            $nanos = (int)round(($value - $units) * 1e9);
            if ($nanos >= 1000000000) {
                $units += 1;
                $nanos -= 1000000000;
            }
            $m->setCurrencyCode($currency);
            $m->setUnits($units);
            $m->setNanos($nanos);
            return $m;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Convert normalized cart items to Barqouq\Shared\OrderProduct list, preserving price snapshots.
     */
    /**
     * Convert normalized cart items to Barqouq\Shared\OrderProduct list.
     * @param array $cartItems Lines from buildCartItems
     * @return array OrderProduct[]
     */
    public static function buildOrderProducts(array $cartItems): array
    {
        $ops = [];
        foreach ($cartItems as $item) {
            $op = new \Barqouq\Shared\OrderProduct();
            $productObj = $item['product'] ?? null;
            if ($productObj && method_exists($productObj, 'getProductId')) {
                try {
                    $op->setProductId($productObj->getProductId());
                } catch (\Throwable $e) {
                }
                // Attempt to set product name snapshot
                if (method_exists($productObj, 'getName')) {
                    try {
                        $name = $productObj->getName();
                        if (is_string($name) && $name !== '') $op->setName($name);
                    } catch (\Throwable $e) {
                    }
                }
            }
            if (!empty($item['variant_id'])) $op->setVariantId((int)$item['variant_id']);
            $op->setQuantity((int)($item['qty'] ?? 0));
            $price = self::money($item['currency'] ?? null, $item['unit_price'] ?? null);
            if ($price) $op->setPrice($price);
            $total = self::money($item['currency'] ?? null, $item['total'] ?? null);
            if ($total) $op->setTotal($total);
            $ops[] = $op;
        }
        return $ops;
    }

    /**
     * Compute local subtotal (sum of unit_price * qty) and return [subtotal|null, currency|null].
     */
    /**
     * Compute local subtotal (sum of unit_price * qty) with best-effort currency.
     * @return array{0: float|null, 1: string|null} [subtotal, currency]
     */
    public static function localSubtotal(array $cartItems): array
    {
        $sum = 0.0;
        $cur = null;
        $any = false;
        foreach ($cartItems as $ci) {
            if (isset($ci['unit_price'], $ci['qty']) && $ci['unit_price'] !== null) {
                $sum += (float)$ci['unit_price'] * (int)$ci['qty'];
                $any = true;
                if ($cur === null && !empty($ci['currency'])) $cur = $ci['currency'];
            }
        }
        return [$any ? $sum : null, $cur];
    }

    /**
     * Build an Order proto for checkout.
     */
    /**
     * Build an Order proto for checkout with products, country, options, and customer.
     * @param array $cartItems From buildCartItems
     * @param string $countryCode ISO country code
     * @param string|null $shippingGuid Selected shipping option GUID
     * @param string|null $paymentGuid Selected payment option GUID
     * @param array $customer {name, email, phone, address, city, state?, zip?}
     */
    public static function buildOrder(array $cartItems, string $countryCode, ?string $shippingGuid, ?string $paymentGuid, array $customer): \Barqouq\Shared\Order
    {
        $order = new \Barqouq\Shared\Order();
        $order->setCurrency('AED');
        $order->setPaymentCountryCode(self::s($countryCode));
        $order->setShippingCountryCode(self::s($countryCode));
        if (!empty($cartItems)) {
            $order->setProducts(self::buildOrderProducts($cartItems));
        }
        if ($shippingGuid) {
            $sid = self::s($shippingGuid);
            if (method_exists($order, 'setShippingOptionId')) {
                $order->setShippingOptionId($sid);
            }
            if (method_exists($order, 'setShippingOption')) {
                $order->setShippingOption($sid);
            }
        }
        if ($paymentGuid) {
            $pid = self::s($paymentGuid);
            if (method_exists($order, 'setPaymentOptionId')) {
                $order->setPaymentOptionId($pid);
            }
            if (method_exists($order, 'setPaymentOption')) {
                $order->setPaymentOption($pid);
            }
        }
        // customer fields
        if (method_exists($order, 'setShippingStreet')) {
            $order->setShippingStreet(self::s($customer['address'] ?? ''));
        }
        if (method_exists($order, 'setShippingCityCode')) {
            $order->setShippingCityCode(self::s($customer['city'] ?? ''));
        }
        if (!empty($customer['state']) && method_exists($order, 'setShippingStateCode')) {
            $order->setShippingStateCode(self::s($customer['state']));
        }
        if (method_exists($order, 'setShippingZipCode')) {
            $order->setShippingZipCode(self::s($customer['zip'] ?? ''));
        }
        if (method_exists($order, 'setShippingPhoneNumber')) {
            $order->setShippingPhoneNumber(self::s($customer['phone'] ?? ''));
        }
        if (method_exists($order, 'setPaymentEmail')) {
            $order->setPaymentEmail(self::s($customer['email'] ?? ''));
        }
        // prefer explicit first_name / last_name; fallback: split legacy 'name'
        $first = $customer['first_name'] ?? null;
        $last = $customer['last_name'] ?? null;
        $legacy = $customer['name'] ?? null;
        if (($first === null || $first === '') && is_string($legacy) && $legacy !== '') {
            $parts = preg_split('/\s+/', trim($legacy), 2);
            $first = $parts[0] ?? $legacy;
            $last = $last ?? ($parts[1] ?? '');
        }
        if (method_exists($order, 'setPaymentFirstName')) {
            $order->setPaymentFirstName(self::s($first ?? ''));
        }
        if (method_exists($order, 'setPaymentLastName')) {
            $order->setPaymentLastName(self::s($last ?? ''));
        }
        return $order;
    }

    /**
     * Populate breakdown and totals from reply order.
     * Returns [breakdown(array), totals(array|null)]
     */
    /**
     * Populate breakdown and totals from reply order.
     * @return array{0: array, 1: array|null} [breakdown, totals]
     */
    public static function populateTotalsFromOrder(\Barqouq\Shared\Order $replyOrder): array
    {
        $breakdown = ['currency' => null, 'subtotal' => null, 'shipping_fee' => null, 'payment_fee' => null, 'discount' => null, 'total' => null];
        $totals = null;
        if (method_exists($replyOrder, 'hasTotalAmount') && $replyOrder->hasTotalAmount()) {
            $em = self::extractMoney($replyOrder->getTotalAmount());
            if ($em) {
                [$cur, $val] = $em;
                $totals = ['currency' => $cur, 'units' => (int)floor($val), 'nanos' => (int)round(($val - floor($val)) * 1e9)];
                $breakdown['total'] = $val;
                if ($breakdown['currency'] === null) $breakdown['currency'] = $cur;
            }
        }
        if (method_exists($replyOrder, 'hasSubtotal') && $replyOrder->hasSubtotal()) {
            $em = self::extractMoney($replyOrder->getSubtotal());
            if ($em) {
                [$cur, $val] = $em;
                $breakdown['subtotal'] = $val;
                if ($breakdown['currency'] === null) $breakdown['currency'] = $cur;
            }
        }
        if (method_exists($replyOrder, 'hasShippingFee') && $replyOrder->hasShippingFee()) {
            $em = self::extractMoney($replyOrder->getShippingFee());
            if ($em) {
                [$cur, $val] = $em;
                $breakdown['shipping_fee'] = $val;
                if ($breakdown['currency'] === null) $breakdown['currency'] = $cur;
            }
        }
        if (method_exists($replyOrder, 'hasPaymentFee') && $replyOrder->hasPaymentFee()) {
            $em = self::extractMoney($replyOrder->getPaymentFee());
            if ($em) {
                [$cur, $val] = $em;
                $breakdown['payment_fee'] = $val;
                if ($breakdown['currency'] === null) $breakdown['currency'] = $cur;
            }
        }
        if (method_exists($replyOrder, 'hasDiscount') && $replyOrder->hasDiscount()) {
            $em = self::extractMoney($replyOrder->getDiscount());
            if ($em) {
                [$cur, $val] = $em;
                $breakdown['discount'] = $val;
                if ($breakdown['currency'] === null) $breakdown['currency'] = $cur;
            }
        }
        if ($breakdown['total'] === null) {
            $derived = 0.0;
            $any = false;
            foreach (['subtotal', 'shipping_fee', 'payment_fee'] as $k) {
                if ($breakdown[$k] !== null) {
                    $derived += $breakdown[$k];
                    $any = true;
                }
            }
            if ($breakdown['discount'] !== null) {
                $derived -= $breakdown['discount'];
                $any = true;
            }
            if ($any) {
                $breakdown['total'] = $derived;
            }
        }
        return [$breakdown, $totals];
    }

    /**
     * Payment: initiate
     * Returns ['redirect_url'=>?string,'integration'=>?array,'message'=>?string,'error'=>?array]
     */
    /**
     * Initiate payment for an order; returns redirect URL or integration data with optional message/error.
     * @param mixed $client OrderServiceClient
     * @param array $config App config with barqouq_* keys
     * @param \Barqouq\Shared\Order $replyOrder Placed order reply
     * @param int|string $placedOrderId Order ID
     * @param string $baseUrl Absolute base URL for callbacks
     * @return array{redirect_url:?string,integration:?array,message:?string,error:?array}
     */
    public static function initiatePayment($client, array $config, \Barqouq\Shared\Order $replyOrder, $placedOrderId, string $baseUrl): array
    {
        try {
            $req = new \Barqouq\Shopfront\Order\InitiatePaymentRequest();
            $req->setSubdomain(self::s($config['barqouq_subdomain']));
            $req->setClientKey(self::s($config['barqouq_secret_key']));
            $req->setOrder($replyOrder);
            $req->setSuccessUrl(rtrim($baseUrl, '/') . '/checkout/success');
            $req->setFailureUrl(rtrim($baseUrl, '/') . '/checkout/failure');
            list($integration, $st) = $client->InitiatePayment($req)->wait();
            try {
                error_log('[checkout] InitiatePayment status: ' . json_encode($st));
            } catch (\Throwable $e) {
            }
            if (($st->code ?? 0) === 0 && $integration instanceof \Insecta\Common\PaymentIntegration) {
                $redirect = method_exists($integration, 'getRedirectUrl') ? $integration->getRedirectUrl() : null;
                $data = [
                    'payment_id' => method_exists($integration, 'getPaymentId') ? $integration->getPaymentId() : null,
                    'type' => method_exists($integration, 'getType') ? $integration->getType() : null,
                    'redirect_url' => $redirect,
                    'web_component_session' => method_exists($integration, 'getWebComponentSession') ? $integration->getWebComponentSession() : null,
                    'instructions' => method_exists($integration, 'getInstructions') ? $integration->getInstructions() : null,
                    'public_key' => method_exists($integration, 'getPublicKey') ? $integration->getPublicKey() : null,
                    'payment_option_code' => method_exists($integration, 'getPaymentOptionCode') ? $integration->getPaymentOptionCode() : null,
                ];
                return ['redirect_url' => $redirect, 'integration' => $data, 'message' => $redirect ? null : 'Proceed with payment', 'error' => null];
            }
            return ['redirect_url' => null, 'integration' => null, 'message' => 'Order placed (payment integration unavailable).', 'error' => ['code' => $st->code ?? null, 'details' => $st->details ?? 'init failed']];
        } catch (\Throwable $e) {
            return ['redirect_url' => null, 'integration' => null, 'message' => null, 'error' => ['code' => 'exception', 'details' => $e->getMessage()]];
        }
    }

    /**
     * Payment: complete. Returns ['message'=>?string,'error'=>?array]
     */
    /**
     * Complete a payment after returning from the PSP using token/order id.
     * @return array{message:?string,error:?array}
     */
    public static function completePayment(?int $orderId, ?string $token): array
    {
        try {
            require_once __DIR__ . '/BarqouqClient.php';
            $config = \BarqouqClient::config();
            $client = \BarqouqClient::create(\Barqouq\Shopfront\Order\OrderServiceClient::class);
            $cp = new \Barqouq\Shopfront\Order\CompletePaymentRequest();
            \BarqouqClient::applyAuth($cp);
            if ($orderId) {
                $o = new \Barqouq\Shared\Order();
                $o->setOrderId((int)$orderId);
                $cp->setOrder($o);
            }
            if ($token && method_exists($cp, 'setPaymentGatewayId')) {
                $cp->setPaymentGatewayId((string)$token);
            }
            list($reply, $st) = $client->CompletePayment($cp)->wait();
            try {
                error_log('[checkout_complete] status=' . json_encode(['code' => $st->code ?? null, 'details' => $st->details ?? null]));
            } catch (\Throwable $e) {
            }
            if (($st->code ?? 0) === 0) return ['message' => 'Payment completed successfully.', 'error' => null];
            $code = $st->code ?? null;
            $details = $st->details ?? 'Payment completion failed';
            // Normalize common transient errors
            if (is_string($details) && stripos($details, 'network') !== false) {
                $details = 'NETWORK_ERROR: failed to retrieve payment details from provider. Please refresh this page or try again shortly.';
            }
            return ['message' => null, 'error' => ['code' => $code, 'details' => $details]];
        } catch (\Throwable $e) {
            return ['message' => null, 'error' => ['code' => 'exception', 'details' => $e->getMessage()]];
        }
    }

    /**
     * Find an order by order session using OrderService.Find.
     * @return array{order:?\Barqouq\Shared\Order, error:?array}
     */
    public static function findOrderBySession(string $orderSession): array
    {
        try {
            require_once __DIR__ . '/BarqouqClient.php';
            $client = \BarqouqClient::create(\Barqouq\Shopfront\Order\OrderServiceClient::class);
            $req = new \Barqouq\Shopfront\Order\OrderRequest();
            \BarqouqClient::applyAuth($req);
            if (method_exists($req, 'setOrderSession')) {
                \call_user_func([$req, 'setOrderSession'], self::s($orderSession));
            } else {
                return ['order' => null, 'error' => ['code' => 'unsupported', 'details' => 'Lookup by order session not supported by this client']];
            }
            // Use FindOrderById RPC method only
            if (!method_exists($client, 'FindOrderById')) {
                return ['order' => null, 'error' => ['code' => 'unsupported', 'details' => 'FindOrderById RPC not available']];
            }
            list($reply, $st) = $client->FindOrderById($req)->wait();
            if (($st->code ?? 0) !== 0 || !($reply instanceof \Barqouq\Shared\OrderReply)) {
                return ['order' => null, 'error' => ['code' => $st->code ?? null, 'details' => $st->details ?? 'Find failed']];
            }
            if (method_exists($reply, 'hasOrder') && !$reply->hasOrder()) {
                return ['order' => null, 'error' => ['code' => 'not_found', 'details' => 'Order not found']];
            }
            $order = method_exists($reply, 'getOrder') ? $reply->getOrder() : null;
            return ['order' => $order, 'error' => null];
        } catch (\Throwable $e) {
            return ['order' => null, 'error' => ['code' => 'exception', 'details' => $e->getMessage()]];
        }
    }

    /**
     * Complete payment with optional orderSession lookup and PSP payment id extraction.
     * Mirrors the Go handler behavior.
     * @param int|null $orderId
     * @param string|null $orderSession
     * @param string|null $paymentGatewayId e.g. cko-payment-id or id
     * @return array{message:?string,error:?array}
     */
    public static function completePaymentFlow(?string $orderSession, ?string $paymentGatewayId): array
    {
        try {
            require_once __DIR__ . '/BarqouqClient.php';
            $config = \BarqouqClient::config();
            $client = \BarqouqClient::create(\Barqouq\Shopfront\Order\OrderServiceClient::class);

            // Resolve order via session if provided
            $resolvedOrder = null;
            $orderId = null;
            if ($orderSession) {
                $fo = self::findOrderBySession($orderSession);
                if (empty($fo['error']) && !empty($fo['order'])) {
                    $resolvedOrder = $fo['order'];
                    if (method_exists($resolvedOrder, 'getOrderId')) {
                        try { $orderId = (int)$resolvedOrder->getOrderId(); } catch (\Throwable $e) {}
                    }
                } else if (!empty($fo['error'])) {
                    // If lookup by session is unsupported or not found, proceed using paymentGatewayId only
                    $code = $fo['error']['code'] ?? null;
                    if ($code !== 'unsupported' && $code !== 'not_found') {
                        return ['message' => null, 'error' => $fo['error']];
                    }
                }
            }

            // If order is already processed (not pending payment), short-circuit with success
            if ($resolvedOrder) {
                try {
                    $statusId = null;
                    if (method_exists($resolvedOrder, 'getStatusId')) { $statusId = $resolvedOrder->getStatusId(); }
                    if ($statusId !== null && class_exists('\\Barqouq\\Shared\\OrderStatus') && defined('\\Barqouq\\Shared\\OrderStatus::ORDER_STATUS_PENDING_PAYMENT')) {
                        if ($statusId !== \Barqouq\Shared\OrderStatus::ORDER_STATUS_PENDING_PAYMENT) {
                            return ['message' => 'Payment already processed for this order.', 'error' => null];
                        }
                    }
                } catch (\Throwable $e) {}
            }

            // Build completion request
            $cp = new \Barqouq\Shopfront\Order\CompletePaymentRequest();
            \BarqouqClient::applyAuth($cp);
            if ($orderId) { $o = new \Barqouq\Shared\Order(); $o->setOrderId((int)$orderId); $cp->setOrder($o); }
            if ($paymentGatewayId && method_exists($cp, 'setPaymentGatewayId')) { $cp->setPaymentGatewayId((string)$paymentGatewayId); }
            list($reply, $st) = $client->CompletePayment($cp)->wait();
            // Try to extract order id from reply if available
            try {
                if ($reply instanceof \Barqouq\Shared\OrderReply && method_exists($reply,'hasOrder') && $reply->hasOrder()) {
                    $ro = method_exists($reply,'getOrder') ? $reply->getOrder() : null;
                    if ($ro && method_exists($ro,'getOrderId')) {
                        $rid = (int)$ro->getOrderId();
                        if ($rid && !$orderId) { $orderId = $rid; }
                    }
                }
            } catch (\Throwable $e) {}
            try { error_log('[checkout_complete_flow] status=' . json_encode(['code' => $st->code ?? null, 'details' => $st->details ?? null])); } catch (\Throwable $e) {}
            if (($st->code ?? 0) === 0) return ['message' => 'Payment completed successfully.', 'error' => null, 'order_id' => $orderId, 'order_session' => $orderSession];
            return ['message' => null, 'error' => ['code' => $st->code ?? null, 'details' => $st->details ?? 'Payment completion failed'], 'order_id' => $orderId, 'order_session' => $orderSession];
        } catch (\Throwable $e) {
            return ['message' => null, 'error' => ['code' => 'exception', 'details' => $e->getMessage()]];
        }
    }
}
