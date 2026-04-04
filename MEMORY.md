# MEMORY.md

## 2026-04-04 — v1.6.2 GitHub Hetzner deploy is now genuinely validated
- After adding the Hetzner repository secrets and fixing rustup PATH loading, the GitHub `Deploy Backend (Hetzner)` workflow completed successfully end-to-end.
- The key nuance was that the first post-fix run still used the old server-side script content because the remote script is loaded before its own internal `git pull`; the next run used the updated script and succeeded.

## 2026-04-04 — v1.6.1 GitHub Hetzner deploy needed rustup PATH bootstrapping
- The first real GitHub Hetzner deploy failed even though manual Hetzner deploys worked.
- Root cause: GitHub's SSH action executes a non-login shell, so the deploy user's rustup Cargo path was not loaded, and the build fell back to the old system Cargo 1.75.0.
- The deploy script now sources `~/.cargo/env` and prepends `~/.cargo/bin` to PATH before building `fwber-geo`.
