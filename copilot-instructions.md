# Copilot Instructions

You are GitHub Copilot. Your job is to keep this project on track by shipping high-quality code, documenting changes, and strictly following the versioning protocol.

## 🧠 Project Context
- **Stack**: Laravel 12 (Backend) + Next.js 14 (Frontend).
- **Phase**: Post-Launch Monitoring & Growth.
- **Status**: See `PROJECT_STATUS.md`.
- **Rules**: See `AGENTS.md`.

## 📜 The Golden Rule: Versioning
**EVERY significant task completion or build MUST result in a version number increment.**
1.  **Bump Version**: Update `package.json` and `fwber-frontend/package.json`.
2.  **Log Change**: Add a detailed entry to `CHANGELOG.md`.
3.  **Display**: Ensure the UI reflects the new version.

## 🛠 Standard Development Strategy
1.  **Understand**: Read `PROJECT_STATUS.md` and `AGENTS.md` first.
2.  **Plan**: Create a checklist.
3.  **Implement**: Make small, reversible changes.
4.  **Test**: Run `php artisan test` (Backend) and `npx cypress run` (Frontend).
5.  **Version & Document**: Bump version, update Changelog, update Status.

## 🚫 Guardrails
- **Do not** implement disabled features without checking `docs/FEATURE_FLAGS.md`.
- **Do not** break existing E2E tests.
- **Do not** leave `TODO` comments without a plan.

## 📂 Key Files
- **Status**: `PROJECT_STATUS.md`
- **Roadmap**: `docs/ROADMAP.md`
- **API Docs**: `docs/API_DOCS.md`
- **Features**: `docs/FEATURE_FLAGS.md`

## OpenAPI Documentation Rules
- Keep reusable `@OA\Schema` definitions inside `fwber-backend/app/Http/Controllers/Schemas.php`.
- Annotate endpoints directly in their controllers.
- Run `php artisan l5-swagger:generate` to verify.

## When in doubt
- Check `AGENTS.md` for the definitive protocol.

