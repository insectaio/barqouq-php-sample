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

Alternatively, you can edit `config/config.php` and hardcode values (not recommended for production).

---

## Install & Run Locally

```bash
composer install
php -S localhost:8000 -t public
```

Then open: http://localhost:8000

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

## License

MIT