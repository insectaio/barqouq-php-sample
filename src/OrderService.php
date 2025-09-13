<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/BarqouqClient.php';

use Barqouq\Shopfront\Order\OrderServiceClient;
use Barqouq\Shopfront\Order\OrderRequest;

class OrderService
{
    /** Convert values to safe UTF-8 */
    private static function s($v): string { return \App\CheckoutUtil::s($v); }

    /** Build a minimal Order proto for options lookup (country + products snapshot) */
    private static function buildOrderForOptions(string $countryCode, array $cartItems): \Barqouq\Shared\Order {
        $order = new \Barqouq\Shared\Order();
        $order->setCurrency('AED');
        $cc = self::s($countryCode);
        if (method_exists($order, 'setShippingCountryCode')) { $order->setShippingCountryCode($cc); }
        if (method_exists($order, 'setPaymentCountryCode')) { $order->setPaymentCountryCode($cc); }
        if (!empty($cartItems)) { $order->setProducts(\App\CheckoutUtil::buildOrderProducts($cartItems)); }
        return $order;
    }

    public static function getShippingOptions($countryCode = 'US', $cartItems = []): array
    {
    $config = \BarqouqClient::config();
    $client = \BarqouqClient::create(OrderServiceClient::class);
        $request = new OrderRequest();
    \BarqouqClient::applyAuth($request);
        $request->setOrder(self::buildOrderForOptions((string)$countryCode, (array)$cartItems));
        list($reply, $status) = $client->ListShippingOptions($request)->wait();
        // Log status to PHP error log instead of printing to page output
        try {
            error_log('OrderService::ListShippingOptions status: ' . json_encode([
                'code' => $status->code ?? null,
                'details' => $status->details ?? null,
            ]));
        } catch (\Throwable $e) {
            // swallow logging errors
        }
        if (($status->code ?? 0) !== 0) {
            return [];
        }
        $options = [];
        if (method_exists($reply, 'getShippingOptions')) {
            $iter = call_user_func([$reply, 'getShippingOptions']);
            if (is_iterable($iter)) {
                foreach ($iter as $option) {
                    if (!is_object($option)) { continue; }
                    $options[] = [
                        'guid' => method_exists($option,'getGuid') ? $option->getGuid() : '',
                        'name' => method_exists($option,'getName') ? $option->getName() : '',
                        'code' => method_exists($option,'getCode') ? $option->getCode() : '',
                        'fee' => method_exists($option,'getFee') ? $option->getFee() : null,
                        'is_active' => method_exists($option,'getIsActive') ? $option->getIsActive() : false,
                    ];
                }
            }
        }
        return $options;
    }

    public static function getPaymentOptions($countryCode = 'US', $cartItems = []): array
    {
    $config = \BarqouqClient::config();
    $client = \BarqouqClient::create(OrderServiceClient::class);
        $request = new OrderRequest();
    \BarqouqClient::applyAuth($request);
        $request->setOrder(self::buildOrderForOptions((string)$countryCode, (array)$cartItems));
        list($reply, $status) = $client->ListPaymentOptions($request)->wait();
        try {
            error_log('OrderService::ListPaymentOptions status: ' . json_encode([
                'code' => $status->code ?? null,
                'details' => $status->details ?? null,
            ]));
        } catch (\Throwable $e) {
            // swallow logging errors
        }
        if (($status->code ?? 0) !== 0) {
            return [];
        }
        $options = [];
        if (method_exists($reply, 'getPaymentOptions')) {
            $iter = call_user_func([$reply, 'getPaymentOptions']);
            if (is_iterable($iter)) {
                foreach ($iter as $option) {
                    if (!is_object($option)) { continue; }
                    $guid = method_exists($option, 'getGuid') ? $option->getGuid() : '';
                    $name = method_exists($option, 'getName') ? $option->getName() : '';
                    $code = method_exists($option, 'getCode') ? $option->getCode() : '';
                    $fee = method_exists($option, 'getFee') ? $option->getFee() : null;
                    $isActive = method_exists($option, 'getIsActive') ? $option->getIsActive() : false;
                    $options[] = [
                        'guid' => $guid,
                        'name' => $name,
                        'code' => $code,
                        'fee' => $fee,
                        'is_active' => $isActive,
                    ];
                }
            }
        }
        return $options;
    }
}
