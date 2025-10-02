# FWBer API Quick Reference

The Laravel API currently exposes authentication and profile endpoints used by the Next.js client.

## Base URL
- Local default: http://localhost:8000/api
- Authorization header: `Authorization: Bearer <token>`

## Authentication

### Register
- POST /api/auth/register
- Accepts name, email, password, password_confirmation, and optional profile data.
- Example request body:
```json
{
  "name": "Example User",
  "email": "user@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "profile": {
    "display_name": "Example"
  }
}
```

### Login
- POST /api/auth/login
- Example request body:
```json
{
  "email": "user@example.com",
  "password": "password123"
}
```
- Response payloads mirror the register endpoint: JSON with `token` and `user` keys.

### Logout
- POST /api/auth/logout
- Requires a bearer token and returns HTTP 204 with no body.

## Profile

### Fetch current user
- GET /api/user
- Requires a bearer token.
- Returns the authenticated user with the related profile (if present).

### Update current user
- PUT /api/user
- Requires a bearer token and accepts `name` plus nested `profile` attributes.
- Example request body:
```json
{
  "name": "Updated User",
  "profile": {
    "display_name": "Updated",
    "bio": "Updated bio"
  }
}
```

## Matches

### List matches
- GET /api/matches
- Requires a bearer token.
- Returns a list of candidate profiles with compatibility metadata.
- Example response snippet:
```json
{
  "matches": [
    {
      "id": 17,
      "name": "Candidate 1",
      "email": "candidate1@example.com",
      "avatar_url": null,
      "bio": "Bio 1",
      "location_description": "Austin, TX",
      "compatibility_score": 83
    }
  ]
}
```
- Compatibility scores currently use a deterministic placeholder (values between 50 and 100) until the production matching engine lands.

## Additional Notes
- Tokens are stored in the `api_tokens` table and must be sent in the Authorization header for protected routes.
- Automated tests rely on running database migrations; install PDO MySQL or SQLite drivers locally to avoid migration failures.
- Response formatting is currently raw Eloquent models; add dedicated API resources before exposing the endpoints publicly.
