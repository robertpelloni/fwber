# MEMORY.md

## 2026-04-04 — v1.3.4 Console Error Sweep
- Real browser-console reports uncovered several hidden contract problems that local green builds would not surface on their own.
- The homepage still contained links to archived routes (`/roast`, `/rate-my-pussy`), which caused Next prefetch 404 noise on production.
- The frontend analytics client was actively posting to `/api/analytics/events`, but the simplified backend route set no longer exposed that endpoint even though `AnalyticsController::store()` still existed.
- Notification preferences had another frontend/backend route mismatch: the frontend already used `/notification-preferences/{type}`, while the active backend route still pointed at a path without `{type}`.
- Auth flows should never assume JSON on error. If production emits HTML, warnings, or malformed content during a 500, the frontend must degrade into a readable error instead of throwing a parser exception.
- The repeated `Access to storage is not allowed from this context` messages are likely extension/content-script noise rather than fwber app code, because the console explicitly shows `content script loaded` and the error occurs at `(index):1` before app-specific stack context.

## 2026-04-04 — v1.3.3 Sentry Build Modernization
- The frontend build was green but noisy because the Sentry integration had drifted behind current Next.js App Router conventions.
- The clean modern shape is:
  - `instrumentation.ts` for server/edge registration plus `onRequestError`
  - `instrumentation-client.ts` for browser init plus `onRouterTransitionStart`
  - no legacy `sentry.client.config.ts`
- Removing deprecated Sentry config patterns is worth doing because warning fatigue hides real build regressions.

## 2026-04-04 — v1.3.2 Notification Route Consistency
- Notification UX can drift even when each individual layer "works." The backend payload shape, toast CTA logic, notification drawer links, and target page behavior must all agree on the same route contract.
- Database notifications should carry explicit `type`, `title`, `body`, `url`, and actor identifiers instead of relying on frontend inference from PHP class names.
- The `/messages` page did not previously honor `?user=` routing, which meant notification links could point somewhere technically valid but still fail to land the user in the intended conversation.
- A small shared helper (`fwber-frontend/lib/notifications.ts`) is enough to eliminate most frontend notification routing drift.
