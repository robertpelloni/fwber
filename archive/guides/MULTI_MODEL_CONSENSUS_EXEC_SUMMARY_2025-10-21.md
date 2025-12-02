# FWBER Multi-Model Consensus Execution Summary — 2025-10-21

Context
- Project: PHP web app with AI matching endpoints and MCP orchestration.
- Key references: [AI_COORDINATION/orchestrator_state.json](AI_COORDINATION/orchestrator_state.json), [docker-compose-chroma.yml](docker-compose-chroma.yml), [start-chroma.ps1](start-chroma.ps1), [consolidated-mcp-server.js](consolidated-mcp-server.js), [docs/mcp-servers/MCP_SERVERS.md](docs/mcp-servers/MCP_SERVERS.md), [docs/mcp-servers/CHROMA_MCP_INTEGRATION_GUIDE.md](docs/mcp-servers/CHROMA_MCP_INTEGRATION_GUIDE.md).

Consensus Run (via Zen MCP)
- Models/stances scheduled: gemini-2.5-pro (for), gpt-5-pro (against), gpt-5-codex (neutral), grok-4 (neutral).
- Outcomes: All four consultations errored due to credentials/credits.
  - Gemini: API_KEY_INVALID (400) — fix Google Generative Language key or route via OpenRouter.
  - OpenAI (gpt-5-pro, gpt-5-codex): invalid_api_key (401) — fix OPENAI_API_KEY or route via OpenRouter.
  - X.AI (grok-4): 403 team lacks credits — purchase credits or disable until funded.
- Orchestrator history shows one prior single-model run; true multi-model usage not yet exercised.

Decisions and Architecture
1) Orchestration topology
- Adopt modular MCP servers per capability (vector, moderation, avatar, matching-assist) behind a thin consolidated gateway for dev/local parity.
- Retain the consolidated server to support single-process/local flows.
- Define typed tool contracts, versioned interfaces, and enforce in CI.

2) Operationalizing debate
- Use Zen consensus as a CI pre-merge gate for high-risk areas (matching logic, security-sensitive code, data flows).
- Runtime: prefer cached prior decisions; degrade to a single-provider fallback with strict timeouts and cost ceilings; structured logging + metrics.
- Fallback routing: when native providers not configured, use OpenRouter routes for Google/OpenAI.

3) Chroma vector pipeline
- Scope: profiles, venues, personas; stable schema + versioned namespace.
- Sources: DB records and [db/test-personas.md](db/test-personas.md); nightly refresh + on-write hooks.
- Artifact path: [AI_COORDINATION/chroma/ingest_2025-10-21.json](AI_COORDINATION/chroma/ingest_2025-10-21.json).
- Infra: [docker-compose-chroma.yml](docker-compose-chroma.yml), [start-chroma.ps1](start-chroma.ps1).

4) Security/ops guardrails
- Secrets via .env and [secure-config.php](secure-config.php); never commit keys.
- Add endpoint auditing, request IDs, rate limiting, abuse controls; minimize/anonymize PII at rest and in prompts.
- Maintain rollback readiness (see [docs/implementation/ROLLBACK_INSTRUCTIONS.md](docs/implementation/ROLLBACK_INSTRUCTIONS.md)).

5) Migration plan
- Phase 0: CI-only consensus; fix provider credentials and/or route via OpenRouter; collect artifacts.
- Phase 1: feature-gated runtime consultations for sensitive actions with cache + fallback provider.
- Phase 2: broad rollout with SLOs, observability, rollback.

Artifacts Produced
- Chroma ingest: [AI_COORDINATION/chroma/ingest_2025-10-21.json](AI_COORDINATION/chroma/ingest_2025-10-21.json)
- Orchestrator state: [AI_COORDINATION/orchestrator_state.json](AI_COORDINATION/orchestrator_state.json)
- Serena memory: CONSENSUS_AND_ARCH_DECISIONS_2025-10-21 (internal)
- Knowledge graph entities/relations recorded via memory MCP server (internal)

Immediate Actions
- Credentials & routing
  - Set valid Google Generative Language API key, OpenAI API key; ensure X.AI team credits.
  - Configure OpenRouter fallbacks for google/openai models if native providers remain unconfigured.
  - Success criterion: at least two models return non-error responses in a re-run.
- CI consensus gate
  - Add a CI job invoking zen consensus with caching/timeouts and artifacts persisted under test-results/.
  - Gate PRs when consensus confidence is below threshold or fewer than 2 models respond.
- Chroma integration
  - Stand up Chroma (start script/compose), ingest seed JSON, and wire top-k retrieval into matching flow.
  - Measure lift on test personas; publish metrics snapshot.

Risk Register
- Latency/cost of runtime multi-model: keep runtime minimal; CI-centric debate; cache; cap requests.
- Embedding schema drift: versioned namespaces, contract tests, migration scripts.
- Provider outages/quotas: multi-provider fallback via OpenRouter; circuit breakers; SLOs.

References
- State: [AI_COORDINATION/orchestrator_state.json](AI_COORDINATION/orchestrator_state.json)
- Vector: [AI_COORDINATION/chroma/ingest_2025-10-21.json](AI_COORDINATION/chroma/ingest_2025-10-21.json), [docker-compose-chroma.yml](docker-compose-chroma.yml), [start-chroma.ps1](start-chroma.ps1)
- MCP: [consolidated-mcp-server.js](consolidated-mcp-server.js), [docs/mcp-servers/MCP_SERVERS.md](docs/mcp-servers/MCP_SERVERS.md), [docs/mcp-servers/CHROMA_MCP_INTEGRATION_GUIDE.md](docs/mcp-servers/CHROMA_MCP_INTEGRATION_GUIDE.md)
