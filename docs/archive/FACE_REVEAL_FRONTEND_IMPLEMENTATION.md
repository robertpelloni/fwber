# Face Reveal Frontend Implementation

## Overview
Implemented the frontend components and logic for the "Face Reveal" feature, allowing users to securely reveal original photos of their matches.

## Changes

### 1. API Client Update (`lib/api/photos.ts`)
- Added `revealPhoto(photoId, matchId)`: Calls `POST /photos/{id}/reveal`.
- Added `getOriginalPhoto(photoId)`: Calls `GET /photos/{id}/original` and returns a Blob.

### 2. New Component: `SecurePhotoReveal` (`components/SecurePhotoReveal.tsx`)
- Handles the UI for a single photo.
- Shows the public (blurred/AI) version by default.
- Provides a "Reveal Original" button.
- Handles the API calls to reveal and fetch the original image.
- Displays the original image upon success.

### 3. Updated Component: `ProfileViewModal` (`components/ProfileViewModal.tsx`)
- Added `matchId` prop.
- If `matchId` is provided, renders a grid of `SecurePhotoReveal` components instead of the legacy `PhotoRevealGate`.
- Falls back to `PhotoRevealGate` if `matchId` is missing (backward compatibility).

### 4. Updated Page: `MessagesPage` (`app/messages/page.tsx`)
- Passed `matchId={selectedConversation.id}` to `ProfileViewModal` when viewing a match's profile.

## Usage
When a user opens a match's profile from the Messages page:
1. They see the user's photos.
2. If they haven't revealed a photo yet, they see the public version (blurred or AI-generated) with a "Reveal" button.
3. Clicking "Reveal" triggers the backend check.
4. If authorized, the original photo is fetched and displayed.

## Next Steps
- Test the flow end-to-end with a real match.
- Consider adding a "Reveal All" button if the backend supports it (currently per-photo).
- Add visual polish (animations, better loading states).
