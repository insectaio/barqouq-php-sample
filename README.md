# Barqouq PHP Demo

Minimal demo of a PHP shopfront that talks to Barqouq over gRPC.

## Quick start

1) Install dependencies

```bash
composer install
```

2) Configure environment

Copy `.env.example` to `.env` and set:

```env
BARQOUQ_GRPC_HOST=api.barqouq.shop:443
BARQOUQ_GRPC_TLS=true
BARQOUQ_SECRET_KEY=your-secret-key
BARQOUQ_SUBDOMAIN=your-subdomain
```

3) Run (built-in server with pretty routes)

```bash
php -S localhost:8000 -t public public/router.php
```

Open http://localhost:8000/home

## Demo routes

- `/home` — list products, add to cart
- `/cart` — review cart
- `/checkout` — place order and pay
- After payment: redirects to `/order/session/{token}` (or `/order/{id}` fallback)

## Docker (optional)

```bash
docker-compose up --build
```

## Notes

- Requires PHP with grpc and protobuf extensions
- This repo is a demo; code and APIs can change