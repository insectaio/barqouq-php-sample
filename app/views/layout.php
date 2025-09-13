<?php
// app/views/layout.php - base layout template
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title><?php echo htmlspecialchars($title ?? 'Barqouq Shop'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Dev: /theme.css -->
    <link rel="stylesheet" href="/theme.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="site-nav px-4 py-3 mb-8">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="/home" class="flex items-center gap-2">
                    <span class="inline-block w-6 h-6 rounded bg-primary" aria-hidden="true"></span>
                    <span class="font-semibold">Barqouq Shop</span>
                </a>
                <a href="/home" class="hidden sm:inline">Home</a>
                <a href="/checkout" class="hidden sm:inline">Checkout</a>
            </div>
            <?php
            // Compute cart count from session
            $__cartCount = 0;
            if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $__line) { if (is_array($__line) && !empty($__line['qty'])) { $__cartCount += (int)$__line['qty']; } }
            }
            ?>
                <a href="/cart" class="btn btn-primary">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 12.39a2 2 0 0 0 2 1.61h7.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    <span class="btn-label">Cart</span>
                    <span id="navCartCount" class="count-badge <?php echo $__cartCount ? '' : 'hidden'; ?>"><?php echo (int)$__cartCount; ?></span>
                </a>
        </div>
    </nav>
        <main class="max-w-5xl mx-auto px-4 pb-16">
        <?php echo $content ?? ''; ?>
    </main>
    <footer class="mt-10 border-t border-gray-200">
        <div class="max-w-3xl mx-auto px-4 py-6 text-sm text-gray-500 flex items-center justify-between">
            <span>&copy; <?php echo date('Y'); ?> Barqouq Shop</span>
            <span class="hidden sm:inline">Crafted with <span class="text-primary">♥</span></span>
        </div>
    </footer>
        <!-- Toast root -->
        <div id="toastRoot" class="fixed bottom-4 right-4 space-y-2 z-50"></div>

        <script>
            // Simple toast utility
            window.showToast = function(message, type = 'info'){
                const root = document.getElementById('toastRoot');
                const el = document.createElement('div');
                const base = 'shadow panel border flex items-center gap-3';
                const styles = type === 'error' ? 'border-red-200 text-red-800 bg-white' : type === 'success' ? 'border-green-200 text-green-800 bg-white' : 'border-gray-200 text-gray-800 bg-white';
                el.className = base + ' ' + styles + ' toast';
                el.innerHTML = `<div class="text-sm">${message}</div>`;
                root.appendChild(el);
                setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateY(6px)'; }, 2600);
                setTimeout(() => { el.remove(); }, 3000);
            };
            // Update nav cart count badge
            window.updateCartCount = function(count){
                const el = document.getElementById('navCartCount');
                if (!el) return;
                const n = Number(count || 0);
                if (n > 0) { el.textContent = String(n); el.classList.remove('hidden'); }
                else { el.textContent = '0'; el.classList.add('hidden'); }
            };
        </script>
</body>
</html>
