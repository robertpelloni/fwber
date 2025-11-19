# Agents Overview

This repository uses a standardized, MVP‑first development strategy enforced by feature flags and documented workflows. Use this file as a quick launcher to the relevant guidance for different AI assistants.

## Quick links
- Repository standards and guardrails: `./AGENTS.md`
- GitHub Copilot instructions: `./copilot-instructions.md`
- Claude model guidance: `./docs/ai-models/CLAUDE.md`
- Multi‑model workflow: `./docs/guides/WORKFLOW_GUIDE.md`
- Project scope & roadmap: `./PROJECT_ANALYSIS_AND_ROADMAP_2025-11-15.md`
- Feature flags (toggle non‑MVP features): `./fwber-backend/config/features.php`
- Swagger config (annotation scan paths): `./fwber-backend/config/l5-swagger.php`

## Core principles (applies to all agents)
- Ship the MVP first. Non‑MVP routes/features must be gated with `->middleware('feature:<flag>')`.
- Keep changes small, focused, and reversible. Avoid refactors or formatting changes unrelated to the task.
- Read only what you need. Prefer targeted edits over broad rewrites.
- Test early: validate routes and regenerate OpenAPI docs.
- Document changes: update Swagger annotations and relevant guides.

## Laravel + Swagger quick facts
- Backend root: `fwber-backend/`
- Routes: `fwber-backend/routes/api.php`
- Middleware aliases: `fwber-backend/bootstrap/app.php`
- Feature flags: `fwber-backend/config/features.php` (middleware alias `feature`)
- Reusable OpenAPI schemas: `fwber-backend/app/Http/Controllers/Schemas.php` (file‑level docblock)
- Swagger scan paths: `fwber-backend/config/l5-swagger.php`

## When in doubt
Prefer the smallest change that ships value without expanding scope. Propose options, call out trade‑offs, and keep advanced features behind flags so they can be enabled later without code churn.
