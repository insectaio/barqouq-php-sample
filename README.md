# Barqouq PHP Demo

Minimal demo of a PHP shopfront that talks to Barqouq over gRPC.

## Quick Start

This repository already includes Barqouq as a dependency in `composer.json`. To get started:

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

## AWS ECS deploy (starter)

There is a starter script for pushing the image to ECR and updating an existing ECS Fargate service. See [deploy/aws/ecs/README.md](deploy/aws/ecs/README.md) for required environment and steps.

## Notes

- Requires PHP with grpc and protobuf extensions
- This repo is a demo; code and APIs can change

## Integrating Barqouq into your own project

To add Barqouq to your own PHP project, install it via Composer:

```bash
composer require insectaio/barqouq:^1.0
```

Or add to your `composer.json`:

```json
{
  "require": {
    "insectaio/barqouq": "^1.0",
    "insectaio/common": "^1.0"
  }
}
```