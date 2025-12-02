# Profile Management Testing Guide

## Overview
Tests for user profile operations including basic profile CRUD, physical profile updates, and location management.

## Endpoints to Test

### 1. Get Profile
**GET** `/api/user`

**Test Cases**:
```json
// Valid Request (with auth token)
Authorization: Bearer {token}

// Expected Response (200)
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Alice",
    "email": "alice@test.local",
    "display_name": "Ally",
    "bio": "Software developer",
    "date_of_birth": "1995-05-15",
    "age": 30,
    "gender": "female",
    "pronouns": "she/her",
    "sexual_orientation": "bisexual",
    "relationship_style": "non-monogamous",
    "looking_for": ["dating", "relationship"],
    "location": {
      "latitude": 40.7128,
      "longitude": -74.0060,
      "city": "New York",
      "state": "NY"
    },
    "preferences": {
      "smoking": "non-smoker",
      "drinking": "occasional",
      "age_range_min": 25,
      "age_range_max": 40
    }
  },
  "profile_complete": true
}

// Missing Auth (401)
{
  "message": "Unauthenticated"
}

// Profile Not Found (404)
{
  "message": "Profile not found. Please complete your profile.",
  "profile_complete": false
}
```

---

### 2. Update Profile
**PUT** `/api/user`

**Test Cases**:

```json
// Update Basic Info
{
  "display_name": "Updated Name",
  "bio": "Updated bio text"
}

// Update Location
{
  "location": {
    "latitude": 34.0522,
    "longitude": -118.2437,
    "city": "Los Angeles",
    "state": "CA"
  }
}

// Update Preferences
{
  "preferences": {
    "smoking": "non-smoker",
    "drinking": "occasional",
    "exercise": "several-times-week",
    "age_range_min": 28,
    "age_range_max": 42
  }
}

// Update Looking For
{
  "looking_for": ["friendship", "networking"]
}

// Expected Success Response (200)
{
  "success": true,
  "message": "Profile updated successfully",
  "data": { /* updated profile */ },
  "profile_complete": true
}

// Validation Error (422)
{
  "message": "Validation failed",
  "errors": {
    "gender": ["The gender field must be one of: male, female, non-binary..."]
  }
}
```

**Validation Tests**:
- Invalid gender value → 422
- Date of birth before 1900 or after 18 years ago → 422
- Looking_for with invalid values → 422
- Latitude outside -90 to 90 → 422
- Longitude outside -180 to 180 → 422
- Bio exceeding 500 characters → 422
- Display name exceeding 50 characters → 422

---

### 3. Profile Completeness
**GET** `/api/profile/completeness`

**Test Cases**:
```json
// Expected Response (200)
{
  "percentage": 75,
  "completed_fields": 9,
  "total_fields": 12,
  "missing_fields": [
    "Pronouns",
    "Preferences",
    "Bio"
  ],
  "is_complete": false
}
```

**Completeness Requirements** (must all be present for `is_complete: true`):
- display_name
- gender
- location_latitude
- location_longitude
- looking_for (array with at least one item)
- date_of_birth (user must be 18+)

---

### 4. Physical Profile - Get
**GET** `/api/physical-profile`

**Test Cases**:
```json
// Expected Response (200)
{
  "data": {
    "height_cm": 175,
    "body_type": "athletic",
    "hair_color": "brown",
    "eye_color": "blue",
    "skin_tone": "fair",
    "ethnicity": "caucasian",
    "facial_hair": "clean-shaven",
    "tattoos": true,
    "piercings": false,
    "dominant_hand": "right",
    "fitness_level": "athletic",
    "clothing_style": "casual",
    "avatar_prompt": "Athletic person with brown hair",
    "avatar_status": "pending"
  }
}

// Empty Profile (200)
{
  "data": null
}
```

---

### 5. Physical Profile - Upsert
**PUT** `/api/physical-profile`

**Test Cases**:

```json
// Create/Update Physical Profile
{
  "height_cm": 180,
  "body_type": "athletic",
  "hair_color": "black",
  "eye_color": "brown",
  "ethnicity": "asian",
  "tattoos": false,
  "piercings": true,
  "dominant_hand": "left",
  "fitness_level": "fit"
}

// Expected Response (200)
{
  "data": {
    "height_cm": 180,
    "body_type": "athletic",
    // ... all fields
  }
}

// Validation Errors (422)
// - height_cm < 80 or > 250
// - dominant_hand not in [left, right, ambi]
// - fitness_level not in [low, average, fit, athletic]
```

---

### 6. Request Avatar Generation
**POST** `/api/physical-profile/avatar/request`

**Test Cases**:

```json
// Request Avatar
{
  "style": "realistic"
}

// Expected Response (200)
{
  "data": {
    "avatar_status": "requested",
    // ... other fields
  }
}

// Missing avatar_prompt (422)
{
  "error": "Set avatar_prompt first"
}

// Invalid style (422)
{
  "message": "Validation failed",
  "errors": {
    "style": ["The style field must be one of: realistic, anime, fantasy, sci-fi, cartoon, pixel-art"]
  }
}
```

**Valid Styles**: realistic, anime, fantasy, sci-fi, cartoon, pixel-art

---

## Postman Test Collection

Create a collection with the following requests:

### Setup
1. **Environment Variables**:
   - `baseUrl`: http://localhost:8000/api
   - `accessToken`: (from login response)

2. **Pre-request Script** (add to collection):
```javascript
// Automatically set Authorization header
pm.request.headers.add({
  key: 'Authorization',
  value: 'Bearer ' + pm.environment.get('accessToken')
});
```

### Tests Sequence

**Test 1: Get Profile (Empty)**
- GET {{baseUrl}}/user
- Expected: 404 or profile_complete: false

**Test 2: Update Profile - Basic Info**
- PUT {{baseUrl}}/user
- Body:
```json
{
  "display_name": "TestUser",
  "bio": "Test bio",
  "date_of_birth": "1995-05-15",
  "gender": "female",
  "pronouns": "she/her"
}
```
- Expected: 200

**Test 3: Update Profile - Location**
- PUT {{baseUrl}}/user
- Body:
```json
{
  "location": {
    "latitude": 40.7128,
    "longitude": -74.0060,
    "city": "New York",
    "state": "NY"
  }
}
```
- Expected: 200

**Test 4: Update Profile - Looking For**
- PUT {{baseUrl}}/user
- Body:
```json
{
  "looking_for": ["dating", "relationship"]
}
```
- Expected: 200

**Test 5: Check Completeness**
- GET {{baseUrl}}/profile/completeness
- Expected: is_complete: true (if all required fields set)

**Test 6: Get Profile (Complete)**
- GET {{baseUrl}}/user
- Expected: 200, profile_complete: true

**Test 7: Update Physical Profile**
- PUT {{baseUrl}}/physical-profile
- Body:
```json
{
  "height_cm": 175,
  "body_type": "athletic",
  "hair_color": "brown",
  "eye_color": "blue",
  "fitness_level": "fit",
  "avatar_prompt": "Athletic person with brown hair and blue eyes"
}
```
- Expected: 200

**Test 8: Request Avatar**
- POST {{baseUrl}}/physical-profile/avatar/request
- Body:
```json
{
  "style": "realistic"
}
```
- Expected: 200, avatar_status: "requested"

**Test 9: Get Physical Profile**
- GET {{baseUrl}}/physical-profile
- Expected: 200 with all fields

---

## Validation Test Matrix

| Field | Valid Values | Invalid Test | Expected |
|-------|--------------|--------------|----------|
| gender | male, female, non-binary, etc. | "invalid" | 422 |
| pronouns | he/him, she/her, they/them, etc. | "invalid" | 422 |
| date_of_birth | 1900-01-01 to 18 years ago | 2020-01-01 | 422 |
| latitude | -90 to 90 | 100 | 422 |
| longitude | -180 to 180 | 200 | 422 |
| looking_for | friendship, dating, relationship, etc. | ["invalid"] | 422 |
| height_cm | 80 to 250 | 50 | 422 |
| dominant_hand | left, right, ambi | "invalid" | 422 |
| fitness_level | low, average, fit, athletic | "invalid" | 422 |
| bio | max 500 chars | 501+ chars | 422 |
| display_name | max 50 chars | 51+ chars | 422 |

---

## Protected Fields Test

These fields should NOT be updatable via profile endpoints:
- `id` (immutable)
- `email` (should use separate endpoint)
- `created_at`
- `updated_at`

**Test**: Try to update these fields via PUT /api/user
**Expected**: Fields ignored or validation error

---

## curl Examples

```bash
# Get Profile
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/user

# Update Profile
curl -X PUT http://localhost:8000/api/user \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "display_name": "Updated Name",
    "bio": "Updated bio"
  }'

# Get Completeness
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/profile/completeness

# Update Physical Profile
curl -X PUT http://localhost:8000/api/physical-profile \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "height_cm": 175,
    "body_type": "athletic",
    "fitness_level": "fit"
  }'

# Request Avatar
curl -X POST http://localhost:8000/api/physical-profile/avatar/request \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"style": "realistic"}'
```

---

## Expected Behavior Summary

✅ **Must Work**:
- Get profile with valid auth token
- Update profile with valid data
- Profile completeness check
- Create/update physical profile
- Request avatar generation (if avatar_prompt set)

❌ **Must Fail**:
- Get/update profile without auth → 401
- Update with invalid enum values → 422
- Update with out-of-range values → 422
- Update with too-long strings → 422
- Request avatar without avatar_prompt → 422
- Update protected fields (silently ignored or 422)

🔒 **Security**:
- All endpoints require authentication
- Users can only view/edit their own profile
- Validation enforced on all inputs
- Age verification (18+) enforced

---

**Status**: Ready for manual testing
**Next**: Run through all test cases in Postman or via curl
