<?php // Content for cart page (used inside layout). Expects $cartItems. ?>
<div class="max-w-3xl mx-auto panel">
    <h1 class="text-2xl section-title mb-6 text-center text-primary">Your Cart</h1>
    <?php if (empty($cartItems)): ?>
        <p class="text-center text-gray-500">Your cart is empty.</p>
    <?php else: ?>
        <table class="min-w-full divide-y divide-gray-200 mb-6">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Unit Price</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Total</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">Quantity</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                <?php foreach ($cartItems as $item): ?>
                <tr>
                    <td class="px-4 py-3 text-gray-800 font-medium">
                        <?php echo htmlspecialchars($item['product']->getName()); ?>
                        <?php if (!empty($item['variant']) && $item['variant'] instanceof \Barqouq\Shared\Variant): ?>
                            <?php
                                $parts = [];
                                foreach (['getName','getName2','getName3'] as $nm) {
                                    if (method_exists($item['variant'], $nm)) { $val = $item['variant']->{$nm}(); if ($val) $parts[] = $val; }
                                }
                                $variantLabel = count($parts) ? implode(' / ', $parts) : ('#'.$item['variant']->getVariantId());
                            ?>
                            <div class="text-xs text-gray-500">Variant: <?php echo htmlspecialchars($variantLabel); ?></div>
                        <?php elseif (!empty($item['variant_label'])): ?>
                            <div class="text-xs text-gray-500">Variant: <?php echo htmlspecialchars($item['variant_label']); ?></div>
                        <?php elseif (!empty($item['variant_id'])): ?>
                            <div class="text-xs text-gray-400">Variant ID: <?php echo htmlspecialchars($item['variant_id']); ?> (details unavailable)</div>
                        <?php endif; ?>
                    </td>
                    <?php
                        // Determine display unit price & currency (prefer snapshot -> variant -> product)
                        $dispCurrency = $item['currency'] ?? null;
                        $dispUnit = $item['unit_price'] ?? null;
                        if (($dispCurrency === null || $dispUnit === null) && !empty($item['variant']) && $item['variant'] instanceof \Barqouq\Shared\Variant && method_exists($item['variant'], 'getPrice') && $item['variant']->getPrice()) {
                            try {
                                $vp = $item['variant']->getPrice();
                                $dispCurrency = $dispCurrency ?? (method_exists($vp,'getCurrencyCode') ? $vp->getCurrencyCode() : null);
                                if ($dispUnit === null) {
                                    $u = method_exists($vp,'getUnits') ? $vp->getUnits() : 0;
                                    $n = method_exists($vp,'getNanos') ? $vp->getNanos() : 0;
                                    $dispUnit = $u + $n/1e9;
                                }
                            } catch (\Throwable $e) {}
                        }
                        if (($dispCurrency === null || $dispUnit === null) && method_exists($item['product'],'getPrice') && $item['product']->getPrice()) {
                            try {
                                $pp = $item['product']->getPrice();
                                $dispCurrency = $dispCurrency ?? (method_exists($pp,'getCurrencyCode') ? $pp->getCurrencyCode() : null);
                                if ($dispUnit === null) {
                                    $u = method_exists($pp,'getUnits') ? $pp->getUnits() : 0;
                                    $n = method_exists($pp,'getNanos') ? $pp->getNanos() : 0;
                                    $dispUnit = $u + $n/1e9;
                                }
                            } catch (\Throwable $e) {}
                        }
                        $lineTotal = $item['total'] ?? (($dispUnit !== null && isset($item['qty'])) ? $dispUnit * $item['qty'] : null);
                    ?>
            <td class="px-4 py-3">
                        <?php if ($dispCurrency !== null && $dispUnit !== null): ?>
                <span class="badge badge-primary">
                                <?php echo htmlspecialchars($dispCurrency) . ' ' . number_format($dispUnit, 2); ?>
                            </span>
                        <?php else: ?>
                            <span class="text-xs text-gray-400">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <?php if ($lineTotal !== null && $dispCurrency !== null): ?>
                            <span class="badge badge-primary">
                                <?php echo htmlspecialchars($dispCurrency) . ' ' . number_format($lineTotal, 2); ?>
                            </span>
                        <?php else: ?>
                            <span class="text-xs text-gray-400">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block bg-gray-200 text-gray-700 px-3 py-1 rounded-full mr-2">
                            <?php echo $item['qty']; ?>
                        </span>
                        <form method="post" action="/cart" class="inline">
                            <input type="hidden" name="product_id" value="<?php echo $item['product']->getProductId(); ?>">
                            <?php if (!empty($item['variant']) && $item['variant'] instanceof \Barqouq\Shared\Variant): ?>
                                <input type="hidden" name="variant_id" value="<?php echo $item['variant']->getVariantId(); ?>">
                            <?php elseif (!empty($item['variant_id'])): ?>
                                <input type="hidden" name="variant_id" value="<?php echo htmlspecialchars($item['variant_id']); ?>">
                            <?php endif; ?>
                            <input type="hidden" name="action" value="remove">
                            <button type="submit" class="btn btn-primary">Remove</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <div class="mt-8 cta-row">
            <a href="/checkout" class="btn btn-primary btn-fluid-xs primary-first-xs" data-loading-btn>
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 12.39a2 2 0 0 0 2 1.61h7.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                <span class="btn-label">Proceed to Checkout</span>
                <span class="spinner hidden" aria-hidden="true"></span>
            </a>
        </div>
    <?php endif; ?>
</div>
