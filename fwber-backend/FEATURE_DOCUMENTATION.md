# Feature Documentation

## P1 Features: Read Receipts & Presence

### Read Receipts

**Status:** ✅ Completed

**Endpoint:** `POST /api/messages/{messageId}/read`

**Behavior:**
- Only the receiver can mark a message as read
- Idempotent: calling multiple times preserves the original `read_at` timestamp
- Returns structured JSON with full message data

**Response:**
```json
{
  "id": 123,
  "sender_id": 1,
  "receiver_id": 2,
  "is_read": true,
  "read_at": "2025-11-10T14:30:00+00:00"
}
```

**Authorization:**
- Sender attempting to mark message as read → `403 Unauthorized`
- Non-existent message → `404 Not Found`

**Tests:** `tests/Feature/ReadReceiptsTest.php`

---

### Last Seen Presence

**Status:** ✅ Completed

**Mechanism:**
- Middleware `UpdateLastSeen` runs on all authenticated API requests
- Updates `users.last_seen_at` with 60-second debounce
- Uses `saveQuietly()` to avoid triggering events

**Exposed In:**

1. **Match Feed** (`GET /api/matches`)
   - Each match includes `lastSeenAt` field (ISO8601)
   - Used for freshness boost in compatibility scoring

2. **User Resource** (`UserResource`)
   - `lastSeenAt` field added to all user responses

3. **Conversation View** (`GET /api/messages/{userId}`)
   - Response includes `other_user` object with `last_seen_at`

**Example:**
```json
{
  "other_user": {
    "id": 42,
    "name": "Jane Doe",
    "last_seen_at": "2025-11-10T14:28:00+00:00"
  },
  "messages": [...],
  "pagination": {...}
}
```

**Freshness Boost Algorithm:**
- Active within 1 hour: +5 compatibility points
- Active within 24 hours: +3 points
- Active within 1 week: +1 point
- Older/never seen: 0 points

**Tests:** `tests/Feature/LastSeenPresenceTest.php`, `tests/Feature/PresenceExposureTest.php`

---

## P0-3 Feature: Email Notifications

**Status:** ✅ Completed

**Feature Flag:** `FLAG_EMAIL_NOTIFICATIONS` (default: `false`)

### New Match Notifications

**Trigger:** When two users mutually like each other (match created)

**Content:**
- Subject: "It's a Match! 💕"
- Match name, bio, avatar (if available)
- Compatibility score
- CTA button: "View Match & Start Chatting"

**Email sent to:** Both users in the match

**Implementation:** 
- `App\Mail\NewMatchNotification`
- Integrated into `MatchController::checkForMatch()`

---

### Unread Messages Notifications

**Trigger:** Manual or scheduled batch

**Debounce:** 6 hours per user (prevents spam)

**Content:**
- Subject: "You have N unread message(s) 💬"
- Groups messages by sender (max 3 senders shown)
- Shows message preview (60 char limit)
- CTA button: "Reply Now"

**Implementation:**
- `App\Mail\UnreadMessagesNotification`
- `EmailNotificationService::sendUnreadMessagesNotification()`
- Batch mode: `EmailNotificationService::sendBatchUnreadNotifications()`

**Cache Key:** `email_unread_notification_sent:{user_id}`

**Scheduled Task (Recommended):**
```php
// In routes/console.php or App\Console\Kernel
$schedule->call(function () {
    app(\App\Services\EmailNotificationService::class)
        ->sendBatchUnreadNotifications();
})->daily();
```

---

### Configuration

**.env variables:**
```env
FLAG_EMAIL_NOTIFICATIONS=false  # Enable/disable email notifications

MAIL_MAILER=smtp               # or 'log' for local dev
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS="noreply@fwber.app"
MAIL_FROM_NAME="${APP_NAME}"
```

**Templates:**
- `resources/views/emails/new-match.blade.php`
- `resources/views/emails/unread-messages.blade.php`

**Tests:** `tests/Feature/EmailNotificationTest.php`

---

## Implementation Notes

### Authentication Middleware Fix

**Issue:** Multi-user token switching in tests was failing due to "sticky" authentication.

**Root Cause:** `AuthenticateApi` middleware had early bypass:
```php
if (auth()->check()) return $next($request);
```

**Fix:** Check for Authorization header presence:
```php
if (auth()->check() && !str_starts_with($header, 'Bearer ')) {
    return $next($request);
}
```

This ensures token-based auth is processed when `Authorization` header is present.

---

## Test Coverage

**Total Tests:** 64 passed, 10 skipped (AI)

**New Tests:**
- `ReadReceiptsTest` (3 tests, 17 assertions)
- `LastSeenPresenceTest` (1 test, 5 assertions)
- `PresenceExposureTest` (2 tests, 17 assertions)
- `EmailNotificationTest` (5 tests, 14 assertions)

**No Regressions:** All existing tests remain green.

---

## Usage Examples

### Read Receipts
```bash
# Mark message as read
curl -X POST http://localhost:8000/api/messages/123/read \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Check Presence in Match Feed
```bash
curl http://localhost:8000/api/matches \
  -H "Authorization: Bearer YOUR_TOKEN"
```

Response includes `lastSeenAt` for each match.

### Enable Email Notifications
```bash
# In .env
FLAG_EMAIL_NOTIFICATIONS=true

# Restart server
php artisan serve
```

---

## Future Enhancements (Optional)

1. **WebSocket Integration:**
   - Wire `UpdateLastSeen` middleware into WebSocket presence endpoints
   - Real-time presence updates for connected users

2. **Typing Indicators:**
   - Extend presence system with "is typing" events
   - Ephemeral state (not persisted)

3. **Email Preferences:**
   - User settings to control notification frequency
   - Opt-out per notification type

4. **Rich Email Analytics:**
   - Track open rates, click-through rates
   - A/B test email content

---

## Deployment Checklist

- [ ] Set `FLAG_EMAIL_NOTIFICATIONS=true` in production
- [ ] Configure SMTP settings in `.env`
- [ ] Test email delivery in staging
- [ ] Set up scheduled task for batch unread notifications
- [ ] Monitor email queue/logs for issues
- [ ] Consider using Laravel Queue for async email sending

---

**Last Updated:** November 10, 2025
**Test Suite Status:** ✅ All Green (64 passed)
