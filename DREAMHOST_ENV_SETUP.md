# DreamHost Environment Setup Guide

This guide details the specific environment variables you need to add or update in your `.env` file on the DreamHost server to enable the performance optimizations (Database Drivers) and security settings (CORS) we've implemented.

## 1. Database Drivers (Performance Optimization)

We are switching from file-based storage to database storage for Cache, Queue, and Sessions. This prevents file locking issues on shared hosting and improves performance.

Add or update these lines in your `.env` file:

```ini
# Cache Configuration
CACHE_STORE=database

# Queue Configuration
QUEUE_CONNECTION=database

# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
```

## 2. CORS Configuration (Security)

We have updated the CORS configuration to be dynamic. You must specify your frontend URL here.

```ini
# CORS Settings
# Replace with your actual frontend domain (e.g., https://fwber.com)
CORS_ALLOWED_ORIGINS="https://fwber.com,http://localhost:3000"
```

## 3. Verification

After updating the `.env` file, run the following command on the server to clear the config cache:

```bash
php artisan config:clear
```

Then, run the migrations to ensure the necessary tables exist (if you haven't already):

```bash
php artisan migrate --force
```
