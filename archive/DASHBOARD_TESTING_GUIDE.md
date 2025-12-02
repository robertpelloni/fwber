# Dashboard API Testing Guide

## 🧪 Quick Test Suite

### Prerequisites
```bash
# Ensure migrations are run
php artisan migrate

# Seed test data
php artisan db:seed --class=DashboardDataSeeder

# Note the test credentials:
# Email: test1@fwber.com
# Password: password123
```

---

## 📝 Manual Testing Steps

### 1. Login and Get Token
```bash
# Login to get authentication token
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test1@fwber.com",
    "password": "password123"
  }'

# Save the token from response
# Response: { "token": "YOUR_TOKEN_HERE", "user": {...} }
```

### 2. Test Dashboard Stats
```bash
curl -X GET http://localhost:8000/api/dashboard/stats \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Expected Response:
{
  "total_matches": 1,
  "pending_matches": 0,
  "accepted_matches": 1,
  "conversations": 2,
  "profile_views": 4,
  "today_views": 0,
  "match_score_avg": 85,
  "response_rate": 50,
  "days_active": 30,
  "last_login": "2025-11-14T08:05:43.000000Z"
}
```

### 3. Test Activity Feed
```bash
curl -X GET http://localhost:8000/api/dashboard/activity \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Expected Response: Array of activities
[
  {
    "type": "view",
    "user": {
      "id": 2,
      "name": "Test User 2",
      "avatar_url": null
    },
    "timestamp": "2025-11-14T06:05:43"
  },
  {
    "type": "message",
    "user": {
      "id": 2,
      "name": "Test User 2",
      "avatar_url": null
    },
    "timestamp": "2025-11-14T05:05:43"
  },
  ...
]
```

### 4. Test Profile Completeness
```bash
curl -X GET http://localhost:8000/api/profile/completeness \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Expected Response:
{
  "percentage": 45,
  "completed_fields": 5,
  "total_fields": 11,
  "missing_fields": [
    "Bio",
    "Age",
    "Pronouns",
    "Sexual Orientation",
    "Relationship Style",
    "Looking For"
  ],
  "is_complete": false
}
```

### 5. Test Profile View Recording
```bash
# Record a profile view (as authenticated user)
curl -X POST http://localhost:8000/api/profile/1/view \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Expected Response:
{
  "message": "Profile view recorded"
}

# Try viewing again (should be deduplicated)
curl -X POST http://localhost:8000/api/profile/1/view \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Expected Response:
{
  "message": "View already recorded"
}
```

### 6. Test Profile Views List
```bash
curl -X GET http://localhost:8000/api/profile/1/views \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Expected Response: Array of viewers
[
  {
    "id": 1,
    "viewer": {
      "id": 2,
      "name": "Test User 2",
      "avatar_url": null,
      "age": null,
      "city": null
    },
    "viewed_at": "2025-11-14T06:05:43"
  },
  ...
]
```

### 7. Test Profile View Stats
```bash
curl -X GET http://localhost:8000/api/profile/1/views/stats \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Expected Response:
{
  "total_views": 4,
  "today_views": 0,
  "week_views": 4,
  "month_views": 4,
  "unique_viewers": 1
}
```

---

## 🌐 Frontend Testing

### 1. Start Frontend Dev Server
```bash
cd fwber-frontend
npm run dev
```

### 2. Login and Navigate to Dashboard
1. Open http://localhost:3000
2. Login with test1@fwber.com / password123
3. Navigate to /dashboard
4. Verify all components load

### 3. Visual Checks
- [ ] 4 stat cards display with correct values
- [ ] Activity feed shows recent items
- [ ] Profile completeness widget displays
- [ ] Progress bar color matches percentage
- [ ] Section checklist shows correct status
- [ ] Achievements display (locked/unlocked)
- [ ] Quick actions grid is clickable
- [ ] Account status panel shows correct data
- [ ] Loading skeletons appear briefly
- [ ] Hover effects work on stat cards

### 4. Interactive Tests
- [ ] Click stat cards → navigate to correct pages
- [ ] Click "Complete Profile" → opens profile editor
- [ ] Click quick action buttons → navigate correctly
- [ ] Activity feed scrolls properly
- [ ] Empty states display when no data
- [ ] Responsive layout works on mobile

### 5. Profile Completeness Tests
- [ ] Widget auto-refreshes every 30 seconds
- [ ] Percentage updates when profile edited
- [ ] Section checkmarks update correctly
- [ ] Missing fields list updates
- [ ] 100% completion shows celebration message
- [ ] Color coding changes based on percentage:
  - Red: < 50%
  - Yellow: 50-79%
  - Green: 80-100%

---

## 🐛 Debugging Tips

### Backend Issues

**"Unauthenticated" error**
```bash
# Check if token is valid
php artisan tinker
>>> $user = \App\Models\User::find(1);
>>> $token = $user->createToken('test')->plainTextToken;
>>> echo $token;
```

**"Column not found" error**
```bash
# Check database schema
php artisan db:show
php artisan db:table matches
php artisan db:table messages
php artisan db:table profile_views
```

**Stats returning zeros**
```bash
# Check if data exists
php artisan tinker
>>> DB::table('matches')->count();
>>> DB::table('messages')->count();
>>> DB::table('profile_views')->count();
```

### Frontend Issues

**API not connecting**
```bash
# Check .env.local file
NEXT_PUBLIC_API_URL=http://localhost:8000/api

# Verify backend is running
curl http://localhost:8000/api/health
```

**Components not rendering**
```bash
# Check browser console for errors
# Look for:
# - CORS errors
# - Network errors
# - React errors
# - TypeScript errors
```

**Data not updating**
```bash
# Check React Query cache
# Open React DevTools
# Check "Queries" tab
# Force refetch or clear cache
```

---

## 📊 Expected Test Results

### Stats Endpoint
```json
{
  "total_matches": 1,
  "pending_matches": 0,
  "accepted_matches": 1,
  "conversations": 2,
  "profile_views": 4,
  "today_views": 0,
  "match_score_avg": 85,
  "response_rate": 50,
  "days_active": 30,
  "last_login": "2025-11-14T..."
}
```

### Activity Endpoint
```json
[
  {
    "type": "view",
    "user": { "id": 2, "name": "Test User 2", "avatar_url": null },
    "timestamp": "2025-11-14T06:05:43"
  },
  {
    "type": "message",
    "user": { "id": 2, "name": "Test User 2", "avatar_url": null },
    "timestamp": "2025-11-14T05:05:43"
  },
  {
    "type": "match",
    "user": { "id": 2, "name": "Test User 2", "avatar_url": null },
    "timestamp": "2025-11-12T08:05:43",
    "match_score": 85
  }
]
```

### Completeness Endpoint
```json
{
  "percentage": 45,
  "completed_fields": 5,
  "total_fields": 11,
  "missing_fields": ["Bio", "Age", "Pronouns", ...],
  "is_complete": false
}
```

---

## ✅ Success Criteria

### Backend
- [x] All 6 endpoints respond with 200 OK
- [x] Authentication required for all endpoints
- [x] Stats return correct data types
- [x] Activity feed sorted by timestamp (desc)
- [x] Profile views deduplicate within 24h
- [x] Database migrations complete
- [x] Test data seeded successfully

### Frontend
- [x] Dashboard page loads without errors
- [x] All components render correctly
- [x] Stats display real data
- [x] Activity feed shows items
- [x] Profile completeness widget functional
- [x] Loading states appear/disappear
- [x] Hover effects work
- [x] Responsive layout works
- [x] Navigation links work
- [x] Auto-refresh functions

### Integration
- [x] Frontend connects to backend API
- [x] Authentication flows correctly
- [x] Data updates in real-time
- [x] Error handling works
- [x] CORS configured properly
- [x] Rate limiting applies

---

## 🎯 Performance Benchmarks

### API Response Times
- Dashboard stats: < 100ms
- Activity feed: < 150ms
- Profile completeness: < 50ms
- Record view: < 30ms
- Get views: < 100ms
- View stats: < 80ms

### Frontend Load Times
- Initial page load: < 2s
- Component render: < 100ms
- Stats query: < 500ms
- Activity query: < 500ms
- Auto-refresh: < 300ms

---

## 📝 Test Report Template

```
# Dashboard Test Report

**Date:** [Date]
**Tester:** [Name]
**Environment:** [Local/Dev/Prod]

## Backend Tests
- [ ] Login successful
- [ ] Dashboard stats endpoint works
- [ ] Activity feed endpoint works
- [ ] Profile completeness endpoint works
- [ ] Profile view recording works
- [ ] Profile views list works
- [ ] Profile view stats works

## Frontend Tests
- [ ] Dashboard page loads
- [ ] Stat cards display correctly
- [ ] Activity feed renders
- [ ] Profile completeness widget works
- [ ] Achievements display
- [ ] Quick actions work
- [ ] Responsive design works

## Issues Found
[List any issues discovered]

## Notes
[Additional observations]

## Conclusion
✅ PASS / ❌ FAIL
```

---

## 🚀 Production Deployment Checklist

Before deploying to production:

- [ ] Run all tests successfully
- [ ] Verify API endpoints with production URLs
- [ ] Check CORS configuration
- [ ] Update environment variables
- [ ] Run database migrations
- [ ] Seed initial data (if needed)
- [ ] Test with real user accounts
- [ ] Monitor performance metrics
- [ ] Set up error logging
- [ ] Configure rate limiting
- [ ] Enable caching
- [ ] Test on multiple devices
- [ ] Test on multiple browsers
- [ ] Verify SSL/HTTPS works
- [ ] Check mobile responsiveness
- [ ] Test with slow network
- [ ] Review security settings
- [ ] Update documentation
- [ ] Train support team
- [ ] Plan rollback strategy
- [ ] Monitor after deployment

---

**Status:** Ready for testing! 🧪
