# Project Status — fwber v1.0.99 (Moderation DAO & Audit Polish)

**Date:** 2026-04-02  
**Version:** 1.0.99 "Moderation DAO & Audit Polish"
**Status:** ✅ **LOCAL RELEASE VERIFIED AND READY**

---

## Community Moderation DAO
- **Automated Enforcement:** The Council can now vote on `ban_user` and `ban_actor` proposals. Upon passing, the `PolicyExecutor` instantly updates the `global_bans` registry.
- **API Guarding:** Launched the `CheckGlobalBan` middleware which protects the entire fwber API from council-banned entities.
- **ActivityPub Security:** Banned federated actors are now blocked at the protocol level, with their incoming inbox activities automatically dropped.

## On-Chain Audit Transparency
- **Solana Verification:** Updated the `VoteVerifier` to cross-reference local Merkle roots with Solana transaction memos, ensuring community-wide immutability.

## On-Chain Governance Proofs
- **Solana Anchoring:** Finalized Merkle roots from community proposals are now recorded on the Solana blockchain.
- **Immutable History:** This provides a third-party verifiable trail of all governance outcomes, making the platform's policy changes audit-ready and tamper-proof.
- **Explorer Integration:** The Council portal now links directly to on-chain transaction hashes, providing total transparency to the community.

## Mobile Cross-Platform NFC
- **iOS Entitlements:** Enabled full NFC support for iOS devices in the mobile app. 
- **Unified Handshake:** Users on both iOS and Android can now participate in "Flash Matching" and "Tap-to-Pay" transactions using the same native protocol.

## Governance Transparency & Auditing
- **Merkle Roots:** Finalized proposals now generate and store a SHA-256 Merkle root of all participant votes.
- **Real-time Notifications:** Integrated WebSocket alerts for the voting lifecycle. Participants are instantly notified via Laravel Reverb when a proposal shifts from `active` to `passed` or `failed`.

## Automated Policy & Rule Updates
- **Closed-Loop Governance:** Proposals of type `policy` now automatically execute their associated changes upon passing.

## Council Proposal Creation
- **Frontend Submission:** Users can now launch new community proposals directly from the `/council` dashboard.

## Full Federated E2E Security
- **Asymmetric Decryption:** Completed the security loop for cross-server DMs. Users now generate and persist RSA keypairs in the browser.

## Governance Execution & Policy
- **Automated Reconciler:** Launched the `ProcessGovernanceProposals` background job that finalizes expired community proposals. 

## Global Token Bridge & Economy
- **Asset Bridging:** Users can now swap liquid FWB Tokens for external assets (SOL, USDC).
- **Price Simulation:** The swap interface now includes real-time simulated price feeds.

## ✅ Release Focus
- [x] Implement Community Moderation DAO actions.
- [x] Build Global Ban infrastructure and middleware.
- [x] Implement On-Chain Governance mirroring (Solana).
- [x] Enable iOS native NFC entitlements.