# TODO — fwber Immediate Action Items

> **Version:** 2.0.16
> **Last Updated:** 2026-05-23

---

## 🔴 Critical: Federation Hardening & UI
- [x] **Remote Actor Persistence**: Implement logic to store external federated users in the local DB. (v2.0.14)
- [x] **Signed Outbound Delivery**: Implement `broadcastUpdate` with real HTTP signatures in `FederationService.ts`. (v2.0.14)
- [x] **UI Polish**: Add "Remote" badges and interactive follow states to the /federation hub. (v2.0.14)
- [x] **Handshake Completion**: Implemented signed 'Accept' activities back to remote servers. (v2.0.15)
- [x] **Outbox Automation**: Wired proximity artifact creation to ActivityPub broadcasting. (v2.0.15)
- [x] **Activity Center Logic**: Implement real data fetching for the Activity Center from `federation_inbox`. (v2.0.16)
- [x] **Interaction Support**: Added `Like` and `Announce` (Boost) processing to the inbox. (v2.0.16)
- [ ] **Fediverse Interop Testing**: Spin up a local Mastodon/Pleroma dev instance and attempt to search for a local `fwber` user handle to test the `webfinger` and `actor` endpoints end-to-end.

[... Legacy entries truncated ...]
