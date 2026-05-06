# TODO — fwber Immediate Action Items

> **Version:** 2.0.8
> **Last Updated:** $(date +%Y-%m-%d)

---

## 🔴 Critical: Data Migration & Deployment
- [ ] **Database Migration**: Run Prisma migrations on the Hetzner staging/production database to generate the new ActivityPub columns and tables.
- [ ] **Deployment Verification**: Deploy the backend and frontend, ensuring no regressions in the core flow.
- [ ] **Fediverse Interop Testing**: Spin up a local Mastodon/Pleroma dev instance and attempt to search for a local `fwber` user handle to test the `webfinger` and `actor` endpoints end-to-end.

## 🟡 High: Map Hydration & Polish
- [x] **Map Live Connection**: Verify the Socket.io `location_updated` events are correctly firing on the frontend and visually moving the leaflet map markers.
- [x] **Map Geolocation Watch**: Bind the browser's `navigator.geolocation.watchPosition` to the Socket.io `update_location` emitter to ensure the user's position constantly broadcasts while the event page is open.

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
- [x] **Event Map UI**: Update the frontend `/events` route to display active users on a Leaflet map if they've checked into the event.
- [x] **Group Spatial Indexing**: Extend `GeoScreenerService` to support querying users restricted to a specific `event_id`.
- [x] **Socket Broadcaster**: Extend `socket.ts` to emit real-time coordinate updates only to users subscribed to the same `event_id` room.
