#!/usr/bin/env bash
set -euo pipefail

# Minimal Azure App Service deploy helper for barqouq-php-sample
# Prereqs: Azure CLI

ROOT_DIR="$(cd "$(dirname "$0")/../../" && pwd)"

: "${AZURE_SUBSCRIPTION_ID:?need AZURE_SUBSCRIPTION_ID}"
: "${AZURE_RESOURCE_GROUP:?need AZURE_RESOURCE_GROUP}"
: "${AZURE_REGISTRY_URL:?need AZURE_REGISTRY_URL}"
: "${AZURE_REGISTRY_NAME:?need AZURE_REGISTRY_NAME}"
: "${AZURE_REGISTRY_USERNAME:?need AZURE_REGISTRY_USERNAME}"
: "${AZURE_REGISTRY_PASSWORD:?need AZURE_REGISTRY_PASSWORD}"
: "${AZURE_APP_SERVICE_NAME:?need AZURE_APP_SERVICE_NAME}"

IMAGE_TAG="${IMAGE_TAG:-latest}"
IMAGE_URI="${AZURE_REGISTRY_URL}/barqouq-php-sample:${IMAGE_TAG}"

# Optional env
APP_ENV="${APP_ENV:-production}"
APP_URL="${APP_URL:-http://localhost}"
BARQOUQ_GRPC_HOST="${BARQOUQ_GRPC_HOST:-api.barqouq.shop:443}"
BARQOUQ_GRPC_TLS="${BARQOUQ_GRPC_TLS:-true}"
BARQOUQ_SECRET_KEY="${BARQOUQ_SECRET_KEY:-}"
BARQOUQ_SUBDOMAIN="${BARQOUQ_SUBDOMAIN:-}"

if [[ -z "$BARQOUQ_SECRET_KEY" || -z "$BARQOUQ_SUBDOMAIN" ]]; then
  echo "BARQOUQ_SECRET_KEY and BARQOUQ_SUBDOMAIN must be set" >&2
  exit 1
fi

# Set default registry for Azure CLI
az configure --defaults acr="${AZURE_REGISTRY_NAME}"

# Build & push image
echo "Logging in to ACR..."
az acr login --name "${AZURE_REGISTRY_NAME}"

echo "Building image ${IMAGE_URI}..."
az acr build \
  --registry "${AZURE_REGISTRY_NAME}" \
  --image "barqouq-php-sample:${IMAGE_TAG}" \
  --file "$ROOT_DIR/docker/Dockerfile" \
  "$ROOT_DIR"

echo "Updating App Service ${AZURE_APP_SERVICE_NAME}..."
# Update app settings (environment variables)
az webapp config appsettings set \
  --name "${AZURE_APP_SERVICE_NAME}" \
  --resource-group "${AZURE_RESOURCE_GROUP}" \
  --settings \
    BARQOUQ_GRPC_HOST="${BARQOUQ_GRPC_HOST}" \
    BARQOUQ_GRPC_TLS="${BARQOUQ_GRPC_TLS}" \
    BARQOUQ_SECRET_KEY="${BARQOUQ_SECRET_KEY}" \
    BARQOUQ_SUBDOMAIN="${BARQOUQ_SUBDOMAIN}" \
    APP_ENV="${APP_ENV}" \
    APP_URL="${APP_URL}" \
    DOCKER_REGISTRY_SERVER_URL="https://${AZURE_REGISTRY_URL}" \
    DOCKER_REGISTRY_SERVER_USERNAME="${AZURE_REGISTRY_USERNAME}" \
    DOCKER_REGISTRY_SERVER_PASSWORD="${AZURE_REGISTRY_PASSWORD}"

# Configure Docker container settings
az webapp config container set \
  --name "${AZURE_APP_SERVICE_NAME}" \
  --resource-group "${AZURE_RESOURCE_GROUP}" \
  --docker-custom-image-name "${IMAGE_URI}" \
  --docker-registry-server-url "https://${AZURE_REGISTRY_URL}" \
  --docker-registry-server-username "${AZURE_REGISTRY_USERNAME}" \
  --docker-registry-server-password "${AZURE_REGISTRY_PASSWORD}"

echo "Restarting App Service..."
az webapp restart \
  --name "${AZURE_APP_SERVICE_NAME}" \
  --resource-group "${AZURE_RESOURCE_GROUP}"

echo "Done. App Service is deploying the new image."
echo "App URL: https://${AZURE_APP_SERVICE_NAME}.azurewebsites.net"
