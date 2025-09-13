<?php
// View: order result/confirmation
/** @var array $error */
/** @var \Barqouq\Shared\Order|null $order */
/** @var int|null $orderId */
/** @var string|null $orderSession */
?>
<div class="max-w-3xl mx-auto p-6">
  <h1 class="text-2xl font-bold mb-4">Thank you!</h1>
  <?php if ($order): ?>
    <p class="mb-4">Your order has been received.</p>
  <div class="rounded-lg border p-4 bg-white shadow-sm mb-6">
      <div class="mb-1 text-sm text-gray-600">Order ID</div>
      <div class="text-lg font-semibold"><?php echo htmlspecialchars((string)($order->getOrderId() ?? $orderId)); ?></div>
      <?php
        // Prefer human-readable payment label; skip GUID-like IDs
        $paymentDisplay = null;
        $pmGetters = ['getPaymentOptionName','getPaymentOptionLabel','getPaymentOption'];
        foreach ($pmGetters as $m) {
          if (method_exists($order, $m)) {
            try { $val = call_user_func([$order, $m]); } catch (Throwable $e) { $val = null; }
            if ($val) {
              $valStr = (string)$val;
              if ($m === 'getPaymentOption') {
                if (!preg_match('/^[0-9a-f-]{16,}$/i', $valStr)) { $paymentDisplay = $valStr; }
              } else {
                $paymentDisplay = $valStr;
              }
              if ($paymentDisplay) break;
            }
          }
        }
      ?>
      <?php if ($paymentDisplay): ?>
        <div class="mt-3 text-sm text-gray-700">Payment: <?php echo htmlspecialchars($paymentDisplay); ?></div>
      <?php endif; ?>
      <?php if (method_exists($order,'getPaymentReference') && $order->getPaymentReference()): ?>
        <div class="mt-1 text-sm text-gray-700">Payment ref: <?php echo htmlspecialchars($order->getPaymentReference()); ?></div>
      <?php endif; ?>
      <?php if (method_exists($order,'getCreatedAt') && $order->getCreatedAt()): $ts=$order->getCreatedAt(); $dt=''; try { $sec=method_exists($ts,'getSeconds')?$ts->getSeconds():null; if ($sec!==null) { $dt=date('Y-m-d H:i', (int)$sec); } } catch (Throwable $e) {} ?>
        <div class="mt-1 text-xs text-gray-500">Placed at: <?php echo htmlspecialchars($dt); ?></div>
      <?php endif; ?>
      <?php
        // Prefer human-readable shipping label; skip GUID-like IDs
        $shippingDisplay = null;
        $shipGetters = ['getShippingOptionName','getShippingOptionLabel','getShippingOption'];
        foreach ($shipGetters as $m) {
          if (method_exists($order, $m)) {
            try { $val = call_user_func([$order, $m]); } catch (Throwable $e) { $val = null; }
            if ($val) {
              $valStr = (string)$val;
              if ($m === 'getShippingOption') {
                if (!preg_match('/^[0-9a-f-]{16,}$/i', $valStr)) { $shippingDisplay = $valStr; }
              } else {
                $shippingDisplay = $valStr;
              }
              if ($shippingDisplay) break;
            }
          }
        }
      ?>
      <?php if ($shippingDisplay): ?>
        <div class="mt-3 text-sm text-gray-700">Shipping method: <?php echo htmlspecialchars($shippingDisplay); ?></div>
      <?php endif; ?>
    </div>

    <?php if (method_exists($order,'getShippingStreet')): ?>
      <div class="rounded-lg border p-4 bg-white shadow-sm mb-6">
        <div class="font-semibold mb-2">Shipping address</div>
        <div class="text-sm text-gray-800"><?php echo htmlspecialchars((string)$order->getShippingStreet()); ?></div>
        <div class="text-sm text-gray-800">
          <?php echo htmlspecialchars((string)$order->getShippingCityCode()); ?>
          <?php if ($order->getShippingStateCode()): ?>, <?php echo htmlspecialchars((string)$order->getShippingStateCode()); ?><?php endif; ?>
          <?php if ($order->getShippingZipCode()): ?>, <?php echo htmlspecialchars((string)$order->getShippingZipCode()); ?><?php endif; ?>
        </div>
        <div class="text-sm text-gray-800"><?php echo htmlspecialchars((string)$order->getShippingCountryCode()); ?></div>
        <?php if ($order->getShippingPhoneNumber()): ?>
          <div class="text-sm text-gray-700 mt-1">Phone: <?php echo htmlspecialchars((string)$order->getShippingPhoneNumber()); ?></div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($items)): ?>
      <div class="rounded-lg border p-4 bg-white shadow-sm mb-6">
        <div class="font-semibold mb-3">Items</div>
        <div class="space-y-3">
          <?php foreach ($items as $it): ?>
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm text-gray-800"><?php echo htmlspecialchars($it['name']); ?></div>
                <div class="text-xs text-gray-500">x <?php echo (int)$it['qty']; ?></div>
              </div>
              <div class="text-sm text-gray-700">
                <?php if ($it['total'] !== null): ?>
                  <?php echo htmlspecialchars((string)($it['currency'] ?? '')) . ' ' . number_format((float)$it['total'], 2); ?>
                <?php elseif ($it['unit'] !== null): ?>
                  <?php echo htmlspecialchars((string)($it['currency'] ?? '')) . ' ' . number_format((float)$it['unit'], 2); ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  <?php if (!empty($breakdown['currency'])): $cur = $breakdown['currency']; $fmt = function($v){ return number_format((float)$v, 2); }; ?>
      <div class="rounded-lg border p-4 bg-white shadow-sm">
        <div class="flex items-center justify-between mb-2 text-sm">
          <span class="text-gray-600">Subtotal</span>
          <span class="text-gray-800"><?php echo htmlspecialchars((string)$cur) . ' ' . $fmt($breakdown['subtotal'] ?? 0); ?></span>
        </div>
        <?php if ($breakdown['shipping_fee'] !== null): ?>
        <div class="flex items-center justify-between mb-2 text-sm">
          <span class="text-gray-600">Shipping</span>
          <span class="text-gray-800"><?php echo htmlspecialchars((string)$cur) . ' ' . $fmt($breakdown['shipping_fee']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($breakdown['payment_fee'] !== null): ?>
        <div class="flex items-center justify-between mb-2 text-sm">
          <span class="text-gray-600">Payment fee</span>
          <span class="text-gray-800"><?php echo htmlspecialchars((string)$cur) . ' ' . $fmt($breakdown['payment_fee']); ?></span>
        </div>
        <?php endif; ?>
        <?php if ($breakdown['discount'] !== null): ?>
        <div class="flex items-center justify-between mb-2 text-sm">
          <span class="text-gray-600">Discount</span>
          <span class="text-green-700">- <?php echo htmlspecialchars((string)$cur) . ' ' . $fmt($breakdown['discount']); ?></span>
        </div>
        <?php endif; ?>
        <div class="flex items-center justify-between text-base font-semibold mt-3">
          <span class="text-gray-900">Total</span>
          <span class="text-gray-900"><?php echo htmlspecialchars((string)$cur) . ' ' . $fmt($breakdown['total'] ?? ($breakdown['subtotal'] ?? 0)); ?></span>
        </div>
      </div>
    <?php endif; ?>
  <?php elseif ($error): ?>
    <div class="bg-red-100 text-red-800 px-4 py-3 rounded">Unable to load your order. <?php echo htmlspecialchars((string)($error['details'] ?? '')); ?></div>
  <?php else: ?>
    <p>Order details are not available.</p>
  <?php endif; ?>
  <div class="mt-6">
    <a href="/home" class="btn btn-outline">Continue shopping</a>
  </div>
</div>
