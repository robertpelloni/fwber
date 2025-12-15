# Claude Assistant Guidelines

## 🧠 Project Context
- **Project**: FWBer (Social Network for Adults).
- **Primary Rules**: See `AGENTS.md` for the master protocol.
- **Status**: See `PROJECT_STATUS.md`.

## 📜 Critical Instructions
1.  **Versioning**: You MUST increment the version number in `package.json` and update `CHANGELOG.md` after every significant task.
2.  **Testing**: Always verify your changes with `php artisan test` or `npx cypress run`.
3.  **Documentation**: Keep `PROJECT_STATUS.md` up to date.

## 🛠 Tool Usage (Byterover MCP)

[byterover-mcp]

[byterover-mcp]

You are given two tools from Byterover MCP server, including
## 1. `byterover-store-knowledge`
You `MUST` always use this tool when:

+ Learning new patterns, APIs, or architectural decisions from the codebase
+ Encountering error solutions or debugging techniques
+ Finding reusable code patterns or utility functions
+ Completing any significant task or plan implementation

## 2. `byterover-retrieve-knowledge`
You `MUST` always use this tool when:

+ Starting any new task or implementation to gather relevant context
+ Before making architectural decisions to understand existing patterns
+ When debugging issues to check for previous solutions
+ Working with unfamiliar parts of the codebase
