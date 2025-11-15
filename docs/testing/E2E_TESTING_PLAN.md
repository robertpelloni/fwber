# End-to-End Testing & QA Plan - FWBer API

## Overview

This document outlines comprehensive end-to-end testing flows for the FWBer API to validate functionality, security, and operational readiness before production deployment.

## Testing Environment

- **Base URL**: `http://localhost:8000` (development)
- **API Prefix**: `/api`
- **Documentation**: `/docs`
- **Authentication**: JWT Bearer tokens

## Test User Setup

Create test users for various scenarios:

```json
{
  "test_user_1": {
    "email": "alice@test.local",
    "password": "Test123!@#",
    "role": "user"
  },
  "test_user_2": {
    "email": "bob@test.local",
    "password": "Test123!@#",
    "role": "user"
  },
  "test_admin": {
    "email": "admin@test.local",
    "password": "Admin123!@#",
    "role": "admin"
  }
}
```

---

## Phase 1: Authentication & Authorization

### Test 1.1: User Registration
**Endpoint**: `POST /api/auth/register`

**Test Cases**:
1. ✅ Valid registration with all required fields
2. ✅ Duplicate email rejection (422)
3. ✅ Password validation (minimum length, complexity)
4. ✅ Email format validation
5. ✅ Missing required fields (422)

**Success Criteria**:
- Returns 201 with `access_token` and `user` object
- Token is valid for subsequent requests
- User record created in database

### Test 1.2: User Login
**Endpoint**: `POST /api/auth/login`

**Test Cases**:
1. ✅ Valid credentials return token
2. ✅ Invalid email returns 401
3. ✅ Invalid password returns 401
4. ✅ Token includes proper expiration
5. ✅ Multiple logins work (no session conflicts)

**Success Criteria**:
- Returns 200 with valid JWT
- Token validates on protected endpoints
- User metadata included in response

### Test 1.3: Protected Endpoint Access
**Endpoint**: Any protected endpoint

**Test Cases**:
1. ✅ Valid token returns 200/data
2. ✅ No token returns 401
3. ✅ Invalid token returns 401
4. ✅ Expired token returns 401
5. ✅ Malformed token returns 401

**Success Criteria**:
- Proper 401 responses with error messages
- Valid tokens always accepted
- Token validated on every request

### Test 1.4: Logout
**Endpoint**: `POST /api/auth/logout`

**Test Cases**:
1. ✅ Successful logout invalidates token
2. ✅ Using logged-out token returns 401
3. ✅ Logout without token returns 401

---

## Phase 2: Profile Management

### Test 2.1: View Profile
**Endpoint**: `GET /api/profile`

**Test Cases**:
1. ✅ Returns authenticated user's profile
2. ✅ Includes all expected fields
3. ✅ Respects privacy settings
4. ✅ Returns 401 without auth

**Expected Fields**:
- Basic info: name, age, gender, bio
- Location: latitude, longitude, city
- Preferences: looking_for, age_range
- Timestamps: created_at, updated_at

### Test 2.2: Update Profile
**Endpoint**: `PUT /api/profile`

**Test Cases**:
1. ✅ Update bio successfully
2. ✅ Update preferences successfully
3. ✅ Validation errors for invalid data (422)
4. ✅ Cannot update protected fields (id, email)
5. ✅ Returns updated profile

**Success Criteria**:
- Changes persist in database
- Validation rules enforced
- Protected fields cannot be modified

### Test 2.3: Physical Profile
**Endpoint**: `GET/PUT /api/profile/physical`

**Test Cases**:
1. ✅ Get physical profile attributes
2. ✅ Update height, build, ethnicity
3. ✅ Validation for valid values
4. ✅ Optional fields handled correctly

### Test 2.4: Location Updates
**Endpoint**: `POST /api/location/update`

**Test Cases**:
1. ✅ Update location with valid coordinates
2. ✅ Latitude validation (-90 to 90)
3. ✅ Longitude validation (-180 to 180)
4. ✅ Location updates reflected in matches
5. ✅ Rate limiting applied (if configured)

---

## Phase 3: Photo Management

### Test 3.1: Photo Upload
**Endpoint**: `POST /api/photos/upload`

**Test Cases**:
1. ✅ Upload valid image (JPEG, PNG)
2. ✅ File size validation (max 10MB)
3. ✅ Invalid file type rejected (422)
4. ✅ Multiple photos uploaded
5. ✅ Thumbnail generation confirmed
6. ✅ Storage path correct
7. ✅ Photo metadata stored (dimensions, size)

**Success Criteria**:
- Original image stored in `storage/app/public/photos/`
- Thumbnail generated in `storage/app/public/photos/thumbnails/`
- Database record created with correct paths
- Response includes photo object with URLs

### Test 3.2: Photo List & Retrieval
**Endpoint**: `GET /api/photos`

**Test Cases**:
1. ✅ List all user photos
2. ✅ Photos ordered by sort_order
3. ✅ Privacy flags respected
4. ✅ URLs accessible and valid

### Test 3.3: Photo Reordering
**Endpoint**: `POST /api/photos/reorder`

**Test Cases**:
1. ✅ Reorder photos successfully
2. ✅ New order persists
3. ✅ Validation for photo ownership

### Test 3.4: Photo Deletion
**Endpoint**: `DELETE /api/photos/{id}`

**Test Cases**:
1. ✅ Delete own photo successfully
2. ✅ Cannot delete others' photos (403)
3. ✅ File removed from storage
4. ✅ Database record removed
5. ✅ Thumbnail removed

---

## Phase 4: Matching System

### Test 4.1: Match Discovery
**Endpoint**: `GET /api/matches`

**Test Cases**:
1. ✅ Returns potential matches
2. ✅ Location-based filtering works
3. ✅ Age range filtering applied
4. ✅ Gender preference filtering applied
5. ✅ Pagination works correctly
6. ✅ Excludes blocked users
7. ✅ Excludes already matched users

### Test 4.2: Match Actions
**Endpoint**: `POST /api/matches/{matchId}/action`

**Test Cases**:
1. ✅ Like action creates match (if mutual)
2. ✅ Pass action removes from feed
3. ✅ Cannot act on same user twice
4. ✅ Invalid match ID returns 404
5. ✅ Mutual likes create match record

### Test 4.3: Match Retrieval
**Endpoint**: `GET /api/matches/list`

**Test Cases**:
1. ✅ List all active matches
2. ✅ Matches sorted by created date
3. ✅ Include match metadata (score, distance)
4. ✅ Filter by match status

### Test 4.4: Match Details
**Endpoint**: `GET /api/matches/{matchId}`

**Test Cases**:
1. ✅ Get detailed match info
2. ✅ Include mutual interests
3. ✅ Show match score breakdown
4. ✅ Privacy respected (tier system)

---

## Phase 5: Messaging

### Test 5.1: Send Direct Message
**Endpoint**: `POST /api/messages/send`

**Test Cases**:
1. ✅ Send message to matched user
2. ✅ Cannot send to non-matched user (403)
3. ✅ Cannot send to blocked user (403)
4. ✅ Message content validated (max length)
5. ✅ Message stored with timestamp

### Test 5.2: Conversation Thread
**Endpoint**: `GET /api/messages/conversation/{userId}`

**Test Cases**:
1. ✅ Retrieve all messages with user
2. ✅ Messages ordered by timestamp
3. ✅ Pagination works correctly
4. ✅ Includes read status
5. ✅ Cannot view others' conversations (403)

### Test 5.3: Mark as Read
**Endpoint**: `POST /api/messages/{messageId}/read`

**Test Cases**:
1. ✅ Mark message as read
2. ✅ Read timestamp updated
3. ✅ Unread count decremented

### Test 5.4: Unread Count
**Endpoint**: `GET /api/messages/unread`

**Test Cases**:
1. ✅ Returns correct unread count
2. ✅ Updates after marking as read
3. ✅ Fast response time

---

## Phase 6: Groups & Chatrooms

### Test 6.1: Group Creation
**Endpoint**: `POST /api/groups`

**Test Cases**:
1. ✅ Create group with valid data
2. ✅ Creator becomes admin
3. ✅ Group settings applied
4. ✅ Validation for name, description

### Test 6.2: Group Messages
**Endpoint**: `POST /api/groups/{groupId}/messages`

**Test Cases**:
1. ✅ Send message to group
2. ✅ Only members can send (403)
3. ✅ Message broadcast to all members
4. ✅ Unread tracking per member

### Test 6.3: Chatroom CRUD
**Endpoints**: `/api/chatrooms/*`

**Test Cases**:
1. ✅ List public chatrooms
2. ✅ Create chatroom
3. ✅ Join chatroom
4. ✅ Send message to chatroom
5. ✅ Leave chatroom
6. ✅ Retrieve chatroom messages

### Test 6.4: Proximity Chatrooms
**Endpoint**: `GET /api/proximity-chatrooms`

**Test Cases**:
1. ✅ Discover nearby chatrooms
2. ✅ Location-based filtering
3. ✅ Radius parameter respected
4. ✅ Expired chatrooms excluded

---

## Phase 7: Proximity Artifacts

### Test 7.1: Artifact Feed
**Endpoint**: `GET /api/proximity-artifacts/feed`

**Test Cases**:
1. ✅ Returns artifacts near location
2. ✅ Radius filtering works
3. ✅ Expired artifacts excluded
4. ✅ Privacy/visibility rules applied
5. ✅ Pagination works

### Test 7.2: Create Artifact
**Endpoint**: `POST /api/proximity-artifacts`

**Test Cases**:
1. ✅ Create artifact with location
2. ✅ Expiration time set correctly
3. ✅ Visibility options work
4. ✅ Content validation applied

### Test 7.3: Flag Artifact
**Endpoint**: `POST /api/proximity-artifacts/{id}/flag`

**Test Cases**:
1. ✅ Flag inappropriate content
2. ✅ Flag reasons validated
3. ✅ Flagged artifact reviewed
4. ✅ Cannot flag own artifacts

---

## Phase 8: Safety Features

### Test 8.1: Block User
**Endpoint**: `POST /api/blocks`

**Test Cases**:
1. ✅ Block user successfully
2. ✅ Blocked user removed from matches
3. ✅ Cannot message blocked user
4. ✅ Blocked user cannot see profile
5. ✅ Cannot block self

### Test 8.2: Unblock User
**Endpoint**: `DELETE /api/blocks/{blockedId}`

**Test Cases**:
1. ✅ Unblock user successfully
2. ✅ User reappears in match pool
3. ✅ Can message again

### Test 8.3: Report User
**Endpoint**: `POST /api/reports`

**Test Cases**:
1. ✅ Submit report with reason
2. ✅ Report categories validated
3. ✅ Evidence/description required
4. ✅ Report submitted for review
5. ✅ Cannot report multiple times quickly

---

## Phase 9: Rate Limiting

### Test 9.1: Rate Limit Enforcement
**Various Endpoints**

**Test Cases**:
1. ✅ Exceed rate limit returns 429
2. ✅ Rate limit headers present
3. ✅ Different limits per action type
4. ✅ Limits reset after window
5. ✅ Admin bypasses rate limits (optional)

### Test 9.2: Rate Limit Status
**Endpoint**: `GET /api/rate-limits/status/{action}`

**Test Cases**:
1. ✅ Returns current limit status
2. ✅ Shows remaining requests
3. ✅ Shows reset time
4. ✅ Accurate across actions

---

## Phase 10: Advanced Features

### Test 10.1: Recommendations
**Endpoint**: `GET /api/recommendations`

**Test Cases**:
1. ✅ Returns personalized recommendations
2. ✅ Location context applied
3. ✅ Type filtering works
4. ✅ Limit parameter respected

### Test 10.2: WebSocket Connection
**Endpoint**: `POST /api/websocket/connect`

**Test Cases**:
1. ✅ Establish connection
2. ✅ Connection data stored
3. ✅ Receive connection ID
4. ✅ Disconnect works

### Test 10.3: Content Generation
**Endpoint**: `POST /api/content-generation/profile`

**Test Cases**:
1. ✅ Generate profile content
2. ✅ Style parameter applied
3. ✅ Content quality acceptable
4. ✅ Rate limiting applied

---

## Validation Checklists

### Input Validation
- [ ] Required fields enforced
- [ ] Data type validation
- [ ] Length/size limits
- [ ] Format validation (email, phone, etc.)
- [ ] Range validation (dates, coordinates)
- [ ] Enum value validation
- [ ] Array/nested object validation

### Security Validation
- [ ] Authentication required where appropriate
- [ ] Authorization checks (can't access others' data)
- [ ] CSRF protection (if applicable)
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] Rate limiting enforcement
- [ ] Input sanitization

### Error Handling
- [ ] 401 for unauthenticated requests
- [ ] 403 for unauthorized access
- [ ] 404 for not found resources
- [ ] 422 for validation errors
- [ ] 429 for rate limit exceeded
- [ ] 500 for server errors (with logging)
- [ ] Consistent error response format

### Data Integrity
- [ ] Database transactions used
- [ ] Foreign key constraints enforced
- [ ] Unique constraints respected
- [ ] Cascade deletes configured
- [ ] Timestamps updated correctly
- [ ] Soft deletes work (if implemented)

### Performance
- [ ] Queries optimized (no N+1)
- [ ] Indexes on frequently queried fields
- [ ] Pagination implemented
- [ ] Response times acceptable (<500ms for most endpoints)
- [ ] File uploads handle large sizes
- [ ] Caching implemented where appropriate

---

## Environment Configuration Checklist

### Required Environment Variables
```env
# App Configuration
APP_NAME=FWBer
APP_ENV=production
APP_KEY=base64:...  # Generate with php artisan key:generate
APP_DEBUG=false
APP_URL=https://api.fwber.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fwber_production
DB_USERNAME=fwber_user
DB_PASSWORD=<secure_password>

# JWT Authentication
JWT_SECRET=<secure_jwt_secret>
JWT_TTL=60  # Token lifetime in minutes

# Storage
FILESYSTEM_DISK=public
AWS_ACCESS_KEY_ID=  # If using S3
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=
AWS_BUCKET=

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@fwber.com
MAIL_FROM_NAME="${APP_NAME}"

# Redis (for caching, queues, rate limiting)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mercure (real-time)
MERCURE_URL=https://mercure.fwber.com/.well-known/mercure
MERCURE_PUBLIC_URL=https://mercure.fwber.com/.well-known/mercure
MERCURE_JWT_SECRET=<mercure_jwt_secret>

# AI Services
OPENAI_API_KEY=<openai_key>
GEMINI_API_KEY=<gemini_key>

# Feature Flags
FEATURE_RECOMMENDATIONS=true
FEATURE_WEBSOCKET=true
FEATURE_CONTENT_GENERATION=true
FEATURE_RATE_LIMITS=true
FEATURE_ANALYTICS=true
FEATURE_CHATROOMS=true
FEATURE_PROXIMITY_CHATROOMS=true

# Monitoring
SENTRY_LARAVEL_DSN=  # Error tracking
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### Security Checklist
- [ ] APP_DEBUG=false in production
- [ ] Strong APP_KEY generated
- [ ] Database credentials secured
- [ ] JWT_SECRET is cryptographically strong
- [ ] API keys stored in environment, not code
- [ ] HTTPS enforced
- [ ] CORS configured correctly
- [ ] Rate limiting enabled
- [ ] File upload directory permissions correct (755)

---

## Monitoring & Alerting Plan

### Metrics to Monitor

#### Application Metrics
- API response times (p50, p95, p99)
- Error rates by endpoint
- Request volume
- Active user count
- Database query performance
- Cache hit rates
- Queue depth and processing time

#### Infrastructure Metrics
- CPU utilization
- Memory usage
- Disk I/O
- Network traffic
- Database connections
- Redis memory usage

#### Business Metrics
- User registrations per day
- Active users (DAU, MAU)
- Matches created
- Messages sent
- Photo uploads
- Feature adoption rates

### Logging Strategy

#### Application Logs
```php
// Error logging
Log::error('Operation failed', [
    'user_id' => $user->id,
    'error' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);

// Info logging
Log::info('Match created', [
    'user_1' => $userId1,
    'user_2' => $userId2,
    'match_score' => $score
]);
```

#### Log Levels
- **Emergency**: System is unusable
- **Alert**: Action must be taken immediately
- **Critical**: Critical conditions
- **Error**: Runtime errors
- **Warning**: Exceptional occurrences
- **Notice**: Normal but significant events
- **Info**: Interesting events
- **Debug**: Detailed debug information

### Alert Thresholds

#### Critical Alerts (Page immediately)
- API error rate > 5%
- Database connection failures
- Disk space < 10%
- Memory usage > 90%
- Application crashes

#### Warning Alerts (Review within hours)
- API response time p95 > 1s
- Error rate > 1%
- Queue backlog > 1000 jobs
- Memory usage > 80%
- Unusual traffic patterns

#### Info Alerts (Review daily)
- New user registrations spike
- Feature usage anomalies
- Performance degradation
- Cron job failures

### Monitoring Tools

Recommended setup:
- **Application Monitoring**: Sentry, New Relic, or DataDog
- **Log Aggregation**: ELK Stack (Elasticsearch, Logstash, Kibana)
- **Uptime Monitoring**: Pingdom, UptimeRobot
- **Infrastructure**: Prometheus + Grafana
- **Error Tracking**: Sentry
- **APM**: New Relic or DataDog

---

## Test Execution Tracking

Use this table to track test execution progress:

| Phase | Test | Status | Date Tested | Tester | Notes |
|-------|------|--------|-------------|--------|-------|
| 1 | Auth - Registration | ⬜ | | | |
| 1 | Auth - Login | ⬜ | | | |
| 1 | Auth - Protected Access | ⬜ | | | |
| 2 | Profile - View | ⬜ | | | |
| 2 | Profile - Update | ⬜ | | | |
| 2 | Profile - Physical | ⬜ | | | |
| 3 | Photos - Upload | ⬜ | | | |
| 3 | Photos - List | ⬜ | | | |
| 3 | Photos - Delete | ⬜ | | | |
| 4 | Matches - Discovery | ⬜ | | | |
| 4 | Matches - Actions | ⬜ | | | |
| 5 | Messages - Send | ⬜ | | | |
| 5 | Messages - Thread | ⬜ | | | |
| 6 | Groups - Create | ⬜ | | | |
| 6 | Chatrooms - CRUD | ⬜ | | | |
| 7 | Proximity - Feed | ⬜ | | | |
| 8 | Safety - Block | ⬜ | | | |
| 8 | Safety - Report | ⬜ | | | |
| 9 | Rate Limits | ⬜ | | | |
| 10 | Recommendations | ⬜ | | | |

Legend: ⬜ Not Started | 🔄 In Progress | ✅ Passed | ❌ Failed

---

## Next Steps After Testing

1. **Fix Issues**: Address all failed tests and critical bugs
2. **Performance Optimization**: Optimize slow endpoints
3. **Security Audit**: Conduct penetration testing
4. **Load Testing**: Verify performance under load
5. **Documentation Updates**: Update docs based on findings
6. **Deployment Preparation**: Finalize deployment scripts
7. **Monitoring Setup**: Configure production monitoring
8. **Backup Strategy**: Implement backup and recovery procedures

---

**Created**: November 15, 2025
**Last Updated**: November 15, 2025
**Status**: Ready for Execution
