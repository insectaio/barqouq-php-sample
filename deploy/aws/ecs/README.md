# AWS ECS Fargate deploy (starter)

This is a minimal, opinionated starter to build/push the PHP demo image and update an existing ECS Fargate service fronted by an ALB. You must already have an ECS cluster, service, VPC/subnets, security groups, and (optionally) an ALB target group wired to the service. The script only builds, pushes, and updates the task definition revision.

## Prerequisites
- Docker and AWS CLI installed and authenticated (with permissions to ECR, ECS, CloudWatch Logs).
- Existing ECS Fargate cluster + service (HTTP on port 80) and load balancer wiring.
- ECR repository exists (or the script can create it if you set `AWS_CREATE_ECR=true`).
- `.env` populated (or you supply the Barqouq env vars inline).

## Required environment variables
Export before running `deploy.sh`:

```
AWS_REGION=us-east-1
AWS_ACCOUNT_ID=123456789012
ECR_REPO=barqouq-php-sample
ECS_CLUSTER=your-ecs-cluster
ECS_SERVICE=your-ecs-service
AWS_VPC_SUBNETS="subnet-aaa,subnet-bbb"   # used only if service creation is needed
AWS_SECURITY_GROUPS="sg-12345"            # used only if service creation is needed
BARQOUQ_GRPC_HOST=api.barqouq.shop:443
BARQOUQ_GRPC_TLS=true
BARQOUQ_SECRET_KEY=replace-with-secret
BARQOUQ_SUBDOMAIN=your-subdomain
```

Optional:
- `IMAGE_TAG` (defaults to `latest`)
- `AWS_CREATE_ECR=true` to auto-create the ECR repo if missing
- `APP_ENV=production` and `APP_URL` for framework awareness (not strictly required)

## Deploy steps
```
cd deploy/aws/ecs
./deploy.sh
```

What it does:
1) Builds the Docker image from repo root.
2) Logs in to ECR and pushes the image.
3) Renders `taskdef.json` from `taskdef.json.tpl` with the image URI and env vars.
4) Registers a new task definition revision.
5) Updates the existing ECS service to use the new task definition revision.

## Notes
- Secrets: for production use, prefer pulling the Barqouq secret from AWS Secrets Manager or SSM Parameter Store. Replace the env block in `taskdef.json.tpl` accordingly.
- Networking: this starter assumes your service already has the right networking/ALB wiring. If you need to create the service, add a `create-service` call in the script using your subnets/security groups/target group.
- Logs: uses awslogs driver by default. Ensure the log group exists or allow auto-create.
