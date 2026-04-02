# Handoff — Copyright 2026 Refresh

**Date:** 2026-04-02  
**Status:** ✅ Local release verified  
**Version:** 1.0.43

## Overview
This cycle applied the requested copyright refresh by updating the public homepage footer from 2025 to 2026, then synchronized the release/version documents so the repo state stays coherent for the next pushed patch. The broader social-graph work remains queued behind the in-progress visibility and journals slice, but this release intentionally keeps scope tight so the legal/footer copy on the live site is correct immediately.

## Accomplishments
- Updated the homepage footer copyright notice to `© 2026 fwber. All rights reserved.` in `fwber-frontend/app/page.tsx`.
- Bumped the repository release version from `1.0.42` to `1.0.43` across the root and frontend package metadata.
- Refreshed `CHANGELOG.md`, `PROJECT_STATUS.md`, `VERSION.md`, `TODO.md`, and `ROADMAP.md` so the release ledger and next-step guidance stay aligned.
- Replaced the stale prior handoff summary with this focused release note so the next session starts from the current patch state.

## Key Files Modified
- `fwber-frontend/app/page.tsx`
  - Updated the public footer copyright string from 2025 to 2026.
- `VERSION`
- `package.json`
- `fwber-frontend/package.json`
- `CHANGELOG.md`
- `PROJECT_STATUS.md`
- `VERSION.md`
- `TODO.md`
- `ROADMAP.md`
- `HANDOFF.md`

## Validation
- `npm run lint`
- `npm run type-check`
- `npm run build`

## Notes / Risks
- Frontend lint still reports the pre-existing `react-hooks/exhaustive-deps` warning in `fwber-frontend/lib/api/photos.ts:476`.
- `fwber-frontend/tsconfig.tsbuildinfo` changes during frontend validation because it is tracked in git.
- The next product release should resume the in-progress social-graph work at visibility primitives plus journals, rather than stacking more unrelated patch releases.

## Next Recommended Slice
1. Ship reusable visibility primitives for `public`, `friends`, `groups/circle`, and `private` social content.
2. Build journals / field notes directly on top of those visibility rules so the first long-form social-graph surface is usable end to end.
3. Resume topic hubs and scene discovery only after those shared privacy/content primitives exist.
