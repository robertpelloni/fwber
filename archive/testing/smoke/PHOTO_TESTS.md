# Photo Management Testing Guide

## Overview
Tests for photo upload, listing, updating, reordering, and deletion with validation, file storage, and thumbnail generation.

## Important: Avatar Mode Configuration

The backend enforces an avatar mode setting that may disable photo uploads. Check `config/app.php`:

```php
'avatar_mode' => env('AVATAR_MODE', 'generated-only'), // or 'uploaded-photos'
```

**Modes**:
- `generated-only`: Photo uploads disabled, users get AI-generated avatars → **MVP default**
- `uploaded-photos`: Photo uploads enabled

To test photo uploads, set in `.env`:
```
AVATAR_MODE=uploaded-photos
```

## Endpoints to Test

### 1. List Photos
**GET** `/api/photos`

**Test Cases**:
```json
// Empty List (200)
{
  "success": true,
  "data": [],
  "count": 0,
  "max_photos": 10
}

// With Photos (200)
{
  "success": true,
  "data": [
    {
      "id": 1,
      "filename": "abc-123.jpg",
      "original_filename": "profile.jpg",
      "url": "http://localhost:8000/storage/photos/1/abc-123.jpg",
      "thumbnail_url": "http://localhost:8000/storage/photos/1/thumbnails/thumb_abc-123.jpg",
      "mime_type": "image/jpeg",
      "file_size": 245678,
      "width": 1920,
      "height": 1080,
      "is_primary": true,
      "is_private": false,
      "sort_order": 0,
      "created_at": "2025-11-15T12:00:00.000000Z",
      "updated_at": "2025-11-15T12:00:00.000000Z"
    }
  ],
  "count": 1,
  "max_photos": 10
}

// No Auth (401)
{
  "message": "Unauthenticated"
}
```

---

### 2. Upload Photo
**POST** `/api/photos`

**Content-Type**: `multipart/form-data`

**Test Cases**:

```
// Valid Upload
FormData:
  photo: [image file]
  is_private: false

Expected Response (201):
{
  "success": true,
  "message": "Photo uploaded successfully",
  "data": {
    "id": 1,
    "filename": "generated-uuid.jpg",
    "original_filename": "my-photo.jpg",
    "url": "http://localhost:8000/storage/photos/1/generated-uuid.jpg",
    "thumbnail_url": "http://localhost:8000/storage/photos/1/thumbnails/thumb_generated-uuid.jpg",
    "mime_type": "image/jpeg",
    "file_size": 245678,
    "width": 1920,
    "height": 1080,
    "is_primary": true,
    "is_private": false,
    "sort_order": 0,
    "created_at": "2025-11-15T12:00:00.000000Z"
  }
}
```

**Validation Tests**:

```json
// Generated-Only Mode (403)
{
  "message": "Photo uploads disabled. Using generated avatars only.",
  "avatar_mode": "generated-only"
}

// Missing File (422)
{
  "message": "Validation failed",
  "errors": {
    "photo": ["The photo field is required."]
  }
}

// Invalid File Type (422)
{
  "message": "Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed."
}

// File Too Large (422)
{
  "message": "File too large. Maximum size is 5MB."
}

// Max Photos Reached (422)
{
  "message": "Maximum number of photos reached",
  "max_photos": 10
}
```

**Allowed**:
- MIME types: `image/jpeg`, `image/png`, `image/gif`, `image/webp`
- Max size: 5 MB (5120 KB)
- Max photos per user: 10

---

### 3. Update Photo
**PUT** `/api/photos/{id}`

**Test Cases**:

```json
// Set as Primary
{
  "is_primary": true
}

// Update Privacy
{
  "is_private": true
}

// Update Sort Order
{
  "sort_order": 3
}

// Expected Response (200)
{
  "success": true,
  "message": "Photo updated successfully",
  "data": {
    "id": 1,
    // ... all photo fields
    "is_primary": true,
    "is_private": true,
    "sort_order": 3
  }
}

// Photo Not Found (404)
{
  "message": "Photo not found"
}

// Not Your Photo (404 - because query scopes to user's photos)
{
  "message": "Photo not found"
}
```

**Notes**:
- Setting `is_primary: true` automatically unsets other photos as primary
- Only one photo can be primary at a time

---

### 4. Delete Photo
**DELETE** `/api/photos/{id}`

**Test Cases**:

```json
// Success (200)
{
  "success": true,
  "message": "Photo deleted successfully"
}

// Photo Not Found (404)
{
  "message": "Photo not found"
}

// Not Your Photo (404)
{
  "message": "Photo not found"
}
```

**Behavior**:
- Deletes photo record from database
- Deletes original file from `storage/app/public/photos/{user_id}/{filename}`
- Deletes thumbnail from `storage/app/public/photos/{user_id}/thumbnails/thumb_{filename}`
- If deleted photo was primary, automatically sets next photo as primary

---

### 5. Reorder Photos
**POST** `/api/photos/reorder`

**Test Cases**:

```json
// Reorder Photos
{
  "photo_ids": [3, 1, 5, 2, 4]
}

// Expected Response (200)
{
  "success": true,
  "message": "Photos reordered successfully"
}

// Invalid Photo IDs (422)
{
  "message": "Invalid photo IDs"
}

// Validation Error (422)
{
  "message": "Validation failed",
  "errors": {
    "photo_ids": ["The photo_ids field is required."]
  }
}
```

**Notes**:
- `photo_ids` array determines new order (first ID becomes sort_order 0, etc.)
- All IDs must belong to the authenticated user
- All IDs must exist in database

---

## File Storage & Thumbnails

### Storage Structure
```
storage/
└── app/
    └── public/
        └── photos/
            └── {user_id}/
                ├── original-uuid.jpg       # Original (max 2000x2000)
                ├── another-uuid.png
                └── thumbnails/
                    ├── thumb_original-uuid.jpg  # 300x300 max
                    └── thumb_another-uuid.png
```

### Public URLs
- Original: `http://localhost:8000/storage/photos/{user_id}/{filename}`
- Thumbnail: `http://localhost:8000/storage/photos/{user_id}/thumbnails/thumb_{filename}`

### Image Processing
- **Original**: Scaled down to max 2000x2000 (preserves aspect ratio)
- **Thumbnail**: Scaled down to max 300x300 (preserves aspect ratio)
- Uses **Intervention Image** with GD driver
- Encodes to same format as original

---

## Postman Test Collection

### Setup
1. Set environment variable `AVATAR_MODE=uploaded-photos` in `.env`
2. Restart Laravel server
3. Prepare test images (various sizes, formats)

### Test Sequence

**Test 1: List Photos (Empty)**
- GET {{baseUrl}}/photos
- Expected: 200, empty array

**Test 2: Upload First Photo**
- POST {{baseUrl}}/photos
- Body: form-data
  - photo: [select image file]
  - is_private: false
- Expected: 201, is_primary: true, sort_order: 0

**Test 3: Upload Second Photo**
- POST {{baseUrl}}/photos
- Body: form-data
  - photo: [select another image]
- Expected: 201, is_primary: false, sort_order: 1

**Test 4: List Photos (With Data)**
- GET {{baseUrl}}/photos
- Expected: 200, count: 2, ordered by sort_order

**Test 5: Set Second Photo as Primary**
- PUT {{baseUrl}}/photos/{secondPhotoId}
- Body:
```json
{
  "is_primary": true
}
```
- Expected: 200, is_primary: true

**Test 6: Verify First Photo No Longer Primary**
- GET {{baseUrl}}/photos
- Expected: First photo has is_primary: false

**Test 7: Update Photo Privacy**
- PUT {{baseUrl}}/photos/{photoId}
- Body:
```json
{
  "is_private": true
}
```
- Expected: 200, is_private: true

**Test 8: Upload Multiple Photos**
- Repeat upload until reaching 10 photos
- Expected: All succeed with 201

**Test 9: Try Upload 11th Photo**
- POST {{baseUrl}}/photos
- Expected: 422, "Maximum number of photos reached"

**Test 10: Reorder Photos**
- POST {{baseUrl}}/photos/reorder
- Body:
```json
{
  "photo_ids": [10, 5, 3, 8, 1, 2, 4, 6, 7, 9]
}
```
- Expected: 200

**Test 11: Verify New Order**
- GET {{baseUrl}}/photos
- Expected: Photos in new order by sort_order

**Test 12: Delete Photo**
- DELETE {{baseUrl}}/photos/{photoId}
- Expected: 200

**Test 13: Verify Photo Deleted**
- GET {{baseUrl}}/photos
- Expected: Photo no longer in list, count decreased

**Test 14: Delete Primary Photo**
- DELETE {{baseUrl}}/photos/{primaryPhotoId}
- Expected: 200, next photo becomes primary

**Test 15: Verify URLs Accessible**
- Open photo URL in browser: {{photoUrl}}
- Open thumbnail URL: {{thumbnailUrl}}
- Expected: Images load

---

## Validation Test Matrix

| Test | Input | Expected |
|------|-------|----------|
| No file | {} | 422, "photo field is required" |
| Invalid type | .txt file | 422, "Invalid file type" |
| Too large | 6MB file | 422, "File too large" |
| Valid JPEG | .jpg, 2MB | 201, success |
| Valid PNG | .png, 1MB | 201, success |
| Valid GIF | .gif, 500KB | 201, success |
| Valid WebP | .webp, 800KB | 201, success |
| 11th photo | When at 10 | 422, "Maximum number of photos" |
| Update non-existent | ID 99999 | 404, "Photo not found" |
| Update other user's photo | Valid ID | 404, "Photo not found" |
| Delete non-existent | ID 99999 | 404, "Photo not found" |
| Reorder with invalid ID | [1, 2, 99999] | 422, "Invalid photo IDs" |
| Reorder other user's photo | [otherUserId] | 422, "Invalid photo IDs" |

---

## Storage Verification

After upload, verify:
1. Original file exists: `storage/app/public/photos/{user_id}/{filename}`
2. Thumbnail exists: `storage/app/public/photos/{user_id}/thumbnails/thumb_{filename}`
3. Files are accessible via public URLs
4. Thumbnails are smaller than originals
5. Both maintain aspect ratio

After deletion, verify:
1. Original file removed
2. Thumbnail removed
3. Database record removed
4. URLs return 404

---

## curl Examples

```bash
# List photos
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/photos

# Upload photo
curl -X POST http://localhost:8000/api/photos \
  -H "Authorization: Bearer $TOKEN" \
  -F "photo=@/path/to/image.jpg" \
  -F "is_private=false"

# Update photo
curl -X PUT http://localhost:8000/api/photos/1 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"is_primary": true}'

# Delete photo
curl -X DELETE http://localhost:8000/api/photos/1 \
  -H "Authorization: Bearer $TOKEN"

# Reorder photos
curl -X POST http://localhost:8000/api/photos/reorder \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"photo_ids": [3, 1, 2, 4, 5]}'
```

---

## Expected Behavior Summary

✅ **Must Work**:
- List user's photos with correct URLs
- Upload valid images (JPEG, PNG, GIF, WebP) up to 5MB
- Set any photo as primary (unsets others)
- Update photo privacy flag
- Reorder photos with valid IDs
- Delete photos (removes files + DB record)
- First uploaded photo becomes primary automatically
- Thumbnail generation for all uploads
- When primary photo deleted, next photo becomes primary

❌ **Must Fail**:
- Upload without auth → 401
- Upload in generated-only mode → 403
- Upload invalid file type → 422
- Upload file > 5MB → 422
- Upload 11th photo → 422
- Update/delete non-existent photo → 404
- Update/delete other user's photo → 404
- Reorder with invalid IDs → 422

🔒 **Security**:
- All endpoints require authentication
- Users can only see/modify their own photos
- File type validation enforced
- File size limits enforced
- Photo count limits enforced
- Generated UUIDs prevent filename conflicts

📁 **Storage**:
- Originals stored in `photos/{user_id}/`
- Thumbnails in `photos/{user_id}/thumbnails/`
- Public disk symlink: `public/storage` → `storage/app/public`
- Files deleted when photo record deleted

---

**Status**: Ready for manual testing
**Prerequisites**: 
1. Set `AVATAR_MODE=uploaded-photos` in `.env`
2. Ensure `storage/app/public/photos` directory exists and is writable
3. Ensure public symlink: `php artisan storage:link`
4. Have test images ready (various formats/sizes)

**Next**: Run through Postman collection or curl tests
