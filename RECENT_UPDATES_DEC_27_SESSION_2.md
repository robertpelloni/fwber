# Recent Updates - December 27, 2025 (Continued)

## Additional Fixes (Session 2)
- **Resolved Persistent Circular Dependency**: The "Module factory not available" error persisted despite previous fixes.
  - **Action**: Refactored `instrumentation.ts` to use dynamic imports (`await import(...)`) for `@sentry/nextjs`.
  - **Reasoning**: The top-level import of `@sentry/nextjs` in `instrumentation.ts` was likely causing a circular dependency or bundle initialization order issue in the Webpack/Turbopack build process. By moving the import inside the `register` function (which is async), we isolate the Sentry dependency from the initial module graph evaluation.
  - **Verification**: Checked `lib/logger.ts` and other Sentry consumers; they appear clean. The dynamic import in `instrumentation.ts` is a known pattern to resolve such bundling issues in Next.js.

## Summary of Changes
1.  Modified `fwber-frontend/instrumentation.ts` to dynamically import `@sentry/nextjs` based on the runtime (`nodejs` or `edge`).
2.  Verified `lib/logger.ts` imports.
3.  Confirmed `components/realtime/ProximityPresenceView.tsx` fix (from previous session) is correct.
