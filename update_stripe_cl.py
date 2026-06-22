with open('CHANGELOG.md', 'r') as f:
    cl = f.read()

new_log = """## [2.3.12] - 2026-06-20
### Added
- Created `docs/PAYMENTS_INFRASTRUCTURE.md` detailing the transition from Stripe test mode to live production keys.
- Updated `.env.example` with standard Stripe environment variables.

"""
cl = cl.replace("## [2.3.11]", new_log + "## [2.3.11]")

with open('CHANGELOG.md', 'w') as f:
    f.write(cl)
