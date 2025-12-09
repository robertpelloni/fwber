# Environment Configuration Checklist

## Overview
Complete checklist of all environment variables required for FWBer backend operation. Use this to verify your `.env` configuration before deployment.

---

## Core Application Settings

### Required
```env
APP_NAME=FWBer
APP_ENV=production              # local|testing|production
APP_KEY=base64:...              # Generate: php artisan key:generate
APP_DEBUG=false                 # MUST be false in production
APP_URL=https://api.fwber.com   # Your production API URL
```

✅ **Verification**:
- `APP_KEY` is set and secure (32-character base64)
- `APP_DEBUG=false` in production
- `APP_URL` matches your domain

---

## Database Configuration

### Required
```env
DB_CONNECTION=mysql             # mysql|pgsql|sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fwber_production
DB_USERNAME=fwber_user
DB_PASSWORD=<secure_password>   # Strong password required
```

✅ **Verification**:
- Database exists and is accessible
- User has appropriate permissions (SELECT, INSERT, UPDATE, DELETE, CREATE, DROP)
- Password is strong (16+ characters, mixed case, numbers, symbols)
- Test connection: `php artisan migrate:status`

---

## Authentication & Security

### Required
```env
JWT_SECRET=<secure_jwt_secret>  # 64+ character random string
JWT_TTL=60                      # Token lifetime in minutes
JWT_REFRESH_TTL=20160          # Refresh token lifetime (14 days)
```

✅ **Verification**:
- `JWT_SECRET` is cryptographically strong
- Never commit JWT_SECRET to version control
- TTL values appropriate for your use case

### Session & Encryption
```env
SESSION_DRIVER=database         # database|redis
SESSION_LIFETIME=120
BCRYPT_ROUNDS=12               # 10-14 recommended
```

---

## File Storage

### Required
```env
FILESYSTEM_DISK=public          # public|s3
```

### For Local Storage (Development)
```env
# No additional config needed
```

### For S3 (Production)
```env
AWS_ACCESS_KEY_ID=<aws_key>
AWS_SECRET_ACCESS_KEY=<aws_secret>
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=fwber-photos
AWS_USE_PATH_STYLE_ENDPOINT=false
```

✅ **Verification**:
- Storage directory exists: `storage/app/public`
- Symlink created: `php artisan storage:link`
- Permissions: `chmod -R 775 storage bootstrap/cache`
- For S3: bucket exists and IAM user has read/write permissions

---

## Email Configuration

### Required
```env
MAIL_MAILER=smtp                # smtp|sendmail|mailgun|ses
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=<smtp_username>
MAIL_PASSWORD=<smtp_password>
MAIL_ENCRYPTION=tls             # tls|ssl
MAIL_FROM_ADDRESS=noreply@fwber.com
MAIL_FROM_NAME="${APP_NAME}"
```

### For Development
```env
MAIL_MAILER=log                 # Logs emails instead of sending
```

✅ **Verification**:
- SMTP credentials valid
- Test email: `php artisan tinker` → `Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));`
- Port 587 (TLS) or 465 (SSL) is open

---

## Caching & Queues

### Cache
```env
CACHE_DRIVER=redis              # redis|file|database
CACHE_PREFIX=fwber_cache
```

### Queue
```env
QUEUE_CONNECTION=redis          # redis|database|sync
```

### Redis (Recommended for Production)
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=<redis_password>
REDIS_PORT=6379
REDIS_CLIENT=phpredis           # phpredis|predis
```

✅ **Verification**:
- Redis is running: `redis-cli ping` → PONG
- Queue workers running: `php artisan queue:work`
- Cache working: `php artisan cache:clear`

---

## Feature Flags

### Core Features (Usually Enabled)
```env
FEATURE_GROUPS=true
FEATURE_PHOTOS=true
FEATURE_PROXIMITY_ARTIFACTS=true
```

### Advanced Features (Opt-in)
```env
FEATURE_CHATROOMS=false
FEATURE_PROXIMITY_CHATROOMS=false
FEATURE_RECOMMENDATIONS=false
FEATURE_WEBSOCKET=false
FEATURE_CONTENT_GENERATION=false
FEATURE_RATE_LIMITS=false
FEATURE_ANALYTICS=false
```

✅ **Verification**:
- Only enable features you need
- Test feature flags: Check routes are accessible/blocked accordingly
- See `docs/FEATURE_FLAGS.md` for details

---

## Avatar Mode

### Required
```env
AVATAR_MODE=generated-only      # generated-only|uploaded-photos
```

**Options**:
- `generated-only`: Users get AI-generated avatars, photo uploads disabled (**MVP default**)
- `uploaded-photos`: Users can upload their own photos

✅ **Verification**:
- Try uploading photo when mode is `generated-only` → should get 403
- Switch to `uploaded-photos` → uploads should work

---

## Real-Time Features (Optional)

### Mercure Hub
```env
MERCURE_URL=https://mercure.fwber.com/.well-known/mercure
MERCURE_PUBLIC_URL=https://mercure.fwber.com/.well-known/mercure
MERCURE_JWT_SECRET=<mercure_jwt_secret>
```

### WebSocket
```env
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1
```

✅ **Verification**:
- Mercure hub is running and accessible
- JWT secret matches Mercure configuration
- Test connection: `curl ${MERCURE_URL}?topic=test`

---

## AI Services (Optional)

### OpenAI
```env
OPENAI_API_KEY=sk-...
OPENAI_ORGANIZATION=org-...     # Optional
```

### Google Gemini
```env
GEMINI_API_KEY=AIza...
```

✅ **Verification**:
- API keys are valid
- Test with content generation endpoint
- Monitor usage/quotas

---

## Monitoring & Logging

### Logging
```env
LOG_CHANNEL=stack               # stack|single|daily|slack|syslog
LOG_LEVEL=error                 # debug|info|warning|error|critical
LOG_DEPRECATIONS_CHANNEL=null
```

### Error Tracking (Sentry)
```env
SENTRY_LARAVEL_DSN=https://...@sentry.io/...
```

✅ **Verification**:
- Logs directory writable: `storage/logs/`
- Log rotation configured (for daily channel)
- Sentry receiving errors (test with intentional exception)

---

## Performance & Optimization

### Recommended Production Settings
```env
APP_DEBUG=false
LOG_LEVEL=error
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# PHP settings (in php.ini or .user.ini)
# memory_limit = 256M
# max_execution_time = 30
# post_max_size = 20M
# upload_max_filesize = 10M
```

✅ **Verification**:
- Config cached: `php artisan config:cache`
- Routes cached: `php artisan route:cache`
- Views cached: `php artisan view:cache`
- Optimized autoloader: `composer install --optimize-autoloader --no-dev`

---

## Security Hardening

### HTTPS & CORS
```env
# Force HTTPS in production
# Add to AppServiceProvider:
# URL::forceScheme('https');

# CORS configuration in config/cors.php
```

### Rate Limiting
```env
FEATURE_RATE_LIMITS=true        # Enable rate limiting
```

✅ **Verification**:
- HTTPS enforced
- CORS configured for your frontend domain
- Rate limits tested (see `RATE_LIMITING_TESTS.md`)
- Security headers set (CSP, HSTS, X-Frame-Options)

---

## Development vs Production

### Development (.env.local)
```env
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug
DB_CONNECTION=sqlite
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
MAIL_MAILER=log
```

### Testing (.env.testing)
```env
APP_ENV=testing
APP_DEBUG=true
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
```

### Production (.env.production)
```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=error
DB_CONNECTION=mysql
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
MAIL_MAILER=smtp
```

---

## Verification Commands

Run these to verify configuration:

```bash
# Check environment
php artisan env

# Verify database connection
php artisan migrate:status

# Test email
php artisan tinker
>>> Mail::raw('Test', fn($m) => $m->to('test@example.com')->subject('Test'));

# Test cache
php artisan cache:clear
php artisan cache:set test_key test_value
php artisan tinker
>>> Cache::get('test_key')

# Test queue
php artisan queue:work --once

# Check storage permissions
ls -la storage/
ls -la bootstrap/cache/

# Verify symlink
ls -la public/storage

# Generate OpenAPI docs
php artisan l5-swagger:generate
```

---

## Pre-Deployment Checklist

- [ ] All required env vars set
- [ ] `APP_DEBUG=false` in production
- [ ] `APP_KEY` generated and secure
- [ ] Database credentials valid
- [ ] `JWT_SECRET` strong and secret
- [ ] Storage configured and writable
- [ ] Email working (test send)
- [ ] Redis running (if using)
- [ ] Queue workers running
- [ ] Feature flags set correctly
- [ ] `AVATAR_MODE` set appropriately
- [ ] AI API keys valid (if using)
- [ ] Sentry configured (optional)
- [ ] HTTPS enforced
- [ ] CORS configured
- [ ] Rate limiting enabled
- [ ] Logs writable
- [ ] Config/routes/views cached
- [ ] Composer optimized
- [ ] Migrations run
- [ ] Seeds run (if needed)
- [ ] OpenAPI docs generated
- [ ] Health check passing: `curl https://api.fwber.com/api/health`

---

## Common Issues & Solutions

### Database Connection Failed
- Verify credentials in `.env`
- Check database exists: `mysql -u username -p`
- Verify host/port accessible
- Check firewall rules

### Storage Permission Denied
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
php artisan storage:link
```

### Queue Not Processing
```bash
# Check queue workers running
ps aux | grep queue

# Start worker
php artisan queue:work --daemon

# Use supervisor for production
```

### Redis Connection Refused
```bash
# Check Redis running
redis-cli ping

# Check port open
netstat -an | grep 6379

# Start Redis
sudo systemctl start redis
```

### JWT Token Issues
- Regenerate secret: `php artisan jwt:secret`
- Clear config cache: `php artisan config:clear`
- Verify TTL values reasonable

---

**Status**: Complete reference guide
**Usage**: Review before each deployment, verify all items checked
**See Also**: `docs/FEATURE_FLAGS.md`, `.env.example`
