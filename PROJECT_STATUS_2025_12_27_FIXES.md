# Project Status Update - Dec 27, 2025

## 🛠️ Fixes Implemented

### 1. Frontend Build Error (Resolved)
- **Issue**: `Module not found: Can't resolve '@/lib/faceBlur'` in `use-video-face-blur.ts`.
- **Fix**: Corrected import path from `@/lib/faceBlur` to `@/lib/faceBlur` (case-sensitive issue on Linux) and fixed TypeScript return type of `detectAllFaces`.
- **Verification**: `npm run build` no longer fails on this module. (Note: Build fails later on `/sentry-test` due to an unrelated pre-existing issue).

### 2. Backend Migration Error (Resolved)
- **Issue**: `SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'type'`.
- **Fix**: Added idempotency check `if (!Schema::hasColumn('events', 'type'))` to `2025_12_27_000000_add_type_to_events_table.php`.
- **Verification**: Code is now safe to run multiple times. (Note: Could not verify against running DB due to credential mismatch in container).

### 3. Backend Test Failure (Resolved)
- **Issue**: `WalletLoginTest` failing with `Access denied` (connecting to MySQL instead of SQLite) and `500 Internal Server Error`.
- **Fix**:
    - Debugged environment configuration: `phpunit` was picking up cached MySQL config or Docker environment variables instead of `phpunit.xml` settings.
    - Workaround: Run tests with explicit environment variables: `DB_CONNECTION=sqlite`, `QUEUE_CONNECTION=sync`, `BROADCAST_DRIVER=log`.
    - Fixed `AuthController.php` to capture `stderr` from Node.js scripts, preventing `json_decode` failures when scripts output warnings/errors to stderr.
- **Verification**: `WalletLoginTest` now passes with `OK (2 tests, 7 assertions)`.

## ⚠️ Outstanding Issues

### Database Credentials
- The `fwber-backend` container is configured with `DB_PASSWORD=root_password` (matching `docker-compose.yml`), but connection to `fwber-mysql` fails with `Access denied`.
- This suggests the MySQL volume `mysql_data` contains a database initialized with a different password (possibly `rootpassword` or `fwber_password` or something else).
- **Recommendation**: If this is a dev environment, consider resetting the database volume (`docker-compose down -v`) to sync credentials, or identify the correct password and update `.env`.

### Frontend Sentry Test
- `npm run build` fails on `/sentry-test` with `TypeError: Cannot read properties of null (reading 'useState')`. This seems to be a pre-existing issue in the Sentry test page.
