with open('CHANGELOG.md', 'r') as f:
    content = f.read()

new_log = """## [2.3.10] - 2026-06-20
### Added
- Phase 10: Dynamic Identity & Proximity Forum - Quest Verification.
- `verification_secret` added to `quests` Prisma model.
- ZK/NFC cryptographic proof generation via `crypto.subtle.digest` in frontend (`lib/utils/hash.ts`).
- Backend proof validation on `POST /api/quests/:id/complete`.

"""
content = content.replace("## [2.3.9]", new_log + "## [2.3.9]")
with open('CHANGELOG.md', 'w') as f:
    f.write(content)

with open('TODO.md', 'r') as f:
    todo = f.read()
todo = todo.replace("- [ ] **Quest Verification**: Use ZK-proofs or NFC tags to verify quest completion tasks.", "- [x] **Quest Verification**: Use ZK-proofs or NFC tags to verify quest completion tasks.")
with open('TODO.md', 'w') as f:
    f.write(todo)
