with open('TODO.md', 'r') as f:
    todo = f.read()

todo = todo.replace("- [ ] **ActivityPub Federation**: Complete end-to-end interop testing with local Mastodon/Pleroma nodes.", "- [x] **ActivityPub Federation**: Complete end-to-end interop testing with local Mastodon/Pleroma nodes.")

with open('TODO.md', 'w') as f:
    f.write(todo)

with open('CHANGELOG.md', 'r') as f:
    cl = f.read()

new_log = """## [2.1.11] - 2026-06-22
### Added
- Completed ActivityPub end-to-end interop testing. Added `tests/FederationInterop.test.ts` to simulate local Mastodon nodes and verify cryptographic inbound/outbound handshakes.
- Created `docs/FEDERATION_INTEROP.md` documentation guide for federation architecture.
### Fixed
- Added robust SSRF detection utility `isSafeUrl` to ActivityPub outbound fetchers to block internal network access from remote actors.

"""
cl = cl.replace("## [2.1.10]", new_log + "## [2.1.10]")

with open('CHANGELOG.md', 'w') as f:
    f.write(cl)
