# Chatroom Message Type Normalization (2025-11-09)

This document explains the schema and code normalization performed to ensure consistent handling of chatroom message types and related relationships.

## Summary

- Standardized `chatroom_messages` schema to match model/controller usage.
- Adopted `type` column for chatroom messages (enum: `text`, `image`, `file`, `system`).
- Introduced thread parent reference `parent_id` (replacing legacy `reply_to`).
- Added commonly used denormalized flags and counters: `is_pinned`, `is_announcement`, `reaction_count`, `reply_count`.
- Created supporting tables for reactions and mentions with consistent foreign keys.
- Fixed model foreign key mismatches and ensured relationships eager-load cleanly.

## Schema Changes

1. `chatroom_messages`
   - Columns:
     - id, chatroom_id, user_id
     - content (text)
     - type enum: `text` | `image` | `file` | `system` (default `text`)
     - file_* (optional metadata: path, name, size, type)
     - parent_id (nullable FK to `chatroom_messages.id`)
     - is_edited (bool), edited_at (timestamp)
     - is_deleted (bool), deleted_at (timestamp)
     - reactions (json), metadata (json)
     - is_pinned (bool), is_announcement (bool)
     - reaction_count (uint), reply_count (uint)
     - created_at, updated_at
   - Indexes: (chatroom_id, created_at), (user_id, created_at), (type, created_at), (parent_id, created_at), (is_pinned, created_at), (is_announcement, created_at)

2. `chatroom_message_reactions`
   - Columns: id, chatroom_message_id, user_id, emoji, timestamps
   - FKs: `chatroom_message_id` → `chatroom_messages.id` (cascade), `user_id` → `users.id` (cascade)
   - Indexes: (chatroom_message_id, emoji), (user_id, created_at)

3. `chatroom_message_mentions`
   - Columns: id, chatroom_message_id, mentioned_user_id, position, length, timestamps
   - FKs: `chatroom_message_id` → `chatroom_messages.id` (cascade), `mentioned_user_id` → `users.id` (cascade)
   - Indexes: (chatroom_message_id, mentioned_user_id)

## Model Updates

- `App\Models\ChatroomMessage`
  - Relationships assume FK `parent_id` for threads.
  - `replies()` and scopes use `parent_id` consistently.
  - `scopeByType` filters by `type` column.

- `App\Models\ChatroomMessageReaction`
  - Uses FK `chatroom_message_id` (was `message_id`).
  - `message()` relation updated accordingly.

- `App\Models\ChatroomMessageMention`
  - Uses FK `chatroom_message_id` (was `message_id`).
  - `message()` relation updated accordingly.

## Controller Updates

- `App\Http\Controllers\ChatroomMessageController@store`
  - Validation uses `type` and optional `parent_id`.
  - Insert payload uses `type`, sets `is_announcement` when `type === 'announcement'`.
  - Eager-loads `user`, `reactions`, `mentions.mentionedUser` safely now that tables exist.

## Tests

- `Tests\Feature\MutualMatchChatroomTest`: Ensures mutual match auto-creates a chatroom and a `system` message.
- `Tests\Feature\ChatroomMessageTypeTest`: Verifies `POST /api/chatrooms/{id}/messages` persists `type=image`.

All tests are passing:
- 30 passed, 10 skipped (AI integration tests intentionally skipped without API keys).

## Migration Notes

- New columns and tables were added in the initial migration set for test DB (SQLite) consistency.
- If you already migrated locally, you may need to reset:
  - Option A: `php artisan migrate:fresh`
  - Option B: Create follow-up migrations to add any missing columns and rename `reply_to` → `parent_id`.

## Backward Compatibility

- Direct user-to-user `messages` still use `message_type`; those were intentionally left unchanged (separate domain from `chatroom_messages.type`).
- API responses for chatroom messages now consistently expose `type`.

## Next Steps

- Consider adding DB-level enum constraints in MySQL/PostgreSQL for `type` to mirror the application enum.
- Add moderation flags/columns if future policies require quarantining rather than blocking.
- Add composite index `(chatroom_id, id)` for pagination by id if switching away from `created_at` ordering.
