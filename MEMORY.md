# MEMORY.md

## 2026-04-04 — v1.6.1 GitHub Hetzner deploy needed rustup PATH bootstrapping
- The first real GitHub Hetzner deploy failed even though manual Hetzner deploys worked.
- Root cause: GitHub's SSH action executes a non-login shell, so the deploy user's rustup Cargo path was not loaded, and the build fell back to the old system Cargo 1.75.0.
- The deploy script now sources `~/.cargo/env` and prepends `~/.cargo/bin` to PATH before building `fwber-geo`.

## 2026-04-04 — v1.6.0 GitHub backend deploy workflow was still pointed at DreamHost
- The live backend has already been successfully deploying on Hetzner through SSH and `ops/hetzner/scripts/deploy-backend.sh`.
- However, `.github/workflows/deploy-backend.yml` was still configured to SSH into DreamHost, which meant CI automation had drifted behind the real infrastructure cutover.
- The workflow has now been rewritten to target Hetzner instead.
