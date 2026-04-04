# MEMORY.md

## 2026-04-04 — v1.3.1 Foreground Notification UX
- Expo foreground notifications were already reaching `mobile/app/index.js`, but there was no user-facing surface inside the WebView. Native events must be explicitly bridged into browser space if the product shell is WebView-first.
- The cleanest approach is to reuse the existing `ToastProvider` instead of building a second native-only overlay system.
- `Notifications.getLastNotificationResponseAsync()` is an important complement to the response listener because cold-start notification opens are easy to miss otherwise.
- The current frontend build succeeds after the new bridge landed; the only observed issues during build were pre-existing Sentry deprecation/configuration warnings.

## 2026-04-04 — v1.3.0 Trust & Safety Hardening
- The active simplified product still had hidden dependencies on archived systems inside `AIMatchingService.php`.
- Discovery was attempting to eager-load `followedTopics` and consult `date_feedback`, which breaks fresh installs and SQLite tests even though those systems are no longer core.
- Blocking semantics must be treated as a hard graph cut, not a UI preference. The correct behavior is:
  - prevent discovery visibility
  - prevent established match visibility
  - prevent match actions
  - prevent messaging
  - deactivate existing matches
  - invalidate both users' caches
- The frontend safety client had a contract mismatch (`blocked_id` vs `user_id`) that made block UX unreliable even though the UI path existed.
- The unblock controller existed before this session, but the route was missing from `routes/api.php`.
