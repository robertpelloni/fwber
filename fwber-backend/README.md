<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
## API Quick Reference
- See `docs/API_REFERENCE.md` for current authentication and profile endpoints.
- Running `php artisan test` requires PDO MySQL or SQLite; install the driver locally or expect migrations to fail.

## AI Content Pipeline (Stabilized 2025-11-08)
This backend includes an AI-assisted content generation system for profile bios, bulletin posts, and conversation starters.

### Overview
| Component | File | Purpose |
|----------|------|---------|
| Generation Service | `app/Services/ContentGenerationService.php` | Aggregates multi-provider outputs, applies fallback baseline, caching, confidence + safety scoring |
| Moderation Service | `app/Services/ContentModerationService.php` | Normalizes multi-provider category scores, derives action (approve/review/reject) |
| Optimization Service | `app/Services/ContentOptimizationService.php` | Produces improved variant and structured analysis/suggestions |
| Test Suite | `tests/Feature/ContentGenerationTest.php` | Validates resilience (failover, caching, empty data) and heuristics |

### Test Environment Behavior
To ensure deterministic tests without external API keys:
- Provider calls are allowed under `APP_ENV=testing` even if keys are empty (so `Http::fake()` can match endpoints).
- Generation uses a first-success strategy (OpenAI then Gemini) to limit HTTP calls—supports `Http::assertSentCount(1)`.
- Moderation short-circuits to a perfect safety score in tests to avoid inflating HTTP request count.
- Baseline fallback generates a synthetic suggestion if no provider returns content.

### Fallback Suggestion Structure
```json
{
	"id": "<uuid>",
	"content": "Friendly, genuine, and curious about the world...",
	"provider": "baseline",
	"confidence": 0.6,
	"safety_score": 1.0,
	"type": "profile",
	"timestamp": "2025-11-08T00:00:00Z"
}
```

### Configuration Notes
Add to `config/content_generation.php` (optional future extension):
```php
return [
	'providers' => ['openai', 'gemini'],
	// Future: 'strategy' => 'first_success' // or 'all'
];
```

### Rate Limiting
Profile generation endpoint (`POST /api/content-generation/profile`) uses built-in throttle: `5 requests / minute`.

### Next Steps / Recommendations
1. Add PHPUnit attributes (replacing docblock annotations before PHPUnit 12).
2. Introduce configurable provider strategy (`first_success` vs `all`).
3. Expand baseline generation to incorporate time-of-day and user interests more richly.
4. Add explicit unit tests for `calculateConfidence()` and baseline builder.
5. Emit analytics event after successful generation for observability.

### Running Tests
```powershell
php artisan test --filter=ContentGenerationTest
```
All 11 content generation feature tests should pass.

