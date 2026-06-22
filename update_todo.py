with open('TODO.md', 'r') as f:
    todo = f.read()

todo = todo.replace("- [ ] **Email Infrastructure**: Configure Resend DNS records (MX, SPF, DKIM, DMARC) for email delivery.", "- [x] **Email Infrastructure**: Configure Resend DNS records (MX, SPF, DKIM, DMARC) for email delivery.")

with open('TODO.md', 'w') as f:
    f.write(todo)

with open('CHANGELOG.md', 'r') as f:
    cl = f.read()

new_log = """## [2.3.11] - 2026-06-20
### Added
- Created `docs/EMAIL_INFRASTRUCTURE.md` to guide production deployments for email deliverability.
### Fixed
- Updated `email.ts` to dynamically load `resend` to prevent ESM crashes when using SMTP fallbacks.

"""
cl = cl.replace("## [2.3.10]", new_log + "## [2.3.10]")

with open('CHANGELOG.md', 'w') as f:
    f.write(cl)
