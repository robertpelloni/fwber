# MEMORY.md — Codebase Observations & Quirks

> *This file serves as a persistent memory bank for all AI agents working on fwber. Update this file whenever you encounter a strange architectural quirk, a persistent bug, or a strict design preference.*

## Architectural Observations

1.  **WebSockets vs HTTP:** The application uses Laravel Reverb for real-time WebSockets on port 8080 (proxied via `ws.fwber.me`), but relies on an external Rust service (`fwber-geo`) for spatial indexing.
2.  **Face Blurring:** Client-side face blurring (`@vladmandic/face-api`) is heavily utilized to maintain the "Anti-Catfish" guarantee. The models are statically served from `public/models`. **Do not attempt to move this logic to the backend.**
3.  **Strict Typing:** The frontend enforces strict TypeScript. If you see a type error, do not bypass it with `any` unless absolutely necessary for a critical hotfix. Fix the underlying data flow.
4.  **Database:** We use MySQL/PostgreSQL in production. Avoid database-specific syntax if possible, or provide fallback logic for local SQLite testing.
5.  **Event Sourcing:** Core modules (Location, Matches, Messages) use a custom Event Sourcing architecture. Always append events to the `EventStore` rather than mutating state directly where this pattern is applied. Ensure `aggregateId` is always cast to a `(string)`.

## Design Preferences

1.  **Aesthetic:** "Neon-Professional" / "Cyber-Noir". Deep dark modes, subtle glowing accents, glassmorphism (`backdrop-blur-md`).
2.  **UI Updates:** Prefer optimistic UI updates over loading spinners where possible. Use `framer-motion` for smooth transitions.
3.  **Alerts:** Never use native browser `alert()` or `confirm()`. Always use the custom Toast system from `shadcn/ui`.

## Known Quirks & Gotchas

1.  **DreamHost Production Quirks:** 
    *   Shared hosting on DreamHost has aggressive process and memory limits. `npm run build` will often crash with OOM errors. Builds must happen locally or on Vercel, and static assets synced via git or rsync.
    *   DreamHost Apache setups prioritize `index.html` over `index.php`. Ensure `index.html` does not exist in the public directory.
    *   The `api.fwber.me` subdomain requires explicit CORS handling in `.htaccess` and `cors.php` to accept requests from the Vercel frontend.
2.  **Double API Prefix:** Frontend `client.ts` automatically appends `/api`. If `NEXT_PUBLIC_API_URL` also contains `/api`, it results in a `api/api/...` 404 error. The URL must be clean (e.g., `https://api.fwber.me`).
3.  **Silent 500 Errors:** Laravel's global exception handler in `bootstrap/app.php` suppresses stack traces for `api/*` routes in production. When debugging 500 errors, temporarily add `Log::error($e)` to the `render` method to expose the trace, as local files may not have write permissions.
4.  **Duplicate Routes:** Be careful when modifying `routes/api.php`. Previous refactors accidentally created duplicate route blocks. Always check the entire file after making changes.
