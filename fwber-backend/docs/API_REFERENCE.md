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
- Requires a bearer token. Accepts flat profile fields (not nested under `profile`).
- Key fields:
  - `display_name` (string, max 50)
  - `bio` (string, max 500)
  - `date_of_birth` (date; must indicate age >= 18)
  - `gender` (one of: male, female, non-binary, mtf, ftm, other, prefer-not-to-say)
  - `pronouns` (one of: he/him, she/her, they/them, he/they, she/they, other, prefer-not-to-say)
  - `sexual_orientation` (one of: straight, gay, lesbian, bisexual, pansexual, asexual, demisexual, queer, questioning, other, prefer-not-to-say)
  - `relationship_style` (one of: monogamous, non-monogamous, polyamorous, open, swinger, other, prefer-not-to-say)
  - `looking_for` (array of: friendship, dating, relationship, casual, marriage, networking)
  - `location` (object: latitude, longitude, max_distance, city, state)
  - `preferences` (object; includes keys like smoking, drinking, exercise, diet, pets, children, education, age_range_min, age_range_max, body_type, religion, politics, hobbies[], music[], sports[])
- Example request body:
```json
{
  "display_name": "Updated",
  "bio": "Updated bio",
  "date_of_birth": "1995-04-20",
  "gender": "female",
  "looking_for": ["friendship", "dating"],
  "location": { "latitude": 30.2672, "longitude": -97.7431, "city": "Austin", "state": "TX" },
  "preferences": { "exercise": "weekly", "age_range_min": 25, "age_range_max": 40 }
}
```
- Notes:
  - Age is computed from `date_of_birth` in responses; there is no direct `age` column.
  - If `preferences.max_distance` is not set, the API defaults to 25 km when reading.

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
- Responses use a dedicated `UserProfileResource` which nests profile fields under `data.profile` and computes completion info.
