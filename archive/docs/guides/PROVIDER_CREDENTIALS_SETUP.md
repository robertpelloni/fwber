# Provider Credentials and Routing Setup

This guide enables successful multi-model consensus and runtime fallbacks by configuring provider API keys and OpenRouter routing. It complements the CI gate in [.github/workflows/consensus-gate.yml](.github/workflows/consensus-gate.yml) and local scripts under [scripts](scripts).

Key references
- Orchestrator state: [AI_COORDINATION/orchestrator_state.json](AI_COORDINATION/orchestrator_state.json)
- CI gate: [.github/workflows/consensus-gate.yml](.github/workflows/consensus-gate.yml), [scripts/consensus_gate.js](scripts/consensus_gate.js)
- Chroma infra: [docker-compose-chroma.yml](docker-compose-chroma.yml), [start-chroma.ps1](start-chroma.ps1)
- MCP docs: [docs/mcp-servers/MCP_SERVERS.md](docs/mcp-servers/MCP_SERVERS.md), [docs/mcp-servers/CHROMA_MCP_INTEGRATION_GUIDE.md](docs/mcp-servers/CHROMA_MCP_INTEGRATION_GUIDE.md)

Required Secrets
- OPENAI_API_KEY — OpenAI dashboard → API keys
- GOOGLE_API_KEY — Google Generative Language (Gemini) API key
- XAI_API_KEY — X.AI (Grok) team API key; ensure team has credits
- OPENROUTER_API_KEY — OpenRouter API key (fallback routing for Google/OpenAI)

GitHub Actions configuration
1) Go to GitHub → Settings → Secrets and variables → Actions → New repository secret.
2) Add the following (exact names):
   - OPENAI_API_KEY
   - GOOGLE_API_KEY
   - XAI_API_KEY
   - OPENROUTER_API_KEY
3) The workflow [.github/workflows/consensus-gate.yml](.github/workflows/consensus-gate.yml) already exports these into env for use by jobs.
4) Initially, the gate runs in SOFT_FAIL mode until providers are validated. Flip SOFT_FAIL: "false" after keys are confirmed.

Local environment (.env)
- Copy [.env.example](.env.example) to .env and add the following entries if not present:
  - OPENAI_API_KEY=sk-...
  - GOOGLE_API_KEY=...
  - XAI_API_KEY=...
  - OPENROUTER_API_KEY=...
- Never commit .env. Ensure [secure-config.php](secure-config.php) reads env values and that secrets are not logged.

Validation steps
- List available models via Zen MCP server (already validated in-tool): ensure credentials allow direct provider calls.
- If direct providers fail, verify OpenRouter routing for Google/OpenAI:
  - Example routed models visible in Zen listmodels: openai/gpt-5-pro, google/gemini-2.5-pro.
- Re-run CI workflow "consensus-gate" via Actions → Run workflow (workflow_dispatch) and confirm at least two models respond without error.

Local testing utilities
- PowerShell harnesses:
  - [test-mcp-servers.ps1](test-mcp-servers.ps1)
  - [test-optimized-setup.ps1](test-optimized-setup.ps1)
  - [test-mcp-servers-windows.ps1](test-mcp-servers-windows.ps1) (if present)
- Node script:
  - [test-mcp-servers.js](test-mcp-servers.js)
- Typical sequence:
  1) Set env in shell or .env
  2) Run tests to confirm handshake → model call succeeds

OpenRouter fallback strategy
- When native providers are not configured or restricted, use OpenRouter for Google/OpenAI routes.
- Keep native provider keys in place for best performance, and fall back to OpenRouter when rate-limited, missing keys, or regional restrictions apply.

Troubleshooting
- Gemini 400 INVALID_ARGUMENT API_KEY_INVALID: verify GOOGLE_API_KEY and project-level API enablement.
- OpenAI 401 invalid_api_key: verify OPENAI_API_KEY (no braces/placeholders) and account status.
- X.AI 403 no credits: fund the team in X.AI console; rotate keys if permission errors persist.
- CI gate failing for staleness: update [AI_COORDINATION/orchestrator_state.json](AI_COORDINATION/orchestrator_state.json) after a successful consensus run, or temporarily increase ALLOW_STALE_DAYS in the workflow.

Exit criteria for enabling hard gate
- At least two models respond successfully in a zen consensus run.
- [.github/workflows/consensus-gate.yml](.github/workflows/consensus-gate.yml): set SOFT_FAIL: "false" and ALLOW_STALE_DAYS to a reasonable value (e.g., 14).
- Confirm artifacts are uploaded under the workflow run, and that [AI_COORDINATION/orchestrator_state.json](AI_COORDINATION/orchestrator_state.json) timestamps update.
