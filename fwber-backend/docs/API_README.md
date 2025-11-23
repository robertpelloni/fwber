# FWBer API Documentation

This directory contains comprehensive API documentation for the FWBer platform.

## Quick Links

- **Live API Docs**: Visit `/docs` on your running server (e.g., `http://localhost:8000/docs`)
- **OpenAPI Spec**: `storage/api-docs/api-docs.json`
- **Postman Collection**: `storage/api-docs/fwber-postman-collection.json`

## Available Endpoints

The FWBer API is organized into the following domains:

### Core Features
- **Authentication** - JWT-based login, registration, logout
- **Profile** - User profile management and settings
- **Dashboard** - User activity overview and statistics
- **Matches** - Match discovery, actions (like/pass), and match management
- **Messages** - Direct messaging between matched users
- **Physical Profile** - Height, build, ethnicity preferences
- **Profile Views** - Track who viewed your profile

### Social & Community
- **Groups** - Create and manage social groups
- **Chatrooms** - Public and private chat rooms
- **Proximity Chatrooms** - Location-based temporary chat rooms
- **Bulletin Boards** - Community message boards
- **Proximity Artifacts** - Location-based ephemeral content

### Media & Content
- **Photos** - Upload, manage, and organize profile photos
- **Content Generation** - AI-powered profile content and conversation starters

### Safety & Moderation
- **Safety** - Block and report users
- **Moderation** - Content moderation tools (admin)
- **Relationship Tiers** - Progressive photo visibility based on relationship level

### Advanced Features
- **Recommendations** - AI-powered personalized recommendations
- **WebSocket** - Real-time messaging and presence
- **Analytics** - Usage statistics and insights (admin)
- **Rate Limiting** - API rate limit monitoring

### System
- **Health** - API health check endpoint

## Getting Started

### Using Swagger UI

1. Start the Laravel development server:
   ```bash
   php artisan serve
   ```

2. Visit `http://localhost:8000/docs` in your browser

3. Click "Authorize" and enter your JWT bearer token

4. Explore endpoints, test requests, and view responses

### Using Postman

1. Import the collection from `storage/api-docs/fwber-postman-collection.json`:
   - Open Postman
   - Click **Import** → **Upload Files**
   - Select `fwber-postman-collection.json`

2. Set up environment variables:
   - Create a new Postman environment
   - Add variable `base_url` = `http://localhost:8000`
   - Add variable `bearer_token` = `your_jwt_token_here`

3. Requests are organized by tags matching the API structure

### Authentication

Most endpoints require JWT authentication. To get a token:

```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

Response includes `access_token`. Use it in subsequent requests:

```
Authorization: Bearer {access_token}
```

## API Tags Reference

| Tag | Description | Example Endpoints |
|-----|-------------|-------------------|
| Authentication | Login, registration, logout | `/auth/login`, `/auth/register` |
| Profile | Profile management | `/profile`, `/profile/update` |
| Dashboard | User dashboard and stats | `/dashboard` |
| Matches | Match discovery and actions | `/matches`, `/matches/{id}/action` |
| Messages | Direct messaging | `/messages/conversation/{userId}` |
| Physical Profile | Physical attributes | `/profile/physical` |
| Profile Views | View tracking | `/profile/views` |
| Groups | Group management | `/groups`, `/groups/{id}` |
| Chatrooms | Chat room features | `/chatrooms`, `/chatrooms/{id}/messages` |
| Proximity Chatrooms | Location-based chats | `/proximity-chatrooms` |
| Bulletin Boards | Message boards | `/bulletin-boards` |
| Photos | Photo management | `/photos`, `/photos/upload` |
| Proximity Artifacts | Location-based content | `/proximity-artifacts/feed` |
| Relationship Tiers | Progressive photo unlock | `/matches/{id}/tier` |
| Safety | Block/report users | `/blocks`, `/reports` |
| Moderation | Content moderation | `/moderation/content` |
| Recommendations | AI recommendations | `/recommendations` |
| WebSocket | Real-time features | `/websocket/connect` |
| Content Generation | AI content tools | `/content-generation/profile` |
| Rate Limiting | Rate limit monitoring | `/rate-limits/status` |
| Analytics | Platform analytics | `/analytics` |
| Health | Health checks | `/health` |

## Feature Flags

Some advanced features are gated behind feature flags. Check `config/features.php` for current configuration:

- `recommendations` - AI-powered recommendations
- `websocket` - WebSocket real-time features
- `content_generation` - AI content generation
- `rate_limits` - Advanced rate limiting
- `analytics` - Analytics dashboard
- `chatrooms` - Public chatrooms
- `proximity_chatrooms` - Location-based chatrooms

To enable a feature in development:

```env
FEATURE_RECOMMENDATIONS=true
FEATURE_WEBSOCKET=true
```

## Schema Definitions

All reusable schemas are defined in `app/Http/Controllers/Schemas.php`. Key schemas include:

**User Data:**
- `User` - Complete user object
- `Profile` - User profile details
- `Photo` - Photo object with metadata
- `Match` - Match information

**Social:**
- `Chatroom` - Chatroom details
- `ChatMessage` - Chat message structure
- `Group` - Group information
- `DirectMessage` - Direct message object

**Content:**
- `ProximityArtifact` - Location-based content
- `BulletinBoard` - Message board details
- `BulletinMessage` - Board message

**Responses:**
- `SimpleMessageResponse` - Standard success response
- `ValidationError` - Validation error details
- `Unauthorized` - 401 error response
- `Forbidden` - 403 error response
- `NotFound` - 404 error response

## Regenerating Documentation

After updating controller annotations:

```bash
php artisan l5-swagger:generate
```

To regenerate the Postman collection:

```bash
openapi2postmanv2 -s storage/api-docs/api-docs.json -o storage/api-docs/fwber-postman-collection.json -p -O folderStrategy=Tags
```

## Configuration

OpenAPI configuration is in `config/l5-swagger.php`:

- **Title**: FWBer API Documentation
- **Version**: Managed in `app/Http/Controllers/Controller.php`
- **Base URL**: Auto-detected from request
- **Security**: Bearer JWT authentication

## Rate Limits

API endpoints are protected by rate limiting. Default limits:

- Authentication: 5 requests per minute
- General API: 60 requests per minute
- Content Generation: 10 requests per hour
- Photo Upload: 10 requests per hour

Check `/rate-limits/status/{action}` to monitor your usage.

## Real-Time Features

FWBer supports real-time communication via:

1. **WebSocket**: Traditional WebSocket connections (`/websocket/*` endpoints)
2. **Mercure**: Server-sent events for efficient push notifications

See `WebSocketController` documentation for connection details.

## AI Features

FWBer includes AI-powered features:

- **Recommendations** (`/recommendations`): Personalized content and user suggestions
- **Content Generation** (`/content-generation`): Profile bio, conversation starters
- **Moderation** (`/moderation`): AI content moderation via OpenAI + Gemini

## Support

- **Issues**: Report bugs via GitHub issues
- **Documentation**: See `docs/` directory for detailed guides
- **Roadmap**: Check `docs/roadmap/PROJECT_ANALYSIS_AND_ROADMAP_2025-11-15.md`

## Related Documentation

- [Feature Flags](../../docs/FEATURE_FLAGS.md)
- [Project Roadmap](../../docs/roadmap/PROJECT_ANALYSIS_AND_ROADMAP_2025-11-15.md)
- [Security Implementation](../../docs/FWBER_ADVANCED_SECURITY_IMPLEMENTATION_COMPLETE.md)
- [Workflow Guide](../../docs/guides/WORKFLOW_GUIDE.md)

---

**Last Updated**: November 15, 2025
**OpenAPI Version**: 3.0.0
**API Base URL**: `/api/*`
