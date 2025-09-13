<?php // checkout content (used inside layout)
?>
<div class="panel">
    <h1 class="text-2xl font-bold mb-6 text-center text-primary">Checkout</h1>
        <div id="inline-error" class="hidden bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded mb-4 text-sm shadow">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="font-semibold mb-1">Error</div>
                    <div id="inline-error-body" class="break-words"></div>
                    <div id="inline-error-order" class="mt-1 text-xs text-red-700 hidden"></div>
                </div>
                <div class="shrink-0">
                    <button id="inline-error-try" type="button" class="btn btn-primary">Try again</button>
                </div>
            </div>
        </div>
        <?php if (!empty($paymentError) && is_array($paymentError)): ?>
            <script>
                (function(){
                    const box = document.getElementById('inline-error');
                    const body = document.getElementById('inline-error-body');
                    if(box && body){ box.classList.remove('hidden'); body.textContent = <?php echo json_encode((string)($paymentError['details'] ?? ($paymentError['code'] ?? 'Unknown error')), JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>; }
                })();
            </script>
        <?php endif; ?>
        <?php if (!empty($message)): ?>
            <div class="bg-green-100 text-green-800 px-4 py-3 rounded mb-6 text-center font-semibold shadow"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if (empty($cartItems)): ?>
            <p class="text-center text-gray-500">Your cart is empty.</p>
        <?php else: ?>
            <form method="post" class="space-y-6" novalidate>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First name</label>
                        <input type="text" value="<?php echo htmlspecialchars($customer['first_name'] ?? ''); ?>" class="block w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 border-primary focus:ring-2" id="first_name" name="first_name" required aria-describedby="first_name-error">
                        <p id="first_name-error" class="mt-1 text-xs text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last name</label>
                        <input type="text" value="<?php echo htmlspecialchars($customer['last_name'] ?? ''); ?>" class="block w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 border-primary focus:ring-2" id="last_name" name="last_name" required aria-describedby="last_name-error">
                        <p id="last_name-error" class="mt-1 text-xs text-red-600 hidden"></p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" class="block w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 border-primary focus:ring-2" id="email" name="email" required aria-describedby="email-error">
                        <p id="email-error" class="mt-1 text-xs text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" class="block w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 border-primary focus:ring-2" id="phone" name="phone" required aria-describedby="phone-error">
                        <p id="phone-error" class="mt-1 text-xs text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                        <input type="text" value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>" class="block w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 border-primary focus:ring-2" id="city" name="city" required aria-describedby="city-error">
                        <p id="city-error" class="mt-1 text-xs text-red-600 hidden"></p>
                    </div>
                </div>
                <div>
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <input type="text" value="<?php echo htmlspecialchars($customer['address'] ?? ''); ?>" class="block w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 border-primary focus:ring-2" id="address" name="address" required aria-describedby="address-error">
                    <p id="address-error" class="mt-1 text-xs text-red-600 hidden"></p>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="zip" class="block text-sm font-medium text-gray-700 mb-1">ZIP / Postal Code</label>
                        <input type="text" value="<?php echo htmlspecialchars($customer['zip'] ?? ''); ?>" class="auto-recalc block w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 border-primary focus:ring-2" id="zip" name="zip" required aria-describedby="zip-error">
                        <p id="zip-error" class="mt-1 text-xs text-red-600 hidden"></p>
                    </div>
                    <div>
                        <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                        <select id="country" name="country" class="auto-recalc-change block w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 border-primary focus:ring-2" required aria-describedby="country-error">
                            <?php foreach ($countries as $country): ?>
                                <option value="<?php echo htmlspecialchars($country['code']); ?>" <?php echo ($country['code'] === $selectedCountry) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($country['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p id="country-error" class="mt-1 text-xs text-red-600 hidden"></p>
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State / Province</label>
                        <input type="text" value="<?php echo htmlspecialchars($customer['state'] ?? ''); ?>" class="auto-recalc block w-full border border-gray-300 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 border-primary focus:ring-2" id="state" name="state" placeholder="State / Province" aria-describedby="state-error">
                        <p id="state-error" class="mt-1 text-xs text-red-600 hidden"></p>
                    </div>
                    <div></div>
                </div>
                <div id="shipping-section">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Shipping Method</label>
                    <div class="grid gap-3" id="shipping-options">
                        <?php if (empty($shippingOptions) || !is_array($shippingOptions)): ?>
                            <div class="text-sm text-gray-500">No shipping options available</div>
                        <?php else: ?>
                            <?php
                                $activeShipping = array_values(array_filter($shippingOptions, function($o){
                                    return isset($o['is_active']) ? (bool)$o['is_active'] : true;
                                }));
                                if (empty($activeShipping)) {
                            ?>
                                <div class="text-sm text-gray-500">No active shipping options available</div>
                            <?php } else { foreach ($activeShipping as $option):
                                $optId = isset($option['id']) ? (string)$option['id'] : (isset($option['guid']) ? (string)$option['guid'] : '');
                                $optName = isset($option['name']) ? (string)$option['name'] : '';
                                $optPrice = 0.0; $optCurrency='';
                                if (isset($option['price']) && is_numeric($option['price'])) { $optPrice=(float)$option['price']; }
                                elseif (isset($option['fee'])) { $fee=$option['fee']; if (is_numeric($fee)) { $optPrice=(float)$fee; } elseif (is_array($fee) && isset($fee['units'])) { $units=$fee['units']??0; $nanos=$fee['nanos']??0; $optPrice=$units+$nanos/1e9; $optCurrency=$fee['currency_code']??''; } elseif (is_object($fee)) { try { if(method_exists($fee,'getUnits')){ $units=$fee->getUnits()??0; $nanos=$fee->getNanos()??0; $optPrice=$units+$nanos/1e9; } if(method_exists($fee,'getCurrencyCode')) $optCurrency=$fee->getCurrencyCode()??''; } catch(\Throwable $e){} } }
                                $checked = ($optId === ($selectedShipping ?? ''));
                            ?>
                <label class="relative flex items-center p-4 border rounded-xl cursor-pointer transition group <?php echo $checked? 'border-primary bg-gray-50':'border-gray-200'; ?> shipping-option-card">
                                <input type="radio" name="shipping" value="<?php echo htmlspecialchars($optId); ?>" class="sr-only auto-recalc-change" <?php echo $checked? 'checked':''; ?>>
                                <div class="flex-1">
                    <div class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($optName); ?></div>
                                    <div class="text-xs text-gray-500 mt-0.5"><?php echo htmlspecialchars((string)$optCurrency) . ' ' . number_format($optPrice,2); ?></div>
                                </div>
                                <div class="ml-3">
                                    <span class="w-4 h-4 inline-block rounded-full border-2 <?php echo $checked? 'border-primary bg-primary':'border-gray-300'; ?>"></span>
                                </div>
                            </label>
                            <?php endforeach; } ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="payment-section">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                    <div class="grid gap-3" id="payment-options">
                        <?php if (empty($paymentOptions) || !is_array($paymentOptions)): ?>
                            <div class="text-sm text-gray-500">No payment options available</div>
                        <?php else: ?>
                            <?php $activePayments = array_values(array_filter($paymentOptions, function($p){ return isset($p['is_active']) ? (bool)$p['is_active'] : true; }));
                            if (empty($activePayments)) { ?>
                                <div class="text-sm text-gray-500">No active payment options available</div>
                            <?php } else { foreach ($activePayments as $p):
                                $pid = isset($p['id']) ? (string)$p['id'] : (isset($p['guid']) ? (string)$p['guid'] : '');
                                $pname = isset($p['name']) ? (string)$p['name'] : '';
                                $checked = ($pid === ($selectedPayment ?? ''));
                            ?>
                <label class="relative flex items-center p-4 border rounded-xl cursor-pointer transition group <?php echo $checked? 'border-primary bg-gray-50':'border-gray-200'; ?> payment-option-card">
                                <input type="radio" name="payment" value="<?php echo htmlspecialchars($pid); ?>" class="sr-only auto-recalc-change" <?php echo $checked? 'checked':''; ?>>
                                <div class="flex-1">
                    <div class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($pname); ?></div>
                                </div>
                                <div class="ml-3">
                                    <span class="w-4 h-4 inline-block rounded-full border-2 <?php echo $checked? 'border-primary bg-primary':'border-gray-300'; ?>"></span>
                                </div>
                            </label>
                            <?php endforeach; } ?>
                        <?php endif; ?>
                    </div>
                </div>
                <h3 class="text-lg font-semibold mt-6 mb-2">Order Summary</h3>
                <ul class="divide-y divide-gray-100 mb-6" id="order-items">
                    <?php foreach ($cartItems as $item): 
                        $prodName = '';
                        $variantName = '';
                        $currency = $item['currency'] ?? null;
                        $unitPrice = $item['unit_price'] ?? null;
                        $qty = $item['qty'] ?? 0;
                        if (isset($item['product']) && is_object($item['product'])) {
                            try { $prodName = $item['product']->getName() ?? ''; } catch (\Throwable $e) { $prodName = ''; }
                        }
                        if ($currency === null || $unitPrice === null) {
                            // Fallback to variant price then product price
                            if (!empty($item['variant']) && is_object($item['variant']) && method_exists($item['variant'], 'getPrice') && $item['variant']->getPrice()) {
                                try {
                                    $vPrice = $item['variant']->getPrice();
                                    if ($currency === null && method_exists($vPrice, 'getCurrencyCode')) { $currency = $vPrice->getCurrencyCode(); }
                                    if ($unitPrice === null) {
                                        $units = method_exists($vPrice, 'getUnits') ? $vPrice->getUnits() : 0;
                                        $nanos = method_exists($vPrice, 'getNanos') ? $vPrice->getNanos() : 0;
                                        $unitPrice = $units + $nanos / 1e9;
                                    }
                                } catch (\Throwable $e) {}
                            }
                            if (($currency === null || $unitPrice === null) && isset($item['product']) && is_object($item['product']) && method_exists($item['product'], 'getPrice') && $item['product']->getPrice()) {
                                try {
                                    $pPrice = $item['product']->getPrice();
                                    if ($currency === null && method_exists($pPrice, 'getCurrencyCode')) { $currency = $pPrice->getCurrencyCode(); }
                                    if ($unitPrice === null) {
                                        $units = method_exists($pPrice, 'getUnits') ? $pPrice->getUnits() : 0;
                                        $nanos = method_exists($pPrice, 'getNanos') ? $pPrice->getNanos() : 0;
                                        $unitPrice = $units + $nanos / 1e9;
                                    }
                                } catch (\Throwable $e) {}
                            }
                        }
                        if (!empty($item['variant']) && is_object($item['variant'])) {
                            // Build variant name from available name fields
                            try {
                                $parts = [];
                                foreach (['getName','getName2','getName3'] as $nm) {
                                    if (method_exists($item['variant'], $nm)) {
                                        $val = $item['variant']->{$nm}();
                                        if ($val !== null && $val !== '') { $parts[] = $val; }
                                    }
                                }
                                $variantName = implode(' / ', $parts);
                            } catch (\Throwable $e) {}
                        } elseif (!empty($item['variant_id'])) {
                            $variantName = 'Variant #' . (int)$item['variant_id'];
                        }
                        $lineTotal = $item['total'] ?? (($unitPrice !== null) ? $unitPrice * $qty : null);
                        if ($currency === null) { $currency = ''; }
                        if ($unitPrice === null) { $unitPrice = 0.0; }
                        if ($lineTotal === null) { $lineTotal = 0.0; }
                    ?>
                        <li class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                            <div class="flex-1">
                                <div class="font-medium text-gray-800"><?php echo htmlspecialchars((string)$prodName); ?></div>
                                <?php if ($variantName !== ''): ?>
                                    <div class="text-xs text-gray-500"><?php echo htmlspecialchars($variantName); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full">x <?php echo (int)$qty; ?></span>
                                <span class="text-sm text-gray-600">@ <?php echo htmlspecialchars((string)$currency) . ' ' . number_format($unitPrice, 2); ?></span>
                                <span class="badge badge-primary font-semibold min-w-[90px] text-center"><?php echo htmlspecialchars((string)$currency) . ' ' . number_format($lineTotal, 2); ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php
                    $bd = $breakdown ?? null;
                    if (is_array($bd) && ($bd['subtotal'] !== null || $bd['total'] !== null)):
                        $cur = $bd['currency'] ?? '';
                        $fmt = function($v){ return number_format((float)$v, 2); };
                ?>
                <div id="breakdown" class="rounded-xl border border-gray-200 p-5 bg-white space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars((string)$cur) . ' ' . ($bd['subtotal'] !== null ? $fmt($bd['subtotal']) : '0.00'); ?></span>
                    </div>
                    <?php if ($bd['shipping_fee'] !== null): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Shipping</span>
                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars((string)$cur) . ' ' . $fmt($bd['shipping_fee']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($bd['payment_fee'] !== null): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Payment Fee</span>
                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars((string)$cur) . ' ' . $fmt($bd['payment_fee']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($bd['discount'] !== null && $bd['discount'] > 0): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Discount</span>
                        <span class="font-medium text-green-600">- <?php echo htmlspecialchars((string)$cur) . ' ' . $fmt($bd['discount']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="border-t border-gray-200 pt-2 mt-1 flex justify-between items-center">
                        <span class="text-sm font-semibold text-gray-700">Total</span>
                        <span class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars((string)$cur) . ' ' . $fmt($bd['total'] ?? ($bd['subtotal'] ?? 0)); ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <div class="pt-4 cta-row">
                    <button type="submit" name="action" value="recalc" class="btn btn-primary btn-fluid-xs">
                        <span class="btn-label">Recalculate</span>
                    </button>
                    <button type="submit" name="action" value="place" class="btn btn-primary btn-fluid-xs primary-first-xs">
                        <span class="btn-label">Place Order</span>
                    </button>
                </div>
                <?php if(!empty($placedOrderId)): ?>
                    <div class="mt-6 p-4 rounded-xl bg-green-50 border border-green-300 text-green-800 text-sm font-medium">
                        Order placed successfully. ID: <span class="font-semibold"><?php echo htmlspecialchars((string)$placedOrderId); ?></span>
                    </div>
                    <?php if(!empty($paymentIntegration) && is_array($paymentIntegration)): ?>
                        <?php
                            $redirectUrl = $paymentIntegration['redirect_url'] ?? '';
                            $instructions = $paymentIntegration['instructions'] ?? '';
                        ?>
                        <script src="https://checkout-web-components.checkout.com/index.js"></script>
                        <script>
                            (function(){
                                var redirectUrl = <?php echo json_encode($redirectUrl, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
                                var instructions = <?php echo json_encode($instructions, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>;
                                function run(){
                                    try {
                                        if(redirectUrl){
                                            window.location.replace(redirectUrl);
                                        } else if(instructions){
                                            var fc = document.getElementById('flow-container');
                                            if(!fc){ fc = document.createElement('div'); fc.id='flow-container'; document.body.appendChild(fc); }
                                            eval(instructions);
                                        }
                                    } catch(e){ console.error('Payment integration handling failed', e); }
                                }
                                if(typeof CheckoutWebComponents === 'undefined'){
                                    // wait a tick for script load
                                    var retry = 0; var timer = setInterval(function(){
                                        if(typeof CheckoutWebComponents !== 'undefined' || retry>20){ clearInterval(timer); run(); }
                                        retry++;
                                    }, 100);
                                } else { run(); }
                            })();
                        </script>
                    <?php endif; ?>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
    <div id="checkout-loading" class="hidden fixed inset-0 bg-white/70 backdrop-blur-sm z-50 flex flex-col items-center justify-center space-y-4">
    <div class="w-12 h-12 border-4 border-primary border-t-transparent rounded-full animate-spin"></div>
        <div class="text-sm font-medium text-gray-700">Recalculating...</div>
    </div>
    <div id="payment-overlay" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
            <h2 class="text-lg font-semibold mb-4">Secure Payment</h2>
            <div id="flow-container" class="min-h-[200px]"></div>
            <div class="mt-4 flex flex-col space-y-3">
                <div id="payment-overlay-close" class="text-center text-xs text-gray-400">Do not close this window until payment completes.</div>
                <button type="button" id="payment-cancel" class="btn btn-primary">Cancel Payment</button>
            </div>
        </div>
    </div>
    <script>
    // Global loader helpers (may be redefined inside IIFE); ensure existence for AJAX order placement
    if(typeof window.showLoading === 'undefined'){
        window.showLoading = function(){ var l=document.getElementById('checkout-loading'); if(l) l.classList.remove('hidden'); };
    }
    if(typeof window.hideLoading === 'undefined'){
        window.hideLoading = function(){ var l=document.getElementById('checkout-loading'); if(l) l.classList.add('hidden'); };
    }
    (function(){
        const form = document.querySelector('form');
        if(!form) return;
        const loading = document.getElementById('checkout-loading');
        const shippingOptionsEl = document.getElementById('shipping-options');
        const paymentOptionsEl = document.getElementById('payment-options');
        const itemsEl = document.getElementById('order-items');
        const breakdownEl = document.getElementById('breakdown');
        const errorBarId = 'checkout-error-bar';
        let typingTimer = null; const debounceMs = 500;
        let requestSeq = 0; // concurrency guard

        function showLoading(){ if(loading) loading.classList.remove('hidden'); }
        function hideLoading(){ if(loading) loading.classList.add('hidden'); }
        function showError(msg){
            let bar = document.getElementById(errorBarId);
            if(!bar){
                bar = document.createElement('div');
                bar.id = errorBarId;
                bar.className='fixed top-4 left-1/2 -translate-x-1/2 bg-red-600 text-white px-4 py-2 rounded shadow z-50 text-sm';
                document.body.appendChild(bar);
            }
            bar.textContent = msg || 'Recalculation failed';
            bar.classList.remove('hidden');
            setTimeout(()=>{ bar.classList.add('hidden'); }, 4000);
            // also show inline persistent error
            try{
                const ibox = document.getElementById('inline-error');
                const ibody = document.getElementById('inline-error-body');
                if(ibox && ibody){ ibody.textContent = msg || 'Unexpected error'; ibox.classList.remove('hidden'); }
            }catch(e){}
        }

        function serializeForm(){
            const data = new FormData(form);
            const obj = {};
            data.forEach((v,k)=>{ obj[k]=v; });
            return obj;
        }

        function formatPrice(cur, val){
            if(val===null || val===undefined) return cur+' 0.00';
            return (cur||'') + ' ' + Number(val).toFixed(2);
        }

        function renderOptions(container, list, name, selected){
            if(!container) return;
            if(!Array.isArray(list) || list.length===0){
                container.innerHTML = '<div class="text-sm text-gray-500">No '+name+' options available</div>';
                return;
            }
            container.innerHTML = list.map(o=>{
                const price = (o.price!=null)? `${o.currency? o.currency+' ':''}${o.price.toFixed(2)}`:'';
                const checked = (o.id === selected);
              const activeCls = checked? 'border-primary bg-gray-50':'border-gray-200';
                return `<label class="relative flex items-center p-4 border rounded-xl cursor-pointer transition group ${activeCls} ${name}-option-card">`+
                       `<input type="radio" name="${name}" value="${o.id}" class="sr-only auto-recalc-change" ${checked? 'checked':''} ${o.is_active? '' : 'disabled'}>`+
                       `<div class="flex-1">`+
                  `<div class="text-sm font-medium text-gray-800">${escapeHtml(o.name||'')}</div>`+
                       (price? `<div class="text-xs text-gray-500 mt-0.5">${escapeHtml(price)}</div>`:'')+
                       `</div>`+
                       `<div class="ml-3"><span class="w-4 h-4 inline-block rounded-full border-2 ${checked? 'border-primary bg-primary':'border-gray-300'}"></span></div>`+
                       `</label>`;
            }).join('');
            updateOptionCardStyles(name);
        }

    function updateOptionCardStyles(name){
            const cards = document.querySelectorAll('label.'+name+'-option-card');
            cards.forEach(card=>{
                const input = card.querySelector('input[type=radio]');
                if(!input) return;
                const bubble = card.querySelector('span.inline-block');
                if(input.checked){
            card.classList.add('border-primary');
            card.classList.add('bg-gray-50');
            card.classList.remove('border-gray-200');
            if(bubble){ bubble.classList.add('border-primary','bg-primary'); bubble.classList.remove('border-gray-300'); }
                } else {
            card.classList.remove('border-primary','bg-gray-50');
                    card.classList.add('border-gray-200');
            if(bubble){ bubble.classList.remove('border-primary','bg-primary'); bubble.classList.add('border-gray-300'); }
                }
            });
        }

        function renderItems(container, items){
            if(!container) return;
            container.innerHTML = (items||[]).map(it=>{
                const variant = it.variant? `<div class=\"text-xs text-gray-500\">${escapeHtml(it.variant)}</div>`:'';
                return `<li class=\"py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2\"><div class=\"flex-1\"><div class=\"font-medium text-gray-800\">${escapeHtml(it.name||'')}</div>${variant}</div><div class=\"flex items-center space-x-2\"><span class=\"bg-gray-200 text-gray-700 px-3 py-1 rounded-full\">x ${it.qty}</span><span class=\"text-sm text-gray-600\">@ ${formatPrice(it.currency,it.unit_price)}</span><span class=\"badge badge-primary font-semibold min-w-[90px] text-center\">${formatPrice(it.currency,it.total)}</span></div></li>`;
            }).join('');
        }

        function renderBreakdown(el, bd){
            if(!el) return; if(!bd){ el.innerHTML=''; return; }
            const cur = bd.currency||'';
            function row(lbl,val,extraCls=''){ if(val==null) return ''; return `<div class=\"flex justify-between text-sm\"><span class=\"text-gray-600\">${lbl}</span><span class=\"font-medium text-gray-800 ${extraCls}\">${formatPrice(cur,val)}</span></div>`; }
            const discountRow = (bd.discount!=null && bd.discount>0)? `<div class=\"flex justify-between text-sm\"><span class=\"text-gray-600\">Discount</span><span class=\"font-medium text-green-600\">- ${formatPrice(cur,bd.discount)}</span></div>`:'';
            el.innerHTML = `${row('Subtotal',bd.subtotal)}${row('Shipping',bd.shipping_fee)}${row('Payment Fee',bd.payment_fee)}${discountRow}<div class=\"border-t border-gray-200 pt-2 mt-1 flex justify-between items-center\"><span class=\"text-sm font-semibold text-gray-700\">Total</span><span class=\"text-lg font-bold text-gray-900\">${formatPrice(cur, bd.total ?? bd.subtotal ?? 0)}</span></div>`;
        }

        function escapeHtml(str){ return (str||'').replace(/[&<>"']/g, s=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"})[s]); }

        function attachDynamicListeners(){
            // Re-bind after DOM updates
            document.querySelectorAll('.auto-recalc-change').forEach(el=>{
                if(!el.dataset._bound){
                    el.addEventListener('change', triggerImmediate);
                    el.dataset._bound = '1';
                }
            });
        }

        function triggerImmediate(){ recalc(); }
        function triggerDebounced(){ if(typingTimer) clearTimeout(typingTimer); typingTimer = setTimeout(recalc, debounceMs); }

        function recalc(){
            const seq = ++requestSeq; // capture request sequence
            showLoading();
            const payload = serializeForm();
            payload.ajax = '1';
            fetch('/checkout/calculate', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) })
                .then(r=> r.ok? r.json(): Promise.reject(new Error('HTTP '+r.status)))
                .then(data=>{
                    if(seq !== requestSeq) return; // stale response
                    if(data.error){ showError(data.message||data.error); return; }
                    renderOptions(shippingOptionsEl, data.shipping_options,'shipping', data.selected_shipping);
                    renderOptions(paymentOptionsEl, data.payment_options,'payment', data.selected_payment);
                    renderItems(itemsEl, data.items);
                    renderBreakdown(breakdownEl, data.breakdown);
                    attachDynamicListeners();
                })
                .catch(err=>{ if(seq === requestSeq) showError(err.message); })
                .finally(()=>{ if(seq === requestSeq) hideLoading(); });
        }

        // Initial binding
        document.querySelectorAll('.auto-recalc-change').forEach(el=> el.addEventListener('change', e=>{ triggerImmediate(); updateOptionCardStyles('shipping'); updateOptionCardStyles('payment'); }));
        document.querySelectorAll('.auto-recalc').forEach(el=>{
            el.addEventListener('input', triggerDebounced);
            el.addEventListener('blur', recalc);
        });
        // Hide loader once static page loaded
    window.addEventListener('load', ()=> { hideLoading(); updateOptionCardStyles('shipping'); updateOptionCardStyles('payment'); });
    })();
    </script>
    <script>
    (function(){
        const form = document.querySelector('form');
        if(!form) return;
    // Remember last created order id when payment initiation fails
    let lastOrderId = null;
        // Cancel payment handler (overlay button)
        const cancelBtn = document.getElementById('payment-cancel');
        if(cancelBtn){
            cancelBtn.addEventListener('click', function(){
                try { const ov = document.getElementById('payment-overlay'); if(ov) ov.classList.add('hidden'); } catch(e){}
                window.location.href = '/home';
            });
        }
        form.addEventListener('submit', function(e){
            const action = (e.submitter && e.submitter.value) ? e.submitter.value : null;
            if(action === 'place'){
                e.preventDefault();
                placeOrderAjax();
            }
        });
        // Try again button triggers place order flow
        (function(){
            const btn = document.getElementById('inline-error-try');
            if(!btn) return;
            btn.addEventListener('click', function(){
                const placeBtn = form.querySelector('button[type="submit"][value="place"]');
                if (placeBtn) { placeBtn.click(); }
                else { placeOrderAjax(); }
            });
        })();
        function clearFieldErrors(){
            ['first_name','last_name','email','phone','address','city','state','zip','country'].forEach(id=>{
                const err = document.getElementById(id+'-error'); if(err){ err.textContent=''; err.classList.add('hidden'); }
                const input = document.getElementById(id); if(input){ input.classList.remove('border-red-500','ring-red-500'); }
            });
        }
        function showFieldErrors(fieldErrors){
            let firstEl = null;
            Object.keys(fieldErrors||{}).forEach(k=>{
                const msg = fieldErrors[k];
                const err = document.getElementById(k+'-error'); if(err){ err.textContent = msg; err.classList.remove('hidden'); }
                const input = document.getElementById(k); if(input){ input.classList.add('border-red-500','ring-red-500'); if(!firstEl) firstEl=input; }
            });
            if(firstEl){ try{ firstEl.focus(); firstEl.scrollIntoView({behavior:'smooth', block:'center'}); }catch(e){} }
        }
        function validateClient(payload){
            const errs={};
            const first=(payload.first_name||'').trim(); if(first.length<2) errs.first_name='Please enter your first name.';
            const last=(payload.last_name||'').trim(); if(last.length<2) errs.last_name='Please enter your last name.';
            const email=(payload.email||'').trim(); if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) errs.email='Please enter a valid email address.';
            const digits=(payload.phone||'').replace(/\D+/g,''); if(digits.length<7) errs.phone='Please enter a valid phone number.';
            const address=(payload.address||'').trim(); if(address.length<5) errs.address='Please enter your street address.';
            const city=(payload.city||'').trim(); if(city.length<2) errs.city='Please enter your city.';
            const country=(payload.country||'').trim(); if(!/^[A-Za-z]{2}$/.test(country)) errs.country='Please select a valid country.';
            if(payload.zip && (payload.zip+'').trim().length>0 && (payload.zip+'').trim().length<3) errs.zip='ZIP/Postal code looks too short.';
            return errs;
        }
        function disableForm(disabled){
            form.querySelectorAll('input,select,button,textarea').forEach(el=>{ el.disabled = !!disabled; });
        }
        function placeOrderAjax(){
            // Collect data BEFORE disabling (disabled inputs are skipped by FormData)
            const fd = new FormData(form);
            const payload = {};
            fd.forEach((v,k)=>{ payload[k]=v; });
            // Ensure currently selected radio values explicitly captured
            const ship = form.querySelector('input[name="shipping"]:checked');
            const pay = form.querySelector('input[name="payment"]:checked');
            if(ship) payload.shipping = ship.value;
            if(pay) payload.payment = pay.value;
            payload.action='place';
            console.log('[checkout_place_payload]', payload);
            // Client-side validation first
            clearFieldErrors();
            const clientErrs = validateClient(payload);
            if(Object.keys(clientErrs).length){ showFieldErrors(clientErrs); return; }
            disableForm(true); showLoading();
            fetch('/checkout/place', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) })
              .then(r=> r.ok? r.json(): r.json().catch(()=>({error:'HTTP_'+r.status})).then(j=>Promise.reject(j)))
              .then(data=>{
                  if(!data.ok){
                      const msg = data.message || data.error || 'Order failed';
                      if(data.field_errors){ showFieldErrors(data.field_errors); }
                      throw new Error(msg);
                  }
                  if(data.payment_integration){
                      const pi = data.payment_integration;
                      if(pi.redirect_url){ window.location.replace(pi.redirect_url); return; }
                      if(pi.instructions){
                          const overlay = document.getElementById('payment-overlay');
                          if(overlay) overlay.classList.remove('hidden');
                          let fc = document.getElementById('flow-container');
                          if(!fc){ fc = document.createElement('div'); fc.id='flow-container'; document.body.appendChild(fc); }
                          function run(){ try { eval(pi.instructions); } catch(e){ console.error('Eval failed', e); } }
                          if(typeof CheckoutWebComponents === 'undefined'){
                              const s = document.createElement('script'); s.src='https://checkout-web-components.checkout.com/index.js'; s.onload=run; s.onerror=function(){ console.error('Payment component script failed to load'); }; document.head.appendChild(s);
                          } else { run(); }
                      }
                  } else if (data.payment_error) {
                      // Handle scenario where order added but payment initiation failed (e.g., VALIDATION_ERROR from provider)
                      const pe = data.payment_error || {};
                      const msg = (data.message || pe.details || pe.code || 'Payment error').toString();
                      clearFieldErrors();
                      // Try to map common validation messages to fields for better UX
                      const mapped = (function mapValidation(msg){
                          const m = msg.toLowerCase();
                          const fe = {};
                          if(m.includes('first') && m.includes('name')) fe.first_name = 'Please enter your first name.';
                          if(m.includes('last') && m.includes('name')) fe.last_name = 'Please enter your last name.';
                          if(!fe.first_name && !fe.last_name && m.includes('name')) { fe.first_name='Please enter your first name.'; fe.last_name='Please enter your last name.'; }
                          if(m.includes('email') && (m.includes('required')||m.includes('invalid'))) fe.email = 'Please enter a valid email address.';
                          if(m.includes('phone') && (m.includes('required')||m.includes('invalid'))) fe.phone = 'Please enter a valid phone number.';
                          if(m.includes('address') && (m.includes('required')||m.includes('invalid'))) fe.address = 'Please enter your street address.';
                          if(m.includes('city') && (m.includes('required')||m.includes('invalid'))) fe.city = 'Please enter your city.';
                          if(m.includes('country') && (m.includes('required')||m.includes('invalid'))) fe.country = 'Please select a valid country.';
                          if(m.includes('zip') || m.includes('postal')) fe.zip = 'ZIP/Postal code looks invalid.';
                          return fe;
                      })(msg);
                      if(Object.keys(mapped).length){ showFieldErrors(mapped); }
                      disableForm(false);
                      hideLoading();
                      // capture last order id and display it inline for support
                      lastOrderId = data.order_id || null;
                      try{
                          const ord = document.getElementById('inline-error-order');
                          if (ord){
                              ord.textContent = lastOrderId ? ('Order ID: ' + String(lastOrderId)) : '';
                              ord.classList.toggle('hidden', !lastOrderId);
                          }
                      }catch(e){}
                      showError(msg + ' — You can update your details and try again. A new order will be created.');
                  }
              })
              .catch(err=>{ console.error(err); disableForm(false); hideLoading(); showError(typeof err==='string'? err : (err.message||'Order failed')); })
              .finally(()=> hideLoading());
        }
    })();
    </script>
