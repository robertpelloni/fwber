# Handoff — ActivityPub Signed Outbound Delivery

**Date:** 2026-04-02  
**Status:** ✅ ActivityPub signed outbound delivery is ready on top of the inbox-signature slice  
**Version:** 1.0.74  
**Latest pushed commit:** `64c4b0f18` (`feat: verify ActivityPub inbox signatures (v1.0.73)`)

## Overview
This handoff covers the next federation protocol slice after `v1.0.73`. The inbound side was already protected by HTTP signature verification, but outbound delivery was still mocked and local actors were still publishing a fake public key. This release closes that remaining protocol gap by generating real actor keypairs, exposing the real public key, and signing outbound Follow / follower-delivery requests before they leave the server.

The implementation also had to widen the existing shared key-storage table carefully so the new ActivityPub keypair would not break the E2E encryption endpoints that were already using `user_public_keys`.

Active release work continues in the clean `C:\Users\hyper\workspace\fwber\temp_build\billing-push-clean` worktree rather than the dirty root checkout.

## What Shipped in v1.0.74

### 1. Dedicated ActivityPub keypair service
- Added `App\Services\ActivityPubKeyService`.
- The service now:
  - generates a 2048-bit RSA keypair for local federated actors
  - stores the public/private key material encrypted at rest
  - publishes the stable local actor URI + `#main-key` key ID
  - lazily provisions the keypair when actor data or outbound delivery needs it

### 2. Shared key-table expansion without breaking E2E
- Added a migration that expands `user_public_keys` with:
  - `private_key`
  - multi-key support via unique `(user_id, key_type)`
- The migration includes a SQLite-safe table rebuild path because dropping the original single-column unique index by a fixed name was not portable across test databases.
- Existing E2E key management now explicitly queries `key_type = ECDH`, so the older `/api/security/keys` behavior survives even though the table now stores ActivityPub RSA keys too.

### 3. Real outbound ActivityPub delivery
- `ActivityPubService::dispatchToRemoteInbox()` is no longer a no-op logger.
- The service now:
  - fetches the remote actor document
  - resolves the remote inbox URL
  - encodes the activity body as ActivityPub JSON
  - signs the request with the local actor private key using the shared HTTP Signature logic
  - performs the outbound POST
- This path is used both by direct follow initiation and by follower broadcast delivery.

### 4. Real actor public-key exposure
- `generateActorPayload()` now pulls the real generated public key from `ActivityPubKeyService`.
- Local actor JSON-LD no longer advertises the old hardcoded `MockPublicKeyForIteration0347` placeholder.
- Remote servers now have a real verification target for both inbound and outbound federation interactions.

### 5. Regression coverage and findings
- Added `ActivityPubOutboundTest` covering:
  - actor endpoint public-key generation/exposure
  - signed outbound follow delivery to a remote inbox
- Re-ran the existing ActivityPub inbox and E2E key suites because the same key table is now shared.
- Important implementation finding:
  - the first migration attempt failed on SQLite because the original unique index name was auto-generated differently than expected, so the final migration rebuilds the table on SQLite rather than relying on a named `dropUnique`.

### 6. Remaining next-step candidates
- The old ActivityPub protocol hardening TODO is now effectively complete for the currently shipped search/follow/outbox scope.
- The next strong repo-backed slices are now things like:
  - merchant portal lifecycle controls
  - AI wingman UI wiring
  - event-sourcing audit

## What Shipped in v1.0.73

### 1. HTTP signature verification service
- Added `App\Services\HttpSignatureService` to verify inbound ActivityPub-style HTTP signatures.
- The service now:
  - parses the `Signature` header
  - validates the request `Date` freshness window
  - validates the `Digest` against the raw request body
  - fetches the remote actor document and extracts the published RSA public key
  - verifies the signature bytes against the reconstructed signed header string
  - rejects actor/key mismatches where the signing key owner does not match the JSON payload actor

### 2. Inbox middleware enforcement
- Added `App\Http\Middleware\VerifyActivityPubSignature`.
- Registered it through the Laravel 11 bootstrap alias map and applied it directly to `Route::post('users/{id}/inbox', ...)`.
- The inbox route now returns `401` for unsigned or invalid federation requests instead of silently processing raw JSON from arbitrary clients.

### 3. Signed-request regression coverage
- Updated the existing `ActivityPubTest` inbox happy-path tests so they generate real RSA keypairs, fake remote actor documents, and send properly signed Follow / Undo / Accept activities.
- Added `ActivityPubSignatureTest` covering:
  - missing `Signature` header rejection
  - invalid signature rejection
  - stale `Date` rejection
  - key-owner / payload-actor mismatch rejection

### 4. Important implementation findings
- The middleware itself worked quickly; the only meaningful bug during implementation was a host mismatch in the signed test helper.
- Laravel's test request host reflects `APP_URL`, which is `localhost:8000` in this backend, so signed test helpers must use that host rather than assuming plain `localhost`.
- The verifier logic is now confirmed against real Laravel request objects, not just isolated Request instances.

### 5. Remaining federation gap
- `ActivityPubService::dispatchToRemoteInbox()` is still mocked and does not yet perform real signed delivery.
- `generateActorPayload()` still returns a mock public key rather than a user-owned generated federation key.
- The next highest-value slice is therefore:
  - local actor key generation/storage
  - real actor public-key exposure
  - signed outbound follow/post delivery

## What Shipped in v1.0.72

### 1. Location write hardening
- `LocationController::update()` previously treated event-store append failure as fatal because the append lived inside the outer request `try/catch`.
- The endpoint now catches event-store append failures locally, logs a warning, and still persists the `user_locations` projection.
- This preserves the user-facing location feature even if the event log infrastructure is temporarily unavailable or misconfigured on DreamHost.

### 2. Photo accessor hardening
- `Photo::getUrlAttribute()` and `Photo::getThumbnailUrlAttribute()` now return empty strings when a legacy row has no path or when storage URL generation throws.
- This prevents `/api/photos` from crashing on malformed or older production rows and keeps the endpoint response usable enough for the frontend to render a recoverable empty-state/photo fallback.

### 3. Safety schema-drift fallback
- `SafetyController::getActiveWalk()` now catches `QueryException` and returns `walk: null` if `safe_walks` is unavailable.
- `SafetyController::getContacts()` now follows the same pattern and returns an empty contacts list on missing-table drift.
- This matches the app’s existing precedent of degrading optional telemetry/secondary features instead of crashing the full API response on legacy schema drift.

### 4. Regression coverage
- `LocationControllerTest` now proves location update still succeeds when `EventStore::append()` throws.
- `PhotoControllerTest` now proves listing photos with null storage paths does not 500.
- New `SafetyControllerTest` proves:
  - active walk lookup works normally
  - active walk lookup degrades to `walk: null` when `safe_walks` is missing

### 5. Root-cause findings
- `bootstrap/app.php` was already logging API exceptions, so the old TODO guidance to “add explicit `Log::error($e)`” was outdated.
- The plausible production-specific failure classes were:
  - **sidecar/event-store coupling** for `/api/location`
  - **legacy/null row assumptions** for `/api/photos`
  - **schema drift / missing optional tables** for `/api/safety/walk/active`
- The release hardens those assumptions instead of widening generic catches everywhere.

## What Shipped in v1.0.65

### 1. Structured interest graph bridge
- Added `InterestGraphService` to:
  - canonicalize profile interests against the existing `topics` taxonomy
  - resolve alias-style values onto stable topic slugs
  - preserve unmatched freeform values instead of dropping them
  - sync matched topics into `User::followedTopics()` without removing prior follows
- This avoids inventing a second topic system and instead bridges the already-live:
  - `UserProfile.interests`
  - `Topic`
  - `followedTopics()`
  - scene/matching surfaces

### 2. Profile API enrichment
- `ProfileController` now attaches structured `interest_topics` to both authenticated and public profile responses.
- `TopicResource` now exposes an optional `match_source` field so clients can tell whether a topic came from:
  - `profile`
  - `followed`
  - `both`
- `UserProfileResource` now includes `interest_topics` alongside the raw `interests` array.

### 3. Live profile editor alignment
- The active `/profile` page now:
  - fetches topic suggestions from `/api/topics`
  - renders topic-backed chips inside the interests section
  - loads the top-level `profile.interests` field into form state
  - merges topic selections with the existing hobby/music/movie/book/sport buckets on save
- This means users no longer have to rely only on the old nested preference buckets for discovery relevance.

### 4. Matching UI alignment
- `MatchFilter` now loads shared-interest chips from the real topics API instead of a duplicated hardcoded constant list.
- A small fallback list remains so the filter still renders if topic fetches fail.

### 5. Validation hardening findings
- Added `node_modules_stale_*` exclusions to `fwber-frontend/tsconfig.json`, which prevents `tsc` from recursing into renamed dependency backup folders.
- The stale `node_modules_stale_20260402151410` folder created during earlier dependency-repair work has now been removed from the clean worktree.
- Fresh lint now reports only the pre-existing warning in:
  - `fwber-frontend/lib/api/photos.ts:476`
- Backend targeted validation for this slice is green:
  - `php artisan test tests/Feature/ProfileUpdateTest.php tests/Feature/SceneDiscoveryFeatureTest.php`
- One fresh frontend build failed after successful compile/static-generation work with:
  - missing `.next\server\app\api\auth\[...nextauth]\route.js.nft.json`
- This matches the repo's earlier pattern of intermittent flaky Next build artifacts in this environment rather than a direct source-code regression, and a clean rebuild from a freshly cleared `.next` directory is in flight.

## Repository State Findings
1. **The original root checkout was not safe to “repair in place.”**
   - `C:\Users\hyper\workspace\fwber` had a stale local `main`, was far behind `origin/main`, and contained a very large dirty tree including tracked artifact churn and unrelated local state.
   - A direct stash/preserve flow was too risky and too slow because of the size and shape of the tracked deletions/artifacts.
2. **The recovery-safe solution was a clean worktree based on upstream.**
   - A clean worktree was created at `C:\Users\hyper\workspace\fwber-mainline-repair`.
   - Work proceeded on branch `repair-referrals-onboarding`, based directly on `origin/main`.
   - The original dirty checkout was intentionally left untouched.
3. **The original checkout still has a safety anchor.**
   - Backup branch retained: `backup-pre-repair-20260402-1809`
   - This preserves the pre-repair local branch pointer/state in the original repository.

## Documentation / Product-State Findings
- The root documentation was internally contradictory.
  - Some files claimed the project was effectively “final” or fully production complete.
  - Other files and the actual upstream history showed active ongoing development and unfinished areas.
- The most trustworthy “what is actually shipped” signal was `origin/main` plus focused validation, not the louder status prose in older docs.
- The release/version state before this slice was `v1.0.63`; this slice updates the repo to `v1.0.64`.

## What Shipped in v1.0.64

### 1. Onboarding progression repair
- Users can now advance through optional onboarding steps without being forced to fill every field immediately.
- The user-reported fitness/physical step no longer traps progression.
- The frontend now sanitizes profile update payloads before sending them:
  - drops `null` / `undefined`
  - drops blank strings
  - drops empty arrays/objects
  - preserves meaningful booleans, numbers, files, and non-empty values
- Problematic onboarding field mismatches are normalized or omitted instead of breaking progression.
- The shared backend profile update contract was loosened for onboarding’s `tattoos` / `piercings` path so the flow is less brittle.
- Added Cypress coverage:
  - `fwber-frontend/cypress/e2e/onboarding-skip.cy.js`

### 2. Referral-code repair
- Legacy users missing `referral_code` are now repaired automatically at runtime.
- `TokenDistributionService` now has an `ensureReferralCode()` path.
- Auth/session-related flows hydrate users through that guarantee so cached frontend auth state is no longer the only source of truth.
- Vouch-link generation also ensures a referral code exists before returning the share URL.
- Added a migration to backfill missing referral codes persistently:
  - `2026_04_02_000001_backfill_missing_referral_codes.php`

### 3. Real referral summary API
- Added backend-owned referral summary endpoint:
  - `GET /api/referrals/summary`
- The summary now returns:
  - referral code
  - invite link
  - vouch link
  - golden ticket count
  - referral count
  - vouch count
  - token balance
  - pending cash reward total
  - earned FWBcoin reward total
  - level-1 / level-2 premium conversion summaries
  - configured reward rules

### 4. Two-level premium referral commissions
- Added a dedicated commission ledger:
  - `referral_commissions`
- Added explicit commission service:
  - `ReferralCommissionService`
- Premium upgrades now award:
  - **Level 1:** `$2.00 + 50 FWBcoin`
  - **Level 2:** `$0.50 + 15 FWBcoin`
- Card/fiat premium purchases record:
  - pending USD payout entries in the commission ledger
  - FWBcoin payouts to uplines
- Token-paid premium purchases record:
  - token-only referral rewards
  - no fake “cash transfer” is implied for token-funded upgrades
- The commission path is idempotent via `commission_key` generation keyed to payment/subscription context.

### 5. Viral Rewards UI repair
- `ReferralModal` no longer trusts nullable cached auth fields as its primary link source.
- It now consumes the backend referral summary API and renders:
  - invite link
  - vouch link
  - reward rule copy
  - pending cash total
  - earned FWBcoin total
  - direct and second-level premium totals
- This closes the user-visible `ref=null` failure mode in the intended UI path.

### 6. Docs / version sync
- Updated:
  - `VERSION`
  - root `package.json`
  - `fwber-frontend/package.json`
  - `fwber-frontend/package-lock.json`
  - `CHANGELOG.md`
  - `VERSION.md`
  - `PROJECT_STATUS.md`
  - `HANDOFF.md`

## Key Backend Files Added / Changed
- `fwber-backend/app/Http/Controllers/AuthController.php`
- `fwber-backend/app/Http/Controllers/PremiumController.php`
- `fwber-backend/app/Http/Controllers/ReferralController.php`
- `fwber-backend/app/Http/Controllers/VouchController.php`
- `fwber-backend/app/Http/Requests/Profile/UpdateProfileRequest.php`
- `fwber-backend/app/Models/ReferralCommission.php`
- `fwber-backend/app/Services/ReferralCommissionService.php`
- `fwber-backend/app/Services/TokenDistributionService.php`
- `fwber-backend/config/referrals.php`
- `fwber-backend/database/migrations/2026_04_02_000000_create_referral_commissions_table.php`
- `fwber-backend/database/migrations/2026_04_02_000001_backfill_missing_referral_codes.php`
- `fwber-backend/routes/api.php`
- `fwber-backend/tests/Feature/ReferralTest.php`
- `fwber-backend/tests/Feature/PremiumControllerTest.php`

## Key Frontend Files Added / Changed
- `fwber-frontend/app/onboarding/page.tsx`
- `fwber-frontend/components/viral/ReferralModal.tsx`
- `fwber-frontend/lib/api/profile.ts`
- `fwber-frontend/cypress/e2e/onboarding-skip.cy.js`
- `fwber-frontend/package.json`
- `fwber-frontend/package-lock.json`

## Validation Status

### Backend: green
Targeted backend validation completed successfully:

```powershell
php artisan test tests/Feature/ReferralTest.php tests/Feature/PremiumControllerTest.php tests/Feature/TokenDistributionTest.php
```

Result:
- **11 tests passed**
- **42 assertions**

Covered behaviors include:
- auth/session referral-code backfill
- referral summary response
- premium purchase with fiat/card path
- premium purchase with token path
- level-1 and level-2 commission awarding
- token distribution baseline behavior

### Frontend: code path repaired, environment validation still being hardened
The remaining instability is not currently pointing at app-code regressions. It is coming from the fresh worktree’s local Windows dependency install state.

Observed frontend validation sequence:
1. Initial `npm run lint` failed because dependencies were not installed in the fresh worktree.
2. Plain `npm install` failed on Windows because a dependency script (`@stellar/stellar-sdk`) expected `yarn` and used shell fallback syntax that does not behave cleanly in this environment.
3. After dependency repair attempts, repeated validation runs exposed partially populated packages inside `node_modules`, including:
   - missing `next/dist/compiled/babel-packages`
   - missing `date-fns/isSameDay.mjs`
   - package roots present without `package.json`
4. Targeted package repairs restored those files, but the cleanest remaining path is a full uninterrupted frontend reinstall in the fresh worktree followed by:

```powershell
npm ci --ignore-scripts
npm run lint
npm run build
npm run type-check
```

### Known frontend validation notes
- Lint still reports a **pre-existing warning**:
  - `fwber-frontend/lib/api/photos.ts:476`
  - `react-hooks/exhaustive-deps`
- Next build still emits the known Sentry warnings/deprecations that were already present before this slice.
- In this shared Windows environment, partially interrupted dependency installs can leave package skeletons in `node_modules`; that was the main frontend validation blocker during this cycle.

## Git / Release State
- Current pushed release: `v1.0.64`
- Current pushed commit: `2f8f84e8a`
- `origin/main` now points at this repair commit.
- The clean worktree branch `repair-referrals-onboarding` is aligned with `origin/main`.

## Important Operational Notes
- Do **not** try to “clean up” the original root checkout destructively.
- Continue any further work from the clean worktree or a fresh checkout from `origin/main`.
- The runtime referral-code backfill is intentionally redundant with the migration so legacy users self-heal immediately even before all environments finish migrations.
- Cash referral rewards are intentionally tracked as **pending ledger entries**, not magically deposited external money.

## Biggest Remaining Risks / Follow-Ups
1. **Frontend dependency validation in the fresh worktree still needs one uninterrupted clean pass.**
   - The code changes themselves are in place and backend behavior is validated.
   - The remaining friction is local install integrity, not a known frontend app logic failure.
2. **Original dirty root checkout still exists and should remain untouched unless explicitly reconciled later.**
3. **The broader repository docs still contain older optimistic/final-state language in places outside the files updated here.**
   - This slice corrected the directly touched release/status docs but did not attempt a full repository-wide doc rewrite.

## Recommended Next Slice
1. Start from `origin/main` at `2f8f84e8a`.
2. Finish one clean frontend validation pass from a fresh, uninterrupted dependency install.
3. If frontend validation is green, decide whether to:
   - leave the root dirty checkout alone permanently, or
   - do a separate, careful repo housekeeping pass
4. After frontend validation is fully green, consider a dedicated documentation reconciliation sweep across remaining contradictory project docs.

## Suggested Resume Procedure
1. Work from `C:\Users\hyper\workspace\fwber-mainline-repair` or a fresh clone of `origin/main`.
2. Re-read:
   - `HANDOFF.md`
   - `PROJECT_STATUS.md`
   - `CHANGELOG.md`
   - session `plan.md`
3. Avoid overlapping Next.js builds in the same checkout.
4. Treat any broken frontend validation signal skeptically until dependency integrity is confirmed.
