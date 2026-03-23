# MEMORY.md — Codebase Observations & Quirks

> *This file serves as a persistent memory bank for all AI agents working on fwber. Update this file whenever you encounter a strange architectural quirk, a persistent bug, or a strict design preference.*

## Architectural Observations

1.  **WebSockets vs HTTP:** The application uses Laravel Reverb for real-time WebSockets, but it relies on an external Rust service (`fwber-geo`) for spatial indexing via HTTP POST/GET requests. This split was intentional for performance, but requires ensuring both services are running locally (`php artisan serve` + `cargo run`).
2.  **Face Blurring:** Client-side face blurring (`@vladmandic/face-api`) is heavily utilized to maintain the "Anti-Catfish" guarantee. The models are statically served from `public/models`. **Do not attempt to move this logic to the backend.**
3.  **Strict Typing:** The frontend enforces strict TypeScript. If you see a type error (e.g., `Type 'undefined' is not assignable to type 'string'`), do not bypass it with `any` or `// @ts-ignore`. Fix the underlying data flow or provide proper fallbacks.
4.  **Database:** We use PostgreSQL with PostGIS in production, but SQLite for local testing. Avoid database-specific syntax (like MySQL's `TIMESTAMPDIFF`) in queries if possible, or provide fallback logic for SQLite (e.g., `julianday('now') - julianday(date_of_birth)`).

## Design Preferences

1.  **Aesthetic:** "Neon-Professional" / "Cyber-Noir". Deep dark modes, subtle glowing accents, glassmorphism (`backdrop-blur-md`).
2.  **UI Updates:** Prefer optimistic UI updates over loading spinners where possible. Use `framer-motion` for smooth transitions.
3.  **Alerts:** Never use native browser `alert()` or `confirm()`. Always use the custom Toast system or custom Modals.

## Known Quirks

1.  **Duplicate Routes:** Be careful when modifying `routes/api.php`. Previous refactors accidentally created duplicate route blocks (e.g., for Federation). Always check the entire file after making changes.
2.  **Middleware:** The `api` middleware group in `bootstrap/app.php` can sometimes apply `auth:sanctum` unexpectedly if routes aren't explicitly placed or excluded using `->withoutMiddleware(['auth:sanctum'])`.
3.  **Test Environment:** The local test suite (`php artisan test`) may skip tests requiring Redis (like `ControllerCachingTest`) if the PHP Redis extension is not installed. This is expected behavior in local dev, but Redis is mandatory in production.