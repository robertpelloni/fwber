# Backend Documentation

> **⚠️ MASTER PROTOCOL:** The Single Source of Truth for all operations is `../docs/UNIVERSAL_LLM_INSTRUCTIONS.md`.

## 🏗️ Architecture
This is a Laravel 12 API backend providing the core business logic, database interactions, and realtime events for fwber.

### Key Components
- **API:** RESTful endpoints defined in `routes/api.php`.
- **Auth:** Sanctum-based token authentication.
- **Realtime:** Laravel Reverb (WebSocket) integration.
- **Database:** MySQL 8.0+ with Spatial extensions for proximity features.

## 🚀 Getting Started

### Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0+ (or SQLite)

### Installation
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

## 🧪 Testing
```bash
php artisan test
```

## 📚 Agent Instructions
See `AGENTS.md` in this directory for specific guidelines on working with the backend code.
