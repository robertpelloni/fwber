# Auth Smoke Tests (Postman or curl)

Quick checks to validate authentication flows on a running local server.

## Prerequisites
- Server running locally at http://localhost:8000
- Import the Postman collection at `fwber-backend/storage/api-docs/fwber-postman-collection.json`
- Import the environment `docs/testing/postman/fwber.postman_environment.json`

## Variables
- baseUrl = http://localhost:8000/api
- email = alice@test.local
- password = Test123!@#
- accessToken = (populated after login)

## Steps (Postman)
1. Register
   - POST {{baseUrl}}/auth/register
   - Body: { "email": "{{email}}", "password": "{{password}}", "name": "Alice" }
   - Expect: 201, response.access_token present
   - Postman Test: set `accessToken` = response.access_token

2. Login
   - POST {{baseUrl}}/auth/login
   - Body: { "email": "{{email}}", "password": "{{password}}" }
   - Expect: 200, response.access_token present (overwrite env var)

3. Protected endpoint
   - GET {{baseUrl}}/user
   - Header: Authorization: Bearer {{accessToken}}
   - Expect: 200 with user profile
   - Negative: No Authorization header → 401

4. Logout
   - POST {{baseUrl}}/auth/logout
   - Header: Authorization: Bearer {{accessToken}}
   - Expect: 200
   - Then retry step 3 with same token → expect 401

## Equivalent curl

```bash
# Register
curl -s -X POST "http://localhost:8000/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@test.local","password":"Test123!@#","name":"Alice"}' | tee /tmp/register.json

# Login
curl -s -X POST "http://localhost:8000/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"alice@test.local","password":"Test123!@#"}' | tee /tmp/login.json

# Extract token (jq required)
TOKEN=$(jq -r '.access_token' /tmp/login.json)

# Protected endpoint
curl -i "http://localhost:8000/api/user" -H "Authorization: Bearer $TOKEN"

# Logout
curl -s -X POST "http://localhost:8000/api/auth/logout" -H "Authorization: Bearer $TOKEN"

# Retry protected with old token (should be 401)
curl -i "http://localhost:8000/api/user" -H "Authorization: Bearer $TOKEN"
```

## Notes
- If using a non-local DB, ensure migrations are up to date.
- For a quick local test, you can point to SQLite in `.env` and run `php artisan migrate --seed`.
- Check logs at `storage/logs/laravel.log` if any 500 errors occur.
