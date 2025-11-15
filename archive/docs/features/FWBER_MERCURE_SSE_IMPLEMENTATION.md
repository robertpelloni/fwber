# FWBer.me - Mercure SSE Broker Implementation

## Overview
This document details the implementation of the Mercure SSE (Server-Sent Events) broker for the FWBer.me location-based bulletin board system. The implementation provides scalable real-time messaging capabilities using the Mercure protocol.

## Architecture

### Components
1. **Mercure Hub** - Docker container running the Mercure server
2. **Laravel Backend** - Publishes messages to Mercure and provides JWT authentication
3. **Next.js Frontend** - Subscribes to Mercure topics via EventSource API
4. **JWT Authentication** - Secure topic-based authorization

### Data Flow
```
User posts message → Laravel API → Mercure Publisher → Mercure Hub → SSE → Frontend
```

## Implementation Details

### 1. Mercure Hub Configuration

**File**: `fwber-backend/docker-compose.mercure.yml`

```yaml
version: '3.8'

services:
  mercure:
    image: dunglas/mercure:latest
    restart: unless-stopped
    environment:
      SERVER_NAME: ":80"
      MERCURE_PUBLISHER_JWT_KEY: "${MERCURE_PUBLISHER_JWT_KEY}"
      MERCURE_SUBSCRIBER_JWT_KEY: "${MERCURE_SUBSCRIBER_JWT_KEY}"
      MERCURE_CORS_ALLOWED_ORIGINS: "${APP_URL}"
      MERCURE_PUBLISH_ALLOWED_ORIGINS: "${API_URL}"
      MERCURE_SUBSCRIPTIONS: "1"
      MERCURE_EXTRA_DIRECTIVES: |
        subscriptions
        heartbeat 10s
        write_timeout 30s
        read_timeout 30s
    ports:
      - "3001:80"
    networks:
      - fwber_network
```

### 2. Laravel Backend Integration

#### MercurePublisher Service
**File**: `fwber-backend/app/Services/MercurePublisher.php`

- Handles JWT generation for publishers and subscribers
- Publishes messages to Mercure hub
- Includes error handling and logging
- Supports batch publishing

#### MercureAuthController
**File**: `fwber-backend/app/Http/Controllers/MercureAuthController.php`

- Sets authorization cookies for client-side SSE connections
- Generates subscriber JWTs with topic-based permissions
- Provides status endpoint for connection monitoring

#### BulletinBoardController Updates
**File**: `fwber-backend/app/Http/Controllers/BulletinBoardController.php`

- Updated `publishMessage()` method to use Mercure instead of Redis
- Publishes to both private board topics and public discovery topics
- Includes comprehensive error handling

### 3. Next.js Frontend Integration

#### Mercure SSE Hook
**File**: `fwber-frontend/lib/hooks/use-mercure-sse.ts`

- Generic hook for Mercure SSE connections
- Handles authentication cookie retrieval
- Automatic reconnection with configurable intervals
- Specialized `useBulletinBoardMercure` hook for bulletin boards

#### Bulletin Board Page Updates
**File**: `fwber-frontend/app/bulletin-boards/page.tsx`

- Integrated Mercure SSE for real-time message updates
- Added connection status indicator
- Real-time message display without page refresh

### 4. Topic Structure

#### Private Topics
- `https://fwber.me/bulletin-boards/{boardId}` - Board-specific messages
- `https://fwber.me/users/{userId}/*` - User-specific notifications

#### Public Topics
- `https://fwber.me/public/bulletin-boards` - Board activity updates
- `https://fwber.me/public/*` - General public updates

### 5. JWT Configuration

#### Publisher JWT
```php
$payload = [
    'mercure' => ['publish' => ['*']],
    'exp' => time() + 60,
    'iat' => time()
];
```

#### Subscriber JWT
```php
$payload = [
    'mercure' => ['subscribe' => $topics],
    'sub' => (string) $userId,
    'exp' => time() + ($expiryMinutes * 60),
    'iat' => time()
];
```

## Environment Variables

### Required Environment Variables
```bash
# Mercure Hub Configuration
MERCURE_PUBLISHER_JWT_KEY=your-publisher-secret-key
MERCURE_SUBSCRIBER_JWT_KEY=your-subscriber-secret-key
MERCURE_PUBLIC_URL=http://localhost:3001
MERCURE_INTERNAL_URL=http://mercure:80
MERCURE_COOKIE_DOMAIN=localhost

# Application URLs
APP_URL=http://localhost:3000
API_URL=http://localhost:8000
```

## Deployment Instructions

### 1. Generate JWT Keys
```bash
# Generate secure random keys
openssl rand -base64 32  # For MERCURE_PUBLISHER_JWT_KEY
openssl rand -base64 32  # For MERCURE_SUBSCRIBER_JWT_KEY
```

### 2. Update Environment Variables
Add the Mercure configuration to your `.env` files:
- `fwber-backend/.env`
- `fwber-frontend/.env.local`

### 3. Install Dependencies
```bash
# Laravel backend
cd fwber-backend
composer require firebase/php-jwt

# Next.js frontend (already included)
cd fwber-frontend
npm install
```

### 4. Start Mercure Hub
```bash
cd fwber-backend
docker-compose -f docker-compose.mercure.yml up -d
```

### 5. Start Application Services
```bash
# Laravel backend
cd fwber-backend
php artisan serve --host=127.0.0.1 --port=8000

# Next.js frontend
cd fwber-frontend
npm run dev
```

## Testing the Implementation

### 1. Verify Mercure Hub
```bash
curl http://localhost:3001/.well-known/mercure
```

### 2. Test Authentication
```bash
# Get authorization cookie
curl -X GET http://localhost:8000/api/mercure/cookie \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -c cookies.txt
```

### 3. Test Message Publishing
```bash
# Post a message to a bulletin board
curl -X POST http://localhost:8000/api/bulletin-boards/1/messages \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "content": "Test message",
    "lat": 40.7128,
    "lng": -74.0060
  }'
```

### 4. Test SSE Subscription
```javascript
// In browser console
const eventSource = new EventSource('http://localhost:3001/.well-known/mercure?topic=https://fwber.me/bulletin-boards/1');
eventSource.onmessage = (event) => console.log('Message:', JSON.parse(event.data));
```

## Performance Considerations

### 1. Connection Limits
- Mercure hub can handle thousands of concurrent connections
- Each browser tab creates a separate EventSource connection
- Consider connection pooling for high-traffic scenarios

### 2. Message Batching
- Implement message batching for high-frequency updates
- Use the `publishBatch()` method for multiple messages

### 3. Topic Optimization
- Use specific topics to reduce unnecessary message delivery
- Implement topic filtering based on user location and preferences

### 4. Error Handling
- Implement exponential backoff for reconnection
- Graceful degradation when Mercure is unavailable
- Fallback to polling when SSE fails

## Security Considerations

### 1. JWT Security
- Use strong, unique keys for publisher and subscriber JWTs
- Implement proper token expiration
- Rotate keys regularly

### 2. Topic Authorization
- Implement fine-grained topic permissions
- Validate user access to specific topics
- Use private topics for sensitive data

### 3. CORS Configuration
- Configure CORS origins properly
- Restrict publisher origins to trusted sources
- Use HTTPS in production

## Monitoring and Debugging

### 1. Logging
- Laravel logs Mercure publish attempts and errors
- Monitor connection status in frontend
- Track message delivery success rates

### 2. Health Checks
- Implement Mercure hub health check endpoint
- Monitor connection counts and message throughput
- Set up alerts for connection failures

### 3. Debug Tools
- Use browser DevTools to monitor EventSource connections
- Check Mercure hub logs for connection issues
- Verify JWT token validity and permissions

## Future Enhancements

### 1. WebSocket Upgrade
- Consider upgrading to WebSockets for bidirectional communication
- Implement WebSocket fallback for SSE failures
- Add real-time typing indicators and presence

### 2. Message Persistence
- Implement message history in Mercure
- Add message replay capabilities
- Store message metadata for analytics

### 3. Advanced Features
- Implement message reactions and threading
- Add file sharing capabilities
- Implement message encryption for sensitive content

## Troubleshooting

### Common Issues

1. **Connection Refused**
   - Check if Mercure hub is running
   - Verify port configuration
   - Check Docker network connectivity

2. **Authentication Failures**
   - Verify JWT keys match between Laravel and Mercure
   - Check cookie domain configuration
   - Ensure proper CORS headers

3. **Messages Not Received**
   - Verify topic names match exactly
   - Check subscriber JWT permissions
   - Monitor browser console for errors

4. **High Memory Usage**
   - Implement connection cleanup
   - Use message expiration
   - Monitor Mercure hub resource usage

### Debug Commands
```bash
# Check Mercure hub status
docker logs fwber-backend_mercure_1

# Test JWT token
php artisan tinker
>>> JWT::decode($token, new Key(config('services.mercure.subscriber_key'), 'HS256'));

# Monitor network connections
netstat -an | grep 3001
```

## Conclusion

The Mercure SSE implementation provides a robust, scalable solution for real-time messaging in the FWBer.me bulletin board system. The architecture supports thousands of concurrent users while maintaining low latency and high reliability. The implementation includes comprehensive error handling, security measures, and monitoring capabilities to ensure production readiness.

The modular design allows for easy extension and enhancement as the platform grows, with clear separation of concerns between the Mercure hub, Laravel backend, and Next.js frontend components.
