# Improvements - November 20, 2025

## Summary
Expanded the collection of sex quotes and refined the Messaging UI.

## Changes

### Frontend
- **components/SexQuote.tsx**: Added a diverse collection of 30+ quotes about sex, intimacy, and relationships, including the requested Salt-N-Pepa quote.
- **app/messages/page.tsx**: Refactored `handleBlock` and `handleReport` to improve type safety and fix potential linting errors regarding `selectedConversation.other_user`.

### Messaging Display
- **app/messages/page.tsx**: Added rendering logic for `image`, `video`, `audio`, and `file` message types. Now displays attachments inline within the chat bubbles.
- **lib/api/messages.ts**: Updated `Message` interface to include `media_url`, `message_type`, etc.

## Verification
- **Quotes**: Refresh the homepage or Proximity Feed to see a random quote from the expanded list.
- **Messaging**: Verify that blocking and reporting users works correctly without console errors.
