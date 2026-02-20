#!/bin/bash

# Deployment Script for fwber
# Usage: ./deploy.sh [environment]

ENV=${1:-staging}
echo "🚀 Deploying to $ENV environment..."

# 1. Build Docker Images
echo "📦 Building Docker images..."
docker build -t fwber-backend:latest -f fwber-backend/Dockerfile.prod fwber-backend
docker build -t fwber-frontend:latest -f fwber-frontend/Dockerfile.prod fwber-frontend

# 2. Tag Images (Mock Registry)
echo "🏷️ Tagging images..."
docker tag fwber-backend:latest registry.fwber.me/fwber-backend:$ENV
docker tag fwber-frontend:latest registry.fwber.me/fwber-frontend:$ENV

# 3. Push Images (Mock)
echo "⬆️ Pushing images to registry (Mock)..."
# docker push registry.fwber.me/fwber-backend:$ENV
# docker push registry.fwber.me/fwber-frontend:$ENV

# 4. Apply Kubernetes Manifests
echo "☸️ Applying Kubernetes manifests..."
kubectl apply -f docker/k8s/fwber-stack.yaml
kubectl apply -f docker/k8s/ingress.yaml

# 5. Run Migrations
echo "🗄️ Running database migrations..."
# kubectl exec -it $(kubectl get pod -l app=fwber-backend -o jsonpath="{.items[0].metadata.name}") -- php artisan migrate --force

echo "✅ Deployment complete! Check status with 'kubectl get pods'."
