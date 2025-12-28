# Recent Updates - December 27, 2025

## Critical Fixes
- **Resolved Circular Dependency**: Fixed "Module factory not available" error on production.
  - Deleted `lib/api/index.ts` barrel file.
  - Removed `instrumentation-client.ts` and inlined Sentry initialization into `components/SentryInitializer.tsx`.
  - Verified no other circular dependencies in `lib/hooks`, `lib/contexts`, or `components`.

## Technical Details
- The error was caused by a circular dependency involving the instrumentation bundle and/or barrel files.
- By removing the barrel file and simplifying the Sentry client initialization, we broke the cycle.
- `instrumentation.ts` (server-side) remains unchanged and correct.

## Next Steps
- Monitor production logs for any further "Module factory not available" errors.
- If error persists, investigate `node_modules` for conflicting versions of `@sentry/nextjs` or other packages.
