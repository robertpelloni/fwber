# Frontend Utilities Context

**Parent Context:** [Frontend Root](../../AGENTS.md)

## PURPOSE
Helper functions, shared logic, and type definitions that don't return UI.

## CRITICAL RULES
1.  **Pure Functions:** Prefer pure functions that are easy to test and reason about.
2.  **Type Safety:** Strong typing is non-negotiable. Use Zod for schema validation if dealing with unknown input.
3.  **Date Handling:** Use `date-fns` for date manipulation. Avoid native `Date` object quirks where possible.
4.  **Formatting:** Centralize formatting logic (Currency, Dates, Numbers) here to ensure consistency across the app.
5.  **Utils vs Hooks:** If it uses React state or lifecycle, it's a Hook (`hooks/`). If it's just logic, it's a Lib (`lib/`).

## COMMON PATTERNS
- `utils.ts`: General purpose helpers (className merging with `cn()`).
- `api.ts`: Typed API client wrappers.
- `constants.ts`: App-wide constants.

## ANTI-PATTERNS
- Importing UI components into `lib/` (circular dependency risk).
- "God utils" files with thousands of lines (split by domain: `string-utils.ts`, `date-utils.ts`).
