
## Structural Enhancements
- Refactor frontend proxy routing: Instead of creating a Next.js `/api/.../route.ts` for every backend gap, implement a universal `[[...slug]]` proxy middleware to catch and securely forward all traffic to the Express service, massively reducing boilerplate.
- Abstract the `ContentUnlockGate.tsx` logic into a higher-order component to ensure premium gating on future views like `burner-links` analytics.
