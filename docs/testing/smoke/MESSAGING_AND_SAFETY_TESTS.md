# Messaging & Safety Features Testing Guide

## Overview
Tests for direct messaging, groups/chatrooms, blocking, and reporting functionality.

---

## Part 1: Direct Messaging

### Endpoints

#### 1. Send Message
**POST** `/api/messages`

```json
// Valid Message
{
  "recipient_id": 42,
  "message": "Hey, how are you?"
}

// Response (201)
{
  "success": true,
  "data": {
    "id": 123,
    "sender_id": 1,
    "recipient_id": 42,
    "message": "Hey, how are you?",
    "read_at": null,
    "created_at": "2025-11-15T12:00:00Z"
  }
}

// Not Matched (403)
{
  "message": "Cannot send message. Not matched with this user."
}

// Blocked User (403)
{
  "message": "Cannot send message to blocked user."
}
```

#### 2. Get Conversation
**GET** `/api/messages/{userId}`

```json
// Response (200)
{
  "success": true,
  "data": [
    {
      "id": 123,
      "sender_id": 1,
      "recipient_id": 42,
      "message": "Hey, how are you?",
      "read_at": "2025-11-15T12:05:00Z",
      "created_at": "2025-11-15T12:00:00Z"
    },
    {
      "id": 124,
      "sender_id": 42,
      "recipient_id": 1,
      "message": "I'm good, thanks!",
      "read_at": null,
      "created_at": "2025-11-15T12:03:00Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 50,
    "total": 2
  }
}
```

#### 3. Mark as Read
**POST** `/api/messages/{messageId}/read`

```json
// Response (200)
{
  "success": true,
  "message": "Message marked as read"
}
```

#### 4. Unread Count
**GET** `/api/messages/unread-count`

```json
// Response (200)
{
  "unread_count": 5
}
```

---

## Part 2: Groups & Chatrooms

### Group Endpoints

#### 1. Create Group
**POST** `/api/groups`

```json
// Request
{
  "name": "Hiking Enthusiasts",
  "description": "Group for hiking lovers",
  "is_private": false,
  "max_members": 50
}

// Response (201)
{
  "success": true,
  "data": {
    "id": 10,
    "name": "Hiking Enthusiasts",
    "description": "Group for hiking lovers",
    "owner_id": 1,
    "is_private": false,
    "member_count": 1,
    "created_at": "2025-11-15T12:00:00Z"
  }
}
```

#### 2. List Groups
**GET** `/api/groups`

```json
// Response (200)
{
  "data": [
    {
      "id": 10,
      "name": "Hiking Enthusiasts",
      "member_count": 15,
      "is_member": true
    }
  ]
}
```

#### 3. Join Group
**POST** `/api/groups/{groupId}/join`

```json
// Response (200)
{
  "success": true,
  "message": "Joined group successfully"
}
```

#### 4. Send Group Message
**POST** `/api/groups/{groupId}/messages`

```json
// Request
{
  "message": "Anyone up for a hike this weekend?"
}

// Response (201)
{
  "success": true,
  "data": {
    "id": 456,
    "group_id": 10,
    "user_id": 1,
    "message": "Anyone up for a hike this weekend?",
    "created_at": "2025-11-15T12:00:00Z"
  }
}
```

### Chatroom Endpoints

#### 1. List Chatrooms
**GET** `/api/chatrooms`

```json
// Response (200)
{
  "data": [
    {
      "id": 5,
      "name": "General Chat",
      "category": "social",
      "active_users": 23,
      "is_joined": false
    }
  ]
}
```

#### 2. Join Chatroom
**POST** `/api/chatrooms/{id}/join`

```json
// Response (200)
{
  "success": true,
  "message": "Joined chatroom"
}
```

#### 3. Send Chatroom Message
**POST** `/api/chatrooms/{chatroomId}/messages`

```json
// Request
{
  "message": "Hello everyone!"
}

// Response (201)
{
  "success": true,
  "data": {
    "id": 789,
    "chatroom_id": 5,
    "user_id": 1,
    "message": "Hello everyone!",
    "created_at": "2025-11-15T12:00:00Z"
  }
}
```

---

## Part 3: Safety Features

### Block User
**POST** `/api/blocks`

```json
// Request
{
  "blocked_user_id": 42
}

// Response (201)
{
  "success": true,
  "message": "User blocked successfully"
}

// Already Blocked (422)
{
  "message": "User already blocked"
}

// Block Self (400)
{
  "message": "Cannot block yourself"
}
```

### Unblock User
**DELETE** `/api/blocks/{blockedId}`

```json
// Response (200)
{
  "success": true,
  "message": "User unblocked successfully"
}
```

### Report User
**POST** `/api/reports`

```json
// Request
{
  "reported_user_id": 42,
  "reason": "harassment",
  "description": "User sent inappropriate messages",
  "evidence": {
    "message_ids": [123, 124],
    "screenshots": ["url1", "url2"]
  }
}

// Response (201)
{
  "success": true,
  "message": "Report submitted successfully",
  "data": {
    "id": 50,
    "status": "pending",
    "created_at": "2025-11-15T12:00:00Z"
  }
}

// Valid Reasons:
// - harassment
// - inappropriate_content
// - spam
// - fake_profile
// - underage
// - threatening_behavior
// - other
```

### List Reports (if admin)
**GET** `/api/reports`

```json
// Response (200)
{
  "data": [
    {
      "id": 50,
      "reported_user_id": 42,
      "reporter_id": 1,
      "reason": "harassment",
      "status": "pending",
      "created_at": "2025-11-15T12:00:00Z"
    }
  ]
}
```

---

## Test Scenarios

### Messaging Flow
1. User A and User B match
2. User A sends message to User B → 201
3. User B retrieves conversation → sees message
4. User B marks message as read → 200
5. User A checks unread count → 0
6. User B sends reply → 201
7. User A retrieves conversation → sees both messages

### Block Flow
1. User A blocks User B → 201
2. User B tries to send message to User A → 403
3. User B removed from User A's match feed
4. User A cannot see User B in searches
5. User A unblocks User B → 200
6. User B can message again (if still matched)

### Report Flow
1. User A reports User B for harassment → 201
2. Report status: "pending"
3. Admin reviews report (separate endpoint)
4. Admin takes action (warn/suspend/ban)
5. Reporter gets notification of outcome

### Group Flow
1. User A creates group → 201
2. User B discovers group → sees in list
3. User B joins group → 200
4. User B sends message → 201
5. User A sees message in group thread
6. User A bans User B → User B removed

---

## Validation Tests

### Messaging
- ✅ Send message to matched user
- ❌ Send message to non-matched user → 403
- ❌ Send message to blocked user → 403
- ❌ Send message to user who blocked you → 403
- ✅ Empty message rejected → 422
- ✅ Message > max length rejected → 422

### Groups
- ✅ Create group with valid data
- ❌ Create group without name → 422
- ✅ Join public group
- ❌ Join private group without invite → 403
- ✅ Send message as member
- ❌ Send message as non-member → 403
- ✅ Owner can ban members
- ❌ Non-owner cannot ban → 403

### Safety
- ✅ Block user
- ❌ Block self → 400
- ❌ Block already blocked user → 422
- ✅ Unblock user
- ✅ Report user with valid reason
- ❌ Report without reason → 422
- ❌ Report self → 400

---

## curl Examples

```bash
# Send message
curl -X POST http://localhost:8000/api/messages \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"recipient_id": 42, "message": "Hello!"}'

# Get conversation
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/messages/42

# Block user
curl -X POST http://localhost:8000/api/blocks \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"blocked_user_id": 42}'

# Report user
curl -X POST http://localhost:8000/api/reports \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "reported_user_id": 42,
    "reason": "harassment",
    "description": "Inappropriate behavior"
  }'

# Create group
curl -X POST http://localhost:8000/api/groups \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "My Group",
    "description": "Group description",
    "is_private": false
  }'
```

---

**Status**: Ready for testing
**Prerequisites**: Multiple user accounts, some with mutual matches
**Next**: Run through test scenarios in sequence
