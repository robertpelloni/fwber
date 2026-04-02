# Version Reference

- **Current version:** `1.0.73`
- **Release date:** `2026-04-02`
- **Release name:** `ActivityPub Inbox Signature Verification`

## Summary

This release hardens inbound federation by requiring valid HTTP signatures on ActivityPub inbox requests. The backend now validates `Signature`, `Date`, and `Digest` headers against the remote actor's published RSA public key before processing Follow, Accept, Undo, or Create activities.

## Version Sources of Truth

- Root version file: `VERSION`
- Root package metadata: `package.json`
- Frontend package metadata: `fwber-frontend/package.json`
- Historical release notes: `CHANGELOG.md`
