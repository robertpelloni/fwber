# FWBer Postman Collection

This folder contains a ready-to-import Postman collection for the FWBer API.

## Files
- `FWBer-API.postman_collection.json` — Collection with core endpoints grouped by feature.

## Usage
1. Start the Laravel dev server:
   
   ```bash
   php artisan serve --host 127.0.0.1 --port 8000
   ```

2. Import the collection into Postman:
   - Postman > Import > Select `FWBer-API.postman_collection.json`.

3. Set environment variables:
   - `baseUrl` (defaults to `http://127.0.0.1:8000/api` in the collection)
   - `token` (after logging in, paste the bearer token)

4. Try the flows:
   - Register → Login → Copy token → Set `token`
   - Profile: Get/Update
   - Matches: List → Action
   - Messages: Send
   - Groups: Create → Join → Stats
   - Physical Profile: Upsert → Request Avatar
   - Health: Check services

If you prefer, you can import the OpenAPI spec directly instead of using the Postman collection:
- OpenAPI JSON: `storage/api-docs/api-docs.json`
- Swagger UI: `/docs` (or `/api/documentation` if configured)
