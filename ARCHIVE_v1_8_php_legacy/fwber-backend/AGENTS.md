# Backend Agent Instructions

> **Context:** `fwber-backend` (Laravel 12 API)

## 🧠 Component Context
This directory contains the core API logic, database interactions, and business rules for fwber. It follows a standard Laravel architecture with strict typing and modern PHP 8.2+ features.

## 🛠️ Key Commands
- **Test:** `php artisan test` (Run before committing)
- **Lint/Fix:** `./vendor/bin/pint` (Laravel Pint)
- **Analyze:** `./vendor/bin/phpstan analyse`
- **Serve:** `php artisan serve`

## 📜 Standards & Conventions
- **OpenAPI:** All API resources must have corresponding schemas in `app/Http/Schemas`.
- **Feature Flags:** Use `Laravel\Pennant` for feature gating where applicable.

## 🏗️ Architecture & Patterns
- **API First:** All routes in `routes/api.php`. No web routes for UI.
- **Strict Types:** All PHP files must start with `declare(strict_types=1);`.
- **Service Layer:** Complex business logic resides in `app/Services`, not Controllers.
- **Events:** Use Laravel Events/Listeners for side effects (e.g., matching notifications).
- **Proximity:** Geospatial logic uses MySQL 8.0 spatial types. See `app/Services/GeolocationService.php`.

## 🚨 Critical Rules
1.  **No `any` equivalents:** Do not use `mixed` loosely. Type everything.
2.  **DTOs:** Use Data Transfer Objects (or specific array shapes) for complex data passing.
3.  **Sanitization:** All user input must be validated via FormRequests (`app/Http/Requests`).
4.  **Realtime:** Implementation must implement `ShouldBroadcast`.

## 🧪 Verification
- Ensure migration files are timestamped correctly.
- Run `php artisan test` to verify API contracts.
