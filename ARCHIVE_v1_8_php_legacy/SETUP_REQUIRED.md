# Setup Required

The following configuration steps are required to fully enable all features.

## 1. AWS Configuration (Required for Media Analysis)
The **Media Analysis** feature (Rekognition) and **Photo Storage** (S3) require valid AWS credentials.
Please update `fwber-backend/.env` with the following:

```dotenv
AWS_ACCESS_KEY_ID=your_access_key_id
AWS_SECRET_ACCESS_KEY=your_secret_access_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_s3_bucket_name
```

## 2. Verify Feature Flags
All feature flags have been enabled in `fwber-backend/.env`. You can verify them by running:
```bash
php fwber-backend/scripts/check_config.php
```

## 3. Frontend Configuration
The Mercure URL has been configured in `fwber-frontend/.env.local`.
You can verify frontend configuration by running:
```bash
node fwber-frontend/scripts/check-env.js
```

## 4. Restart Services
After updating `.env` files, please restart your backend and frontend servers to apply changes.
```bash
# Backend
cd fwber-backend
php artisan serve

# Frontend
cd fwber-frontend
npm run dev
```
