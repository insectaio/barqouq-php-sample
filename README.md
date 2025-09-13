# My E-Commerce PHP gRPC App

This is a sample PHP web app that fetches products over gRPC from the Barqouq backend, using generated PHP Protobuf and gRPC client libraries.

---

## Configuration

Create a `.env` file in the root directory with the following keys:

```env
BARQOUQ_GRPC_HOST=api.barqouq.shop:443
BARQOUQ_GRPC_TLS=true
BARQOUQ_SECRET_KEY=your-secret-key
BARQOUQ_SUBDOMAIN=xxx
```

Quick start:

- Copy the example file: `.env.example` → `.env`
- Fill `BARQOUQ_SECRET_KEY` and `BARQOUQ_SUBDOMAIN`
- Leave `BARQOUQ_GRPC_HOST` and `BARQOUQ_GRPC_TLS` as-is unless you have a custom endpoint

Alternatively, you can edit `config/config.php` and hardcode values (not recommended for production).

Note: `.env.example` is the canonical template. `.env.sample` is deprecated and kept only for backward compatibility.

---

## Install & Run Locally

```bash
composer install
# Use the router to enable pretty URLs
php -S localhost:8000 -t public public/router.php
```

Then open: http://localhost:8000/home

---

## Run with Docker

```bash
docker-compose up --build
```

Or with Docker alone:

```bash
docker build -t barqouq-php .
docker run -p 8000:80 --env-file .env barqouq-php
```

---

## Deploy on AWS

[Deploy to AWS](https://console.aws.amazon.com/cloudformation/home?#/stacks/create/template)

This deploys the app using AWS CloudFormation with PHP + Apache + Docker support.

---

## Notes

- PHP gRPC support requires the grpc and protobuf extensions (`pecl install grpc protobuf`)
- The gRPC clients are auto-generated using Buf + PHP plugin
- See: https://buf.build/insecta/common and https://buf.build/insecta/barqouq

---

## Architecture

	- `public/checkout_calculate.php` for AJAX recalculation.
	- `public/checkout.php` renders the page using the controller + View renderer.
- Routing:
	- For local dev, use the PHP built-in server with `public/router.php` to enable pretty URLs.
	- Unused `public/templates/index.php` proxy was removed; templates live under `app/views`.

---
## License


---



### UI theme and utilities

The app uses Tailwind CDN for layout plus a small brand stylesheet at `public/theme.css`.

- Primary color: `#7a527a`
- Global inclusion: the layout `app/views/layout.php` links `/theme.min.css` for all pages (a minified build of `public/theme.css`).
	- For local tweaking, you can switch to `/theme.css` in the layout or implement an env-based toggle.

#### Dev/Prod CSS (optional)
- Current: layout loads the minified stylesheet `/theme.min.css`.
- Option: load `/theme.css` during local development and `/theme.min.css` in production by checking `APP_ENV` in `config/config.php` and branching in the layout.
- Design tokens (CSS variables):
	- `--color-primary`, `--color-primary-600/700/800/50`, `--color-accent`, `--color-contrast`, `--color-muted`
	- `--radius-btn`, `--radius-panel`
	- `--shadow-focus`, `--shadow-hover`
- Buttons (outline, no hover color change):
	- `.btn`, `.btn-primary`, `.btn-sm`
	- Responsive helpers: `.btn-fluid-xs` (full-width under ~380px), `.primary-first-xs` (orders the primary CTA first under ~380px when inside `.cta-row`)
- CTA rows: `.cta-row` aligns action buttons to the right and wraps on narrow screens.
- Panels: `.panel` uses `--radius-panel`; large rounded helpers `.rounded-lg`/`.rounded-xl` are mapped to the same radius for consistency.
- Helpers: `.text-primary`, `.bg-primary`, `.border-primary`
- Badges: `.badge`, `.badge-primary`, and the nav `.count-badge`
- Variant inputs: `.variant-radio` sets consistent accent color for option radios.

Gradients and hover color shifts are intentionally not used to keep the UI minimal.

- `App\CheckoutUtil` helpers you’ll typically use:
	- `buildCartItems($sessionCart, $products)` → normalize cart with price snapshots.
	- `buildOrder($cartItems, $country, $shippingGuid, $paymentGuid, $customer)` → `Barqouq\Shared\Order`.
	- `populateTotalsFromOrder($replyOrder)` → `[breakdown, totals]` for UI.
	- `initiatePayment($client, $config, $replyOrder, $orderId, $baseUrl)` → payment integration info.
	- `completePayment($orderId, $token)` → finalize on success callback.

See `public/checkout_calculate.php` and `public/checkout_place.php` for concrete usage.