# Version Reference

- **Current version:** `1.0.74`
- **Release date:** `2026-04-02`
- **Release name:** `ActivityPub Signed Outbound Delivery`

## Summary

This release closes the outbound side of the ActivityPub hardening work. The backend now generates a dedicated RSA keypair for local actors, exposes the real public key in actor payloads, and sends signed Follow or follower-broadcast activities to remote inboxes instead of just logging mocked dispatch intent.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
