# Project Status — fwber v1.1.1 (Federated ID & Native NFC)

**Date:** 2026-04-02  
**Version:** 1.1.1 "Federated ID & Native NFC"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Global Federated Identity
- **Decentralized Login:** Users can now use their ActivityPub handle (e.g. Mastodon) to log in to the fwber network. 
- **Shadow User Sync:** Implemented automated local user creation for remote actors, ensuring that federated identities have a persistent home on our nodes.
- **WebFinger Auth:** Secure challenge-response handshake that verifies Actor ownership via profile summary commitments.

## Native Mobile NFC Bridge
- **Hardware Integration:** The mobile app now utilizes `react-native-nfc-manager` to handle tap events.
- **WebView Injection:** Built a real-time JS injection bridge that pushes native NFC scans directly into the web interface, solving browser-based NFC reliability issues on mobile.

## Community Court & Appeals
- **Judicial Layer:** Users restricted by the Council now have a formal path to reinstatement. The `/council/appeals` portal allows banned users to state their case.
- **Direct Democracy:** Appeals are instantly converted into community proposals, ensuring that the same FWB-weighted voting power that banned a user also decides their fate.
- **Graceful Restrictions:** The frontend now detects a `GLOBAL_BAN` state and redirects users away from protected features to the appeals hub without crashing the app.

## Community Moderation DAO
- **Automated Enforcement:** The Council can now vote on `ban_user` and `ban_actor` proposals. Upon passing, the `PolicyExecutor` instantly updates the `global_bans` registry.

## On-Chain Governance Proofs
- **Solana Anchoring:** Finalized Merkle roots from community proposals are now recorded on the Solana blockchain for immutable historical auditing.

## Governance Transparency & Auditing
- **Merkle Roots:** Finalized proposals generate and store a SHA-256 Merkle root of all participant votes.
- **Real-time Notifications:** Integrated WebSocket alerts for the voting lifecycle. 

## Automated Policy & Rule Updates
- **Closed-Loop Governance:** Proposals of type `policy` now automatically execute their associated changes upon passing.

## Global Token Bridge & Economy
- **Asset Bridging:** Users can now swap liquid FWB Tokens for external assets (SOL, USDC).
- **Real-time Rates:** Integrated live price feeds from CoinGecko for precise cross-chain conversion quotes.

## ✅ Release Focus
- [x] Build Global Federated Identity system.
- [x] Build Native Mobile NFC Bridge.
- [x] Build Council Appeal system and UI.
- [x] Implement automated Unban proposals.
