# Matching System Testing Guide

## Overview
Tests for match discovery feed, like/pass/super_like actions, mutual matches, and filters.

## Endpoints to Test

### 1. Get Matches Feed
**GET** `/api/matches`

**Query Parameters**:
- `age_min` (optional): integer, 18-100
- `age_max` (optional): integer, 18-100
- `max_distance` (optional): integer, 1-500 miles (default: 50)

**Test Cases**:

```json
// No Filters (200)
GET /api/matches

Response:
{
  "matches": [
    {
      "id": 42,
      "name": "Jane Smith",
      "age": 28,
      "bio": "Love hiking and coffee",
      "distance": 5.3,
      "match_score": 85,
      "avatar_url": "https://..."
    },
    {
      "id": 43,
      "name": "Alex Johnson",
      "age": 32,
      "bio": "Software engineer",
      "distance": 12.7,
      "match_score": 78,
      "avatar_url": "https://..."
    }
  ],
  "total": 2
}

// With Age Filter
GET /api/matches?age_min=25&age_max=35

// With Distance Filter
GET /api/matches?max_distance=10

// Profile Not Complete (400)
{
  "message": "Profile not found. Please complete your profile first."
}

// Unauthenticated (401)
{
  "message": "Unauthenticated"
}
```

**Notes**:
- Results cached for 60 seconds per user + filter combination
- Excludes: current user, blocked users, already matched users
- Ordered by match score (higher = better match)
- Location-based filtering if user has coordinates
- Age filter based on date_of_birth
- Distance calculated from user's location

---

### 2. Match Action (Like/Pass/Super Like)
**POST** `/api/matches/action`

**Test Cases**:

```json
// Like Action
{
  "action": "like",
  "target_user_id": 42
}

Response (200) - No Mutual Match:
{
  "action": "like",
  "is_match": false,
  "message": "Action recorded"
}

Response (200) - Mutual Match:
{
  "action": "like",
  "is_match": true,
  "message": "It's a match!"
}

// Pass Action
{
  "action": "pass",
  "target_user_id": 43
}

Response (200):
{
  "action": "pass",
  "is_match": false,
  "message": "Action recorded"
}

// Super Like Action
{
  "action": "super_like",
  "target_user_id": 44
}

Response (200):
{
  "action": "super_like",
  "is_match": false,
  "message": "Action recorded"
}
```

**Validation Errors**:

```json
// Invalid Action (422)
{
  "action": "invalid",
  "target_user_id": 42
}

Response:
{
  "message": "Validation failed",
  "errors": {
    "action": ["The action field must be one of: like, pass, super_like"]
  }
}

// Non-existent User (422)
{
  "action": "like",
  "target_user_id": 99999
}

Response:
{
  "message": "Validation failed",
  "errors": {
    "target_user_id": ["The selected target_user_id is invalid."]
  }
}

// Action on Self (400)
{
  "action": "like",
  "target_user_id": 1  // your own ID
}

Response:
{
  "message": "Cannot perform action on yourself"
}

// User Not Accessible (400)
// e.g., blocked user, deleted user
{
  "message": "User not accessible"
}
```

**Valid Actions**:
- `like`: Standard like
- `pass`: Skip/reject
- `super_like`: Premium like (higher visibility)

---

## Match Logic

### Discovery Feed Exclusions
Users are excluded from feed if:
1. It's the current user (self)
2. User has no profile
3. User is blocked by you
4. You are blocked by user
5. Already matched (mutual like exists)
6. Already passed (you passed on them)

### Mutual Match Conditions
A match is created when:
1. User A likes User B
2. User B likes User A back (or vice versa)
3. Result: `is_match: true` response

### Distance Calculation
- Uses Haversine formula for great-circle distance
- Based on latitude/longitude in user profiles
- Measured in miles
- Only users with location data included in distance-based filtering

### Match Score Calculation
Factors may include:
- Location proximity
- Age compatibility
- Shared interests in `looking_for`
- Profile completeness
- Recent activity

---

## Postman Test Collection

### Setup Requirements
1. **Create multiple test users** to simulate matches:
   - User A (main test account)
   - User B, C, D (potential matches)
2. **Complete profiles** for all test users:
   - Set date_of_birth (18+)
   - Set location (latitude, longitude)
   - Set looking_for preferences
3. **Login as each user** and save tokens

### Test Sequence

**Test 1: Get Matches (Incomplete Profile)**
- Login as new user without profile
- GET {{baseUrl}}/matches
- Expected: 400, "Profile not found"

**Test 2: Complete Profile**
- PUT {{baseUrl}}/user
- Body: Complete profile data (name, age, location, looking_for)
- Expected: 200

**Test 3: Get Matches (Empty Feed)**
- GET {{baseUrl}}/matches
- Expected: 200, empty matches array (if no other users)

**Test 4: Get Matches (With Results)**
- Ensure other test users exist with profiles
- GET {{baseUrl}}/matches
- Expected: 200, array of potential matches

**Test 5: Filter by Age**
- GET {{baseUrl}}/matches?age_min=25&age_max=35
- Expected: 200, only matches in age range

**Test 6: Filter by Distance**
- GET {{baseUrl}}/matches?max_distance=10
- Expected: 200, only matches within 10 miles

**Test 7: Like a User**
- POST {{baseUrl}}/matches/action
- Body:
```json
{
  "action": "like",
  "target_user_id": {{userBId}}
}
```
- Expected: 200, is_match: false (unless User B already liked you)

**Test 8: Create Mutual Match**
- Switch to User B's token
- POST {{baseUrl}}/matches/action
- Body:
```json
{
  "action": "like",
  "target_user_id": {{userAId}}
}
```
- Expected: 200, is_match: true, "It's a match!"

**Test 9: Pass on User**
- POST {{baseUrl}}/matches/action
- Body:
```json
{
  "action": "pass",
  "target_user_id": {{userCId}}
}
```
- Expected: 200, is_match: false

**Test 10: Super Like User**
- POST {{baseUrl}}/matches/action
- Body:
```json
{
  "action": "super_like",
  "target_user_id": {{userDId}}
}
```
- Expected: 200, is_match: false

**Test 11: Verify Exclusions**
- GET {{baseUrl}}/matches
- Expected: User B (matched), User C (passed) NOT in feed

**Test 12: Try Action on Self**
- POST {{baseUrl}}/matches/action
- Body:
```json
{
  "action": "like",
  "target_user_id": {{ownUserId}}
}
```
- Expected: 400, "Cannot perform action on yourself"

**Test 13: Try Invalid Action**
- POST {{baseUrl}}/matches/action
- Body:
```json
{
  "action": "invalid_action",
  "target_user_id": {{userBId}}
}
```
- Expected: 422, validation error

**Test 14: Cache Verification**
- GET {{baseUrl}}/matches
- Note timestamp
- GET {{baseUrl}}/matches (within 60 seconds)
- Expected: Same results (cached), fast response

**Test 15: Cache with Different Filters**
- GET {{baseUrl}}/matches?age_min=25
- GET {{baseUrl}}/matches?age_min=30
- Expected: Different results (different cache keys)

---

## Validation Test Matrix

| Test | Input | Expected |
|------|-------|----------|
| No profile | GET /matches | 400, "Profile not found" |
| Valid request | GET /matches | 200, matches array |
| Age min < 18 | ?age_min=17 | 422, validation error |
| Age max > 100 | ?age_max=101 | 422, validation error |
| Distance < 1 | ?max_distance=0 | 422, validation error |
| Distance > 500 | ?max_distance=501 | 422, validation error |
| Valid like | action=like | 200, is_match boolean |
| Valid pass | action=pass | 200, is_match false |
| Valid super_like | action=super_like | 200, is_match boolean |
| Invalid action | action=invalid | 422, validation error |
| Missing action | {} | 422, "action required" |
| Missing target | {action:like} | 422, "target_user_id required" |
| Non-existent user | target_user_id=99999 | 422, invalid |
| Action on self | target_user_id=own_id | 400, cannot action self |

---

## curl Examples

```bash
# Get matches
curl -H "Authorization: Bearer $TOKEN" \
  "http://localhost:8000/api/matches"

# Get matches with filters
curl -H "Authorization: Bearer $TOKEN" \
  "http://localhost:8000/api/matches?age_min=25&age_max=35&max_distance=20"

# Like a user
curl -X POST http://localhost:8000/api/matches/action \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"action": "like", "target_user_id": 42}'

# Pass on user
curl -X POST http://localhost:8000/api/matches/action \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"action": "pass", "target_user_id": 43}'

# Super like
curl -X POST http://localhost:8000/api/matches/action \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"action": "super_like", "target_user_id": 44}'
```

---

## Database Verification

After actions, verify in database:

**Match Actions Table**:
```sql
SELECT * FROM match_actions 
WHERE user_id = 1 OR target_user_id = 1
ORDER BY created_at DESC;
```

**Matches Table** (mutual matches):
```sql
SELECT * FROM matches 
WHERE user_id_1 = 1 OR user_id_2 = 1;
```

**Expected Schema**:
- `match_actions`: user_id, target_user_id, action, created_at
- `matches`: user_id_1, user_id_2, match_score, created_at

---

## Expected Behavior Summary

✅ **Must Work**:
- Get matches feed with valid profile
- Filter by age range (18-100)
- Filter by distance (1-500 miles)
- Like/pass/super_like actions recorded
- Mutual matches detected (is_match: true)
- Matched users excluded from future feed
- Passed users excluded from future feed
- Cache operates correctly (60s TTL)
- Different filters use different cache keys

❌ **Must Fail**:
- Get matches without profile → 400
- Get matches without auth → 401
- Action without auth → 401
- Invalid action value → 422
- Action on self → 400
- Action on non-existent user → 422
- Age/distance out of range → 422

🔒 **Security**:
- All endpoints require authentication
- Users can only perform actions on accessible users
- Blocked users not shown in feed
- Users who blocked you not shown in feed
- Cannot action yourself

📊 **Performance**:
- Feed cached for 60 seconds
- Cache key includes user ID + filter params
- Telemetry emitted on feed views
- Distance calculations optimized with spatial queries

---

**Status**: Ready for manual testing
**Prerequisites**: 
1. Multiple test user accounts with complete profiles
2. Users with various locations/ages for filter testing
3. Database accessible for verification queries

**Next**: Run through Postman collection or curl tests
