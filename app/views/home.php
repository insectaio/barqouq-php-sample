<?php // Content for home page, used inside layout. Expects $products and optional $error. ?>
<?php if (!empty($error)): ?>
    <div class="max-w-5xl mx-auto mb-6 px-4">
        <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg">
            <div class="font-semibold mb-1">Configuration required</div>
            <div class="text-sm"><?php echo htmlspecialchars($error); ?></div>
            <div class="text-xs mt-2 text-red-700">Set BARQOUQ_GRPC_HOST, BARQOUQ_SECRET_KEY, BARQOUQ_SUBDOMAIN in your .env file, then reload.</div>
        </div>
    </div>
<?php endif; ?>

<div class="max-w-7xl mx-auto px-4 space-y-8">
    <!-- Hero -->
    <section class="panel">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-primary">Browse our products</h1>
                <p class="mt-0.5 text-sm text-gray-600">Simple, tasteful, and aligned with our brand colors.</p>
            </div>
                        <a href="/cart" class="btn btn-primary">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="9" cy="21" r="1"></circle>
                                <circle cx="20" cy="21" r="1"></circle>
                                <path d="M1 1h4l2.68 12.39a2 2 0 0 0 2 1.61h7.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                            </svg>
                            <span class="btn-label">View cart</span>
                        </a>
        </div>
    </section>

    <!-- Products grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
        <?php foreach ($products as $product): ?>
            <?php
                $pid = method_exists($product, 'getProductId') ? $product->getProductId() : null;
                $name = method_exists($product, 'getName') ? $product->getName() : 'Product';
                $img = null;
                if (method_exists($product, 'getImage') && $product->getImage() && method_exists($product->getImage(), 'getUrl')) {
                    $img = $product->getImage()->getUrl();
                }
                $currency = 'USD'; $priceFloat = 0.0;
                if (method_exists($product, 'getPrice') && $product->getPrice()) {
                    $po = $product->getPrice();
                    if (method_exists($po, 'getCurrencyCode')) $currency = $po->getCurrencyCode();
                    $units = method_exists($po, 'getUnits') ? $po->getUnits() : 0;
                    $nanos = method_exists($po, 'getNanos') ? $po->getNanos() : 0;
                    $priceFloat = $units + $nanos / 1e9;
                }
                // Detect variants presence if available on listing object
                $variantCount = 0;
                if (method_exists($product, 'getVariants')) {
                    $vars = $product->getVariants();
                    if (is_array($vars)) { $variantCount = count($vars); }
                    elseif (is_object($vars) && method_exists($vars, 'count')) { $variantCount = count($vars); }
                }
            ?>
                    <div class="panel transition border border-gray-200 rounded-lg p-3 flex flex-col justify-between hover-border-primary relative overflow-hidden">
                                <span class="price-float badge badge-primary" title="Price" data-price-badge-for="<?php echo htmlspecialchars((string)$pid); ?>">
                                    <?php echo htmlspecialchars($currency) . ' ' . number_format($priceFloat, 2); ?>
                                </span>
                        <div class="flex flex-col items-center">
                                <?php if ($img): ?>
                                    <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($name); ?>" class="w-[200px] h-[200px] object-cover rounded-xl mb-3 border border-gray-100" loading="lazy">
                    <?php else: ?>
                        <div class="w-[200px] h-[200px] flex items-center justify-center bg-gray-100 rounded-xl mb-4 text-gray-400 border border-gray-100">No image</div>
                    <?php endif; ?>
                    <div class="text-base font-medium text-gray-900 text-center">
                        <?php echo htmlspecialchars($name); ?>
                    </div>
                                <?php if ($variantCount > 0): ?>
                                    <div class="mt-2"><span class="pill" title="Customizable">Customizable</span></div>
                                <?php endif; ?>
                </div>
                        <div class="mt-3 flex items-center gap-2">
                            <form method="post" action="/cart.php" class="flex-1" data-quick-add data-product-id="<?php echo htmlspecialchars((string)$pid); ?>" data-product-name="<?php echo htmlspecialchars($name); ?>" data-has-variants="<?php echo $variantCount>0 ? '1':'0'; ?>">
                                <input type="hidden" name="action" value="add" />
                                <input type="hidden" name="product_id" value="<?php echo htmlspecialchars((string)$pid); ?>" />
                                <button type="submit" class="btn btn-primary" data-loading-btn>
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="9" cy="21" r="1"></circle>
                                        <circle cx="20" cy="21" r="1"></circle>
                                        <path d="M1 1h4l2.68 12.39a2 2 0 0 0 2 1.61h7.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                    </svg>
                                    <span class="btn-label"><?php echo $variantCount>0 ? 'Choose options' : 'Add to cart'; ?></span>
                                    <span class="spinner hidden" aria-hidden="true"></span>
                                </button>
                            </form>
                        </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- end main content container -->
</div>

<!-- Variant modal moved outside to avoid space-y margins -->
<div id="variantModal" class="hidden fixed inset-0 z-50" aria-hidden="true">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative w-full h-full flex items-start md:items-center justify-center p-4 overflow-y-auto">
        <div class="panel w-full max-w-lg" style="margin:0;">
            <div class="flex items-start justify-between">
                <h2 class="text-lg font-medium text-gray-900" id="variantTitle">Choose options</h2>
                <button class="text-gray-500" data-close-variant aria-label="Close">✕</button>
            </div>
            <p class="mt-1 text-sm text-gray-600" id="variantSubtitle"></p>
            <div id="variantGroupsContainer" class="mt-4 space-y-5" data-variant-groups>
                <!-- Dynamic variant groups with radio buttons will be injected here -->
                <div class="text-sm text-gray-500" data-groups-loading>Loading…</div>
            </div>
            <div class="mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-700">Price:</div>
                <div class="text-base font-semibold text-primary" id="variantPrice">—</div>
            </div>
            <div class="mt-4 flex items-center gap-2">
                <button class="btn btn-primary" data-close-variant>Cancel</button>
                <form method="post" action="/cart.php" id="variantForm" class="flex-1">
                    <input type="hidden" name="action" value="add" />
                    <input type="hidden" name="product_id" id="variantProductId" />
                    <input type="hidden" name="variant_id" id="variantIdInput" />
                    <input type="hidden" name="variant_label" id="variantLabelInput" />
                    <button type="submit" class="btn btn-primary" id="variantAddBtn" disabled data-loading-btn>
                        <span class="btn-label">Add to cart</span>
                        <span class="spinner hidden" aria-hidden="true"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Ensure radios use primary accent color consistently */
    input[type="radio"].accent-primary { accent-color: #6d4e85; }
    input[type="radio"].accent-primary:focus { outline: 2px solid #6d4e85; outline-offset:2px; }
    /* Explicitly remove any accidental margins from modal root/panel */
    #variantModal, #variantModal .panel { margin:0 !important; }
</style>
<script>
        async function refreshCartCount(){
            try {
                const res = await fetch('/cart_count.php', { credentials: 'same-origin' });
                if(!res.ok) return;
                const j = await res.json();
                if (j && typeof j.count === 'number' && window.updateCartCount) {
                    window.updateCartCount(j.count);
                }
            } catch(e){}
        }
        // Quick add: if product has variants, open modal; else AJAX add
    const variantModal = document.getElementById('variantModal');
    const variantProductIdInput = document.getElementById('variantProductId');
        (function setupQuickAdd(){
            const variantPresenceCache = new Map(); // pid -> boolean
            async function hasVariants(pid){
                if (variantPresenceCache.has(pid)) return variantPresenceCache.get(pid);
                try {
                    const res = await fetch(`/product_variants.php?product_id=${encodeURIComponent(pid)}`);
                    const data = await res.json();
                    const list = Array.isArray(data.variants) ? data.variants : [];
                    const present = list.length > 0;
                    variantPresenceCache.set(pid, present);
                    return present;
                } catch(e){ return false; }
            }
            document.querySelectorAll('[data-quick-add]').forEach(form => {
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const pid = form.getAttribute('data-product-id');
                    const pname = form.getAttribute('data-product-name') || 'Choose options';
                    const btn = form.querySelector('[data-loading-btn]');
                    if (btn) { btn.disabled = true; btn.querySelector('.btn-label').textContent = 'Adding…'; btn.querySelector('.spinner')?.classList.remove('hidden'); }
                    // Decide path by probing variants API
                    const hasVar = await hasVariants(pid);
                    if (hasVar) {
                        // Open modal and stop here
                        if (btn) { btn.disabled = false; btn.querySelector('.btn-label').textContent = 'Add to cart'; btn.querySelector('.spinner')?.classList.add('hidden'); }
                        openVariantModalForProduct(pid, pname);
                        return;
                    }
                    // No variants: proceed to add directly
                    const fd = new FormData(form);
                    try {
                        const res = await fetch('/cart.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                        if (!res.ok) {
                            try { const j = await res.json(); window.showToast && window.showToast(j.message || 'Failed to add to cart', 'error'); } catch(_) { window.showToast && window.showToast('Failed to add to cart', 'error'); }
                            return;
                        }
                        await refreshCartCount();
                        window.showToast && window.showToast('Added to cart', 'success');
                    } catch (err) {
                        window.showToast && window.showToast('Failed to add to cart', 'error');
                    } finally {
                        if (btn) { btn.disabled = false; btn.querySelector('.btn-label').textContent = 'Add to cart'; btn.querySelector('.spinner')?.classList.add('hidden'); }
                    }
                });
            });
        })();

    // Variant modal logic (simple select)
    const modal = document.getElementById('variantModal');
    const titleEl = document.getElementById('variantTitle');
    const subtitleEl = document.getElementById('variantSubtitle');
    const priceEl = document.getElementById('variantPrice');
    const groupsContainer = document.getElementById('variantGroupsContainer');
    const productIdInput = document.getElementById('variantProductId');
    const variantIdInput = document.getElementById('variantIdInput');
    const variantLabelInput = document.getElementById('variantLabelInput');
    const addBtn = document.getElementById('variantAddBtn');

    function openModal(){ modal.classList.remove('hidden'); modal.setAttribute('aria-hidden', 'false'); }
    function closeModal(){ modal.classList.add('hidden'); modal.setAttribute('aria-hidden', 'true'); }

    async function openVariantModalForProduct(id, name){
        titleEl.textContent = name || 'Choose options';
        subtitleEl.textContent = 'Select a variant';
        productIdInput.value = id;
        priceEl.textContent = '—';
        addBtn.disabled = true;
        variantIdInput.value = '';
        if (variantLabelInput) variantLabelInput.value = '';
        groupsContainer.innerHTML = '<div class="text-sm text-gray-500" data-groups-loading>Loading…</div>';
        try {
            const res = await fetch(`/product_variants.php?product_id=${encodeURIComponent(id)}`);
            const data = await res.json();
            const variants = Array.isArray(data.variants) ? data.variants : [];
            if (!variants.length) {
                groupsContainer.innerHTML = '<div class="text-sm text-gray-500">No variants found</div>';
            } else {
                // Determine dynamic group labels (attribute names) and collect unique value names.
                // group_name / group2_name are headers (e.g., "Color", "Size"). name / name2 hold the selected value (e.g., "Red", "Large").
                const groupLabel1 = variants.find(v => v.group_name)?.group_name || 'Option 1';
                const hasGroup2 = variants.some(v => v.group2_name);
                const groupLabel2 = hasGroup2 ? (variants.find(v => v.group2_name)?.group2_name || 'Option 2') : null;
                // No special color rendering; radios use primary accent.

                const attr1Values = []; // values come from variant.name
                const attr2Values = []; // values come from variant.name2
                const seenVal1 = new Set();
                const seenVal2 = new Set();
                variants.forEach(v => {
                    const val1 = v.name || 'Value';
                    if(!seenVal1.has(val1)) { seenVal1.add(val1); attr1Values.push(val1); }
                    if (hasGroup2) {
                        const val2 = v.name2 || 'Value';
                        if(!seenVal2.has(val2)) { seenVal2.add(val2); attr2Values.push(val2); }
                    }
                });
                // Build combination map keyed by value names (not headers) => stable resolution
                const comboMap = new Map(); // key val1||val2 -> variant
                variants.forEach(v => {
                    const key = (v.name || 'Value') + '||' + (hasGroup2 ? (v.name2 || '') : '');
                    if(!comboMap.has(key) || (v.stock>0 && !(comboMap.get(key).stock>0))) {
                        comboMap.set(key, v);
                    }
                });
                groupsContainer.innerHTML = '';
                // Build attribute 1 radios
                const section1 = document.createElement('div');
                section1.className='space-y-2';
                const h1 = document.createElement('div'); h1.className='text-sm font-medium text-gray-800'; h1.textContent = groupLabel1; section1.appendChild(h1);
                section1.appendChild(buildAttributeRadioGroup(attr1Values, 'attr1'));
                groupsContainer.appendChild(section1);
                // Attribute 2 if present
                if (hasGroup2){
                    const section2 = document.createElement('div'); section2.className='space-y-2';
                    const h2 = document.createElement('div'); h2.className='text-sm font-medium text-gray-800'; h2.textContent = groupLabel2 || 'Option 2';
                    section2.appendChild(h2);
                    section2.appendChild(buildAttributeRadioGroup(attr2Values, 'attr2'));
                    groupsContainer.appendChild(section2);
                }
                // Handlers
                function currentSelection(){
                    const a1 = groupsContainer.querySelector('input[name="attr1"]:checked')?.value || '';
                    const a2 = hasGroup2 ? (groupsContainer.querySelector('input[name="attr2"]:checked')?.value || '') : '';
                    return {a1,a2};
                }
                function resolveVariant(sel){
                    const key = sel.a1 + '||' + (hasGroup2 ? sel.a2 : '');
                    return comboMap.get(key) || null;
                }
                function updateVariantFromAttributes(){
                    const sel = currentSelection();
                    // Build availability maps for each first attr => set of second attr values and vice versa
                    if (hasGroup2){
                        const a1ToA2 = new Map();
                        const a2ToA1 = new Map();
                        comboMap.forEach(v => {
                            const keyParts = [...comboMap.keys()].find(k => comboMap.get(k) === v).split('||');
                        });
                        // Instead of iterating values twice, iterate variants array
                        variants.forEach(v => {
                            const v1 = v.name || 'Value';
                            const v2 = v.name2 || '';
                            const set12 = a1ToA2.get(v1) || new Set(); set12.add(v2); a1ToA2.set(v1,set12);
                            const set21 = a2ToA1.get(v2) || new Set(); set21.add(v1); a2ToA1.set(v2,set21);
                        });
                        // Filter second group based on first selection (if any)
                        const sel1 = sel.a1;
                        const validA2 = sel1 ? (a1ToA2.get(sel1) || new Set()) : null;
                        groupsContainer.querySelectorAll('input[name="attr2"]').forEach(r => {
                            const enable = !validA2 || validA2.has(r.value);
                            r.disabled = !enable;
                            r.parentElement.classList.toggle('opacity-50', r.disabled);
                        });
                        const cur2 = groupsContainer.querySelector('input[name="attr2"]:checked');
                        if (cur2 && cur2.disabled){ cur2.checked = false; }
                        // Filter first group based on second selection (if any)
                        const sel2 = groupsContainer.querySelector('input[name="attr2"]:checked')?.value || '';
                        const validA1 = sel2 ? (a2ToA1.get(sel2) || new Set()) : null;
                        groupsContainer.querySelectorAll('input[name="attr1"]').forEach(r => {
                            const enable = !validA1 || validA1.has(r.value);
                            r.disabled = !enable;
                            r.parentElement.classList.toggle('opacity-50', r.disabled);
                        });
                        const cur1 = groupsContainer.querySelector('input[name="attr1"]:checked');
                        if (cur1 && cur1.disabled){ cur1.checked = false; }
                    }
                    // Only resolve variant if (no second group) OR (both groups selected)
                    const complete = hasGroup2 ? (sel.a1 && sel.a2) : sel.a1;
                    if(!complete){
                        addBtn.disabled = true; priceEl.textContent='—'; variantIdInput.value=''; if(variantLabelInput) variantLabelInput.value=''; return;
                    }
                    const variant = resolveVariant(sel);
                    if (!variant){ addBtn.disabled=true; priceEl.textContent='—'; variantIdInput.value=''; if(variantLabelInput) variantLabelInput.value=''; return; }
                    const labelParts = [variant.name, variant.name2, variant.name3].filter(Boolean);
                    const fullLabel = labelParts.join(' / ') || ('Variant #'+variant.variant_id);
                    variantIdInput.value = variant.variant_id;
                    if(variantLabelInput) variantLabelInput.value = fullLabel;
                    const currency = variant.price_currency || '';
                    const price = typeof variant.price==='number'?variant.price:null;
                    if(currency && typeof price==='number'){ priceEl.textContent = `${currency} ${price.toFixed(2)}`; updateProductCardBadge(productIdInput.value,currency,price); }
                    else { priceEl.textContent='—'; }
                    addBtn.disabled = false;
                }
                // Attach listeners
                groupsContainer.querySelectorAll('input[name="attr1"]').forEach(r=> r.addEventListener('change', updateVariantFromAttributes));
                if (hasGroup2){ groupsContainer.querySelectorAll('input[name="attr2"]').forEach(r=> r.addEventListener('change', updateVariantFromAttributes)); }
                // Preselect first values
                const firstAttr1 = groupsContainer.querySelector('input[name="attr1"]'); if(firstAttr1) firstAttr1.checked = true;
                const firstAttr2 = hasGroup2 ? groupsContainer.querySelector('input[name="attr2"]') : null; if(firstAttr2) firstAttr2.checked = true;
                updateVariantFromAttributes();
            }
        } catch (e) {
            groupsContainer.innerHTML = '<div class="text-sm text-red-600">Failed to load variants</div>';
        }
        openModal();
    }

    function buildAttributeRadioGroup(values, name){
        const wrap = document.createElement('div');
        wrap.className='flex flex-wrap gap-2';
        values.forEach(val => {
            const label = document.createElement('label');
            label.className='flex items-center gap-2 border rounded-lg px-3 py-1 cursor-pointer text-sm hover-border-primary';
            const r = document.createElement('input'); r.type='radio'; r.name=name; r.value=val; r.className='accent-primary';
            label.appendChild(r);
            const span = document.createElement('span'); span.textContent=val; label.appendChild(span);
            wrap.appendChild(label);
        });
        return wrap;
    }

    document.querySelectorAll('[data-close-variant]').forEach(el => el.addEventListener('click', closeModal));
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

        // Variant add via AJAX to avoid page jump
        const variantForm = document.getElementById('variantForm');
        variantForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = variantForm.querySelector('[data-loading-btn]');
            if (btn) { btn.disabled = true; btn.querySelector('.btn-label').textContent = 'Adding…'; btn.querySelector('.spinner')?.classList.remove('hidden'); }
            try {
                const fd = new FormData(variantForm);
                const res = await fetch('/cart.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                // Optimistically bump cart count by 1
                await refreshCartCount();
                window.showToast && window.showToast('Added to cart', 'success');
                closeModal();
            } catch (_) {
                window.showToast && window.showToast('Failed to add to cart', 'error');
            } finally {
                if (btn) { btn.disabled = false; btn.querySelector('.btn-label').textContent = 'Add to cart'; btn.querySelector('.spinner')?.classList.add('hidden'); }
            }
        });

            // Update product card price badge helper
            function updateProductCardBadge(productId, currency, price){
                try{
                    const el = document.querySelector(`[data-price-badge-for="${CSS.escape(String(productId))}"]`);
                    if (!el) return;
                    if (currency && typeof price === 'number' && !Number.isNaN(price)) {
                        el.textContent = `${currency} ${price.toFixed(2)}`;
                    }
                }catch(e){}
            }

            // On load, for products with variants, fetch first variant price and show it on the card
            (function initVariantPrices(){
                const seen = new Set();
                document.querySelectorAll('[data-quick-add]').forEach(form => {
                    const pid = form.getAttribute('data-product-id');
                    if (!pid || seen.has(pid)) return; seen.add(pid);
                    fetch(`/product_variants.php?product_id=${encodeURIComponent(pid)}`)
                        .then(r=>r.json())
                        .then(data => {
                            const vars = Array.isArray(data.variants) ? data.variants : [];
                            if (!vars.length) return;
                            // pick first in-stock else first
                            const first = vars.find(v => v && v.stock>0) || vars[0];
                            if (!first) return;
                            const cur = first.price_currency || '';
                            const p = typeof first.price === 'number' ? first.price : null;
                            if (cur && typeof p === 'number') updateProductCardBadge(pid, cur, p);
                        }).catch(()=>{});
                });
            })();
</script>