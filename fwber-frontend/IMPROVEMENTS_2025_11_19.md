# Improvements - November 19, 2025

## Summary
Implemented GenAI Avatar prompt automation and Direct Message Image Attachments.

## Changes

### Frontend
- **PhysicalProfileEditor.tsx**: Added `generatePromptFromAttributes` to automatically create AI avatar prompts based on user's physical attributes (height, hair color, etc.). Added "Regenerate" button.
- **lib/api/messages.ts**: 
  - Updated `sendMessage` to support `FormData` and file uploads.
  - Updated `getConversations` to use new backend route `/matches/established`.
  - Updated `getMessages` to use `/messages/{userId}`.
- **app/messages/page.tsx**: 
  - Added file input UI for sending images/media.
  - Updated message loading and sending logic to use user IDs instead of match IDs.
  - Fixed "Conversations List" by connecting to the new backend endpoint.

### Backend
- **MatchController.php**: Added `establishedMatches` endpoint (`GET /matches/established`) to list users the current user has matched with. This powers the "Conversations" list.
- **routes/api.php**: Registered the `/matches/established` route.

## Verification
- **GenAI Avatars**: Go to Physical Profile, edit attributes, see prompt auto-fill. Click "Generate Avatar".
- **DM Images**: Go to Messages. List should populate with matches. Select a match. Click paperclip icon to attach image. Send.
