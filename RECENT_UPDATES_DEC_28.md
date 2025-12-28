# Recent Updates - December 28, 2025

## Critical Fixes
- **Resolved Circular Dependency in Realtime Components**:
  - Identified a circular import in `components/realtime/ProximityPresenceView.tsx`.
  - It was importing `OnlineUsersList`, `PresenceIndicator`, etc. from `@/components/realtime` (the barrel file), which exports `ProximityPresenceView`.
  - Changed the import to point directly to `./PresenceComponents`, breaking the cycle.

## Technical Details
- **Cycle**: `components/realtime/index.ts` -> `ProximityPresenceView.tsx` -> `components/realtime/index.ts`.
- **Fix**: Direct import in `ProximityPresenceView.tsx`.
- **Verification**: Checked other files in `components/realtime` and `lib/vault` for similar patterns. No other cycles found.

## Next Steps
- Redeploy and verify `www.fwber.me`.
- The "Module factory not available" error should be resolved.
