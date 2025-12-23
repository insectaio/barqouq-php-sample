#!/usr/bin/env bash
set -euo pipefail

# Minimal ECS deploy helper for barqouq-php-sample
# Prereqs: AWS CLI v2, Docker

ROOT_DIR="$(cd "$(dirname "$0")/../../" && pwd)"
TPL_DIR="$(cd "$(dirname "$0")" && pwd)"

: "${AWS_REGION:?need AWS_REGION}"
: "${AWS_ACCOUNT_ID:?need AWS_ACCOUNT_ID}"
: "${ECR_REPO:?need ECR_REPO}"
: "${ECS_CLUSTER:?need ECS_CLUSTER}"
: "${ECS_SERVICE:?need ECS_SERVICE}"
: "${ECS_EXECUTION_ROLE_ARN:?need ECS_EXECUTION_ROLE_ARN}"

IMAGE_TAG="${IMAGE_TAG:-latest}"
IMAGE_URI="${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com/${ECR_REPO}:${IMAGE_TAG}"
TASK_FAMILY="${ECS_TASK_FAMILY:-barqouq-php-sample}"

# Optional env
APP_ENV="${APP_ENV:-production}"
APP_URL="${APP_URL:-http://localhost}"
BARQOUQ_GRPC_HOST="${BARQOUQ_GRPC_HOST:-api.barqouq.shop:443}"
BARQOUQ_GRPC_TLS="${BARQOUQ_GRPC_TLS:-true}"
BARQOUQ_SECRET_KEY="${BARQOUQ_SECRET_KEY:-}"
BARQOUQ_SUBDOMAIN="${BARQOUQ_SUBDOMAIN:-}"
ECS_LOG_GROUP="${ECS_LOG_GROUP:-/ecs/barqouq-php-sample}"

if [[ -z "$BARQOUQ_SECRET_KEY" || -z "$BARQOUQ_SUBDOMAIN" ]]; then
  echo "BARQOUQ_SECRET_KEY and BARQOUQ_SUBDOMAIN must be set" >&2
  exit 1
fi

# Build & push image
echo "Logging in to ECR..."
aws ecr get-login-password --region "$AWS_REGION" | docker login --username AWS --password-stdin "${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com"

if [[ "${AWS_CREATE_ECR:-false}" == "true" ]]; then
  aws ecr describe-repositories --repository-names "$ECR_REPO" --region "$AWS_REGION" >/dev/null 2>&1 || \
    aws ecr create-repository --repository-name "$ECR_REPO" --region "$AWS_REGION" >/dev/null
fi

echo "Building image ${IMAGE_URI}..."
docker build -t "$IMAGE_URI" -f "$ROOT_DIR/docker/Dockerfile" "$ROOT_DIR"

echo "Pushing image..."
docker push "$IMAGE_URI"

# Render task definition
echo "Rendering task definition..."
export IMAGE_URI APP_ENV APP_URL BARQOUQ_GRPC_HOST BARQOUQ_GRPC_TLS BARQOUQ_SECRET_KEY BARQOUQ_SUBDOMAIN ECS_LOG_GROUP AWS_REGION ECS_TASK_ROLE_ARN ECS_EXECUTION_ROLE_ARN ECS_TASK_FAMILY TASK_FAMILY
envsubst < "$TPL_DIR/taskdef.json.tpl" > "$TPL_DIR/taskdef.json"

# Register new task definition
echo "Registering task definition..."
NEW_TASK_DEF_ARN=$(aws ecs register-task-definition \
  --cli-input-json "file://$TPL_DIR/taskdef.json" \
  --query 'taskDefinition.taskDefinitionArn' --output text)

echo "Updating service ${ECS_SERVICE} on cluster ${ECS_CLUSTER} to ${NEW_TASK_DEF_ARN}..."
aws ecs update-service \
  --cluster "$ECS_CLUSTER" \
  --service "$ECS_SERVICE" \
  --task-definition "$NEW_TASK_DEF_ARN" \
  --force-new-deployment \
  --region "$AWS_REGION" >/dev/null

echo "Done. Service is deploying the new task: ${NEW_TASK_DEF_ARN}"
