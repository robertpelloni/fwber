# FWBer.com Quick Start Guide

## Prerequisites

- **Docker** and **Docker Compose** installed
- **Node.js 18+** and **npm** (for local development)
- **PHP 8.2+** and **Composer** (for local development)
- **MySQL 8.0+** (or use Docker)
- **Redis** (or use Docker)

## Step 1: Project Setup

### Clone and Initialize Project

```bash
# Create project directory
mkdir fwber-modern
cd fwber-modern

# Create backend (Laravel)
composer create-project laravel/laravel backend
cd backend

# Install Laravel dependencies
composer require laravel/sanctum
composer require laravel/broadcasting
composer require predis/predis
composer require intervention/image
composer require spatie/laravel-permission

# Create frontend (Next.js)
cd ..
npx create-next-app@latest frontend --typescript --tailwind --app --src-dir
cd frontend

# Install Next.js dependencies
npm install @tanstack/react-query axios react-hook-form @hookform/resolvers zod zustand socket.io-client lucide-react @headlessui/react clsx tailwind-merge react-hot-toast
```

## Step 2: Backend Configuration

### Configure Laravel Environment

```bash
cd backend
cp .env.example .env
```

Edit `.env` file:

```env
APP_NAME="FWBer"
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fwber
DB_USERNAME=root
DB_PASSWORD=your-password

BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# GenAI Configuration (you'll need to get API key)
GENAI_API_KEY=your-genai-api-key
GENAI_ENDPOINT=https://api.genai.com/v1/generation

FILESYSTEM_DISK=public
SANCTUM_STATEFUL_DOMAINS=localhost:3000
CORS_ALLOWED_ORIGINS=http://localhost:3000
```

### Generate Application Key

```bash
php artisan key:generate
```

### Run Database Migrations

```bash
php artisan migrate
```

### Create Storage Link

```bash
php artisan storage:link
```

## Step 3: Frontend Configuration

### Configure Next.js Environment

```bash
cd ../frontend
```

Create `.env.local`:

```env
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_APP_NAME=FWBer
NEXT_PUBLIC_APP_URL=http://localhost:3000
```

## Step 4: GenAI Setup

### Option 1: Google Gemini (Recommended)

1. Go to [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Create an API key
3. Update your Laravel `.env`:

```env
GENAI_API_KEY=your-gemini-api-key
GENAI_ENDPOINT=https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent
```

### Option 2: Stable Diffusion

1. Get API key from [Stability AI](https://platform.stability.ai/)
2. Update your Laravel `.env`:

```env
GENAI_API_KEY=your-stability-api-key
GENAI_ENDPOINT=https://api.stability.ai/v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image
```

## Step 5: Start Development Servers

### Option A: Using Docker (Recommended)

Create `docker-compose.yml` in the root directory:

```yaml
version: '3.8'

services:
  backend:
    build: ./backend
    ports:
      - "8000:9000"
    volumes:
      - ./backend:/var/www
    depends_on:
      - mysql
      - redis
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis

  frontend:
    build: ./frontend
    ports:
      - "3000:3000"
    volumes:
      - ./frontend:/app
    depends_on:
      - backend

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: fwber
      MYSQL_ROOT_PASSWORD: password
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

volumes:
  mysql_data:
```

Start services:

```bash
docker-compose up -d
```

### Option B: Local Development

#### Start Backend (Laravel)

```bash
cd backend
php artisan serve --host=0.0.0.0 --port=8000
```

#### Start Frontend (Next.js)

```bash
cd frontend
npm run dev
```

## Step 6: Test the Setup

### Test Backend API

```bash
# Test if Laravel is running
curl http://localhost:8000/api/health

# Expected response: {"status":"ok"}
```

### Test Frontend

1. Open http://localhost:3000
2. You should see the Next.js welcome page

### Test Avatar Generation

1. Register a new user at http://localhost:3000/register
2. Complete your profile
3. Check if avatar is generated automatically

## Step 7: Development Workflow

### Backend Development

```bash
cd backend

# Run migrations
php artisan migrate

# Seed database (if you have seeders)
php artisan db:seed

# Clear cache
php artisan cache:clear
php artisan config:clear

# Watch for changes (optional)
php artisan serve --host=0.0.0.0 --port=8000
```

### Frontend Development

```bash
cd frontend

# Install new dependencies
npm install package-name

# Run development server
npm run dev

# Build for production
npm run build
```

## Step 8: Common Issues & Solutions

### Issue: CORS Errors

**Solution**: Update Laravel CORS configuration in `config/cors.php`:

```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['http://localhost:3000'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### Issue: Avatar Generation Fails

**Solution**: Check your GenAI API key and endpoint:

```bash
# Test GenAI connection
php artisan tinker
>>> app(\App\Services\AvatarGenerationService::class)->testConnection()
```

### Issue: Database Connection

**Solution**: Verify MySQL is running and credentials are correct:

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()
```

### Issue: Storage Permissions

**Solution**: Set proper permissions for file uploads:

```bash
# Set storage permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

## Step 9: Production Deployment

### Environment Variables for Production

```env
# Laravel Production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_HOST=your-production-db-host
DB_PASSWORD=your-production-db-password

REDIS_HOST=your-production-redis-host
REDIS_PASSWORD=your-production-redis-password

# Use HTTPS in production
FORCE_HTTPS=true
```

```env
# Next.js Production
NEXT_PUBLIC_API_URL=https://your-domain.com/api
NEXT_PUBLIC_APP_URL=https://your-domain.com
```

### Build for Production

```bash
# Backend
cd backend
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Frontend
cd frontend
npm run build
```

## Step 10: Next Development Steps

1. **Implement Authentication Flow**
   - Complete registration/login forms
   - Add email verification
   - Implement password reset

2. **Build Profile Management**
   - Create profile completion wizard
   - Add avatar regeneration
   - Implement preference settings

3. **Add Matching System**
   - Implement basic matching algorithm
   - Add compatibility scoring
   - Create match display interface

4. **Implement Location Features**
   - Add GPS location detection
   - Create venue presence system
   - Build nearby user discovery

5. **Add Real-time Features**
   - Implement WebSocket connections
   - Add live notifications
   - Create real-time messaging

6. **Security & Anti-Bot Measures**
   - Add phone verification
   - Implement photo verification
   - Add rate limiting and bot detection

## Support & Resources

- **Laravel Documentation**: https://laravel.com/docs
- **Next.js Documentation**: https://nextjs.org/docs
- **GenAI Documentation**: https://ai.google.dev/docs
- **Tailwind CSS**: https://tailwindcss.com/docs

## Getting Help

If you encounter issues:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Check browser console for frontend errors
3. Verify all environment variables are set correctly
4. Ensure all services (MySQL, Redis) are running
5. Check Docker logs if using containers: `docker-compose logs`

This quick start guide should get you up and running with the basic FWBer.com revival setup. From here, you can build upon the foundation to add all the advanced features outlined in the implementation plan.
