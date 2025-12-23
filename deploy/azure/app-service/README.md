# Azure App Service + ACR deploy (starter)

This is a minimal, opinionated starter to build/push the PHP demo image to Azure Container Registry (ACR) and deploy/update an Azure App Service. You must already have an App Service and ACR set up.

## Prerequisites
- Azure CLI installed and authenticated.
- Existing Azure Container Registry and App Service (Linux).
- Service principal or managed identity with permissions to ACR and App Service.

## Required environment variables
Export before running `deploy.sh`:

```
AZURE_SUBSCRIPTION_ID=your-subscription-id
AZURE_RESOURCE_GROUP=your-resource-group
AZURE_REGISTRY_URL=myregistry.azurecr.io
AZURE_REGISTRY_NAME=myregistry
AZURE_REGISTRY_USERNAME=username
AZURE_REGISTRY_PASSWORD=password
AZURE_APP_SERVICE_NAME=my-app-service
BARQOUQ_GRPC_HOST=api.barqouq.shop:443
BARQOUQ_GRPC_TLS=true
BARQOUQ_SECRET_KEY=replace-with-secret
BARQOUQ_SUBDOMAIN=your-subdomain
```

Optional:
- `IMAGE_TAG` (defaults to `latest`)
- `APP_ENV=production` and `APP_URL` for framework awareness.

## Deploy steps
```
cd deploy/azure/app-service
./deploy.sh
```

What it does:
1) Builds the Docker image from repo root.
2) Logs in to ACR and pushes the image.
3) Updates App Service to use the new image (via app settings or redeploy).
4) Polls app health.

## Notes
- For production use, prefer Managed Identity or Azure Key Vault for secrets instead of inline env vars.
- Networking: ensure App Service is in the correct VNet/subnet and has outbound routes to Barqouq API.
- Logs: view via Azure Portal App Service → Deployment center or deployment slots.
