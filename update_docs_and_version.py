with open('TODO.md', 'r') as f:
    todo = f.read()

todo = todo.replace("- [ ] **Email Infrastructure**: Configure Resend DNS records (MX, SPF, DKIM, DMARC) for email delivery.", "- [x] **Email Infrastructure**: Configure Resend DNS records (MX, SPF, DKIM, DMARC) for email delivery.")

with open('TODO.md', 'w') as f:
    f.write(todo)

with open('CHANGELOG.md', 'r') as f:
    cl = f.read()

new_log = """## [2.1.10] - 2026-06-22
### Added
- Created `docs/EMAIL_INFRASTRUCTURE.md` to guide production deployments for email deliverability.
### Fixed
- Updated `email.ts` to dynamically load `resend` to prevent ESM crashes when using SMTP fallbacks.
- Fixed a fatal startup crash in `wingman.ts` when `OPENAI_API_KEY` is not provided.

"""
cl = cl.replace("## [2.1.9]", new_log + "## [2.1.9]")

with open('CHANGELOG.md', 'w') as f:
    f.write(cl)
