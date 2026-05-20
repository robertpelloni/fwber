# TODO — fwber Immediate Action Items

> **Version:** 2.0.11
> **Last Updated:** $(date +%Y-%m-%d)

---

## 🔴 Critical: Data Migration & Deployment
- [x] **Database Migration**: Run Prisma migrations on the Hetzner staging/production database to generate the new ActivityPub columns and tables.
- [ ] **Deployment Verification**: Deploy the backend and frontend, ensuring no regressions in the core flow.
- [ ] **Fediverse Interop Testing**: Spin up a local Mastodon/Pleroma dev instance and attempt to search for a local `fwber` user handle to test the `webfinger` and `actor` endpoints end-to-end.

## 🟡 High: Mobile & Notifications Polish
- [x] **Mobile Store Prep:** Verify all native Expo capabilities (NFC, Push) against final iOS/Android store guidelines for distribution.
- [x] **Engagement Push:** Leverage the restored Referral & Payout system to drive early adopter signups.

## ✅ Completed (v2.0 Phase 8)
- [x] **Event Map Frontend**: Built `EventMap.tsx` using `react-leaflet` to visualize attendee coordinates in real time on the `/events/[id]` page.
- [x] **Live Socket Broadcasting**: Added `join_event`, `leave_event`, and `update_location` to `fwber-backend-ts/src/socket.ts` to power the live map functionality.
- [x] **Map Geolocation Watch**: Bound the browser's `navigator.geolocation.watchPosition` to the Socket.io `update_location` emitter to ensure the user's position constantly broadcasts while the event page is open.
- [x] **TypeScript Stabilization**: Repaired Prisma schema validation errors and exported backend enum usage to allow the project to build cleanly after merging `main`.

## ✅ Completed (v2.0 Phase 7)
- [x] **ActivityPub Models**: Define Prisma models for `actors`, `follows`, `inbox`, and `outbox`.
- [x] **Federation Keys**: Generate RSA keypairs for users upon registration and implement HTTP Signatures for secure ActivityPub payload transmission.
- [x] **WebFinger Service**: Implement `.well-known/webfinger` endpoint to allow external Fediverse discovery.
- [x] **Inbox Handling**: Build an endpoint to accept `Follow`, `Accept`, `Like`, and `Create` activities from external instances.
- [x] **Outbox Sync**: Hook into internal app actions (e.g., posting a bulletin or changing a bio) to distribute `Update` and `Create` payloads to followers.
- [x] **Federation E2E tests**: Write supertest specs confirming WebFinger and Actor resolution.
- [x] **Merchant Subscriptions**: Hook up Stripe webhooks to automatically activate Merchant Premium tiers based on verified payments.
- [x] **Crypto Loyalty Hooks**: Connect the internal token system (FWB) to an external chain (e.g., Solana via an RPC) for minting loyalty NFTs.
- [x] **Marketplace Integration**: Expose Merchant premium tiers on the frontend `/merchant/dashboard`.
- [x] **NFT display**: Show Loyalty Badges (mocked NFTs) on user profiles if they've earned them from a merchant.
- [x] **Minting UI**: Add a "Mint to Wallet" button in the Merchant UI to manually trigger the Solana service for specific patrons.
- [x] **Group Spatial Indexing**: Extend `GeoScreenerService` to support querying users restricted to a specific `event_id`.
