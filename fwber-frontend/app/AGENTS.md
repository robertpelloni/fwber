# Next.js App Router Context

**Parent Context:** [Frontend Root](../../AGENTS.md)

## PURPOSE
Application routing, pages, and layouts using Next.js 14 App Router.

## CRITICAL RULES
1.  **Server Components:** Default to Server Components. Add `"use client"` ONLY when interactivity (hooks, event listeners) is needed.
2.  **Data Fetching:** Fetch data in Server Components where possible.
3.  **Metadata:** Define `generateMetadata` or static `metadata` for SEO.
4.  **Loading/Error:** Use `loading.tsx` and `error.tsx` for granular state handling.
5.  **Layouts:** Use `layout.tsx` for shared UI across routes.

## DIRECTORY STRUCTURE
- `page.tsx`: The UI for a route.
- `layout.tsx`: Shared UI for a subtree.
- `loading.tsx`: Loading UI.
- `error.tsx`: Error UI.
- `route.ts`: API Routes (Route Handlers).

## ANTI-PATTERNS
- Fetching data in `useEffect` when it could be done on the server.
- Prop drilling deep into client components (use Context or Composition).
