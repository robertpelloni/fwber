# fwber Federation Interoperability Guide

## Overview
As part of Phase 9 (Social Velocity & Federation), fwber has implemented end-to-end ActivityPub interoperability. This allows fwber instances to communicate seamlessly with other fediverse platforms such as Mastodon, Pleroma, and Misskey.

## Supported Protocols
The current `FederationService` fully implements:
1. **WebFinger Discovery**: Resolving `acct:user@domain.com` into valid `application/activity+json` Actor URIs.
2. **Actor Profiles**: Serving fwber users as standard `Person` ActivityStreams objects with attached RSA public keys.
3. **HTTP Signatures (Outbound & Inbound)**:
   - All outbound POST requests to remote inboxes are cryptographically signed (`RSA-SHA256`) using the local user's private key.
   - Inbound signatures are verified by fetching the remote actor's public key (with strict SSRF network mitigation).
4. **Activity Support**:
   - `Follow` / `Undo Follow`
   - `Accept`
   - `Like` / `Undo Like`
   - `Announce` (Boost) / `Undo Announce`
   - `Create` (Note/Post)

## Local Interop Testing Strategy
For development and continuous integration without the heavy overhead of standing up full Mastodon Docker containers, we utilize **Mock Express Actors**.

The script `tests/FederationInterop.test.ts` dynamically spins up an ephemeral web server on port `8082`. This server mimics a standard Mastodon node by:
1. Providing a mock `/.well-known/webfinger` endpoint.
2. Serving an ActivityPub compliant Actor JSON-LD payload with a dynamically generated public key.
3. Hosting a secure `/inbox` endpoint to catch and assert cryptographic broadcasts.

### Running the Interop Test
```bash
cd fwber-backend-ts
node --experimental-vm-modules node_modules/jest/bin/jest.js tests/FederationInterop.test.ts
```

## Production Compatibility
Our payload structures and HTTP signature headers have been mapped exactly to the Mastodon specs. When deploying, ensure:
1. SSL/TLS is fully active (`https:`), as federation strictly rejects non-HTTPS traffic (except in `NODE_ENV=test`).
2. The domain configured in your `.env` perfectly matches the domain your server receives traffic from. Signature verification fails instantly on domain mismatch.
