# Proximity Chatroom API Specification - Complete Implementation

## API Overview
The Proximity Chatroom API provides comprehensive endpoints for location-based networking and social interaction, supporting real-time messaging, member management, and spatial queries.

## Authentication
All API endpoints require JWT authentication:
```
Authorization: Bearer {jwt_token}
```

## Base URL
```
http://localhost:8000/api
```

## Endpoints

### Proximity Chatroom Management

#### Find Nearby Chatrooms
```http
GET /proximity-chatrooms/find-nearby
```

**Query Parameters:**
- `latitude` (required): User's latitude
- `longitude` (required): User's longitude
- `radius_meters` (optional): Search radius in meters (default: 1000)
- `type` (optional): Filter by chatroom type
- `search` (optional): Search term for chatroom names

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Tech Networking NYC",
      "description": "Professional networking for tech professionals",
      "type": "networking",
      "radius_meters": 1000,
      "is_public": true,
      "member_count": 25,
      "distance_meters": 500,
      "last_activity_at": "2025-01-19T10:30:00Z"
    }
  ],
  "total": 1
}
```

#### Create Proximity Chatroom
```http
POST /proximity-chatrooms
```

**Request Body:**
```json
{
  "name": "Tech Networking NYC",
  "description": "Professional networking for tech professionals",
  "latitude": 40.7128,
  "longitude": -74.0060,
  "radius_meters": 1000,
  "type": "networking",
  "is_public": true
}
```

**Response:**
```json
{
  "id": 1,
  "name": "Tech Networking NYC",
  "description": "Professional networking for tech professionals",
  "type": "networking",
  "radius_meters": 1000,
  "is_public": true,
  "owner_id": 1,
  "member_count": 1,
  "created_at": "2025-01-19T10:30:00Z"
}
```

#### Get Chatroom Details
```http
GET /proximity-chatrooms/{id}
```

**Query Parameters:**
- `latitude` (optional): User's latitude for distance calculation
- `longitude` (optional): User's longitude for distance calculation

**Response:**
```json
{
  "id": 1,
  "name": "Tech Networking NYC",
  "description": "Professional networking for tech professionals",
  "type": "networking",
  "radius_meters": 1000,
  "is_public": true,
  "owner_id": 1,
  "member_count": 25,
  "distance_meters": 500,
  "last_activity_at": "2025-01-19T10:30:00Z",
  "owner": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

#### Join Chatroom
```http
POST /proximity-chatrooms/{id}/join
```

**Request Body:**
```json
{
  "latitude": 40.7128,
  "longitude": -74.0060
}
```

**Response:**
```json
{
  "success": true,
  "message": "Successfully joined chatroom",
  "member": {
    "id": 1,
    "proximity_chatroom_id": 1,
    "user_id": 1,
    "role": "member",
    "joined_at": "2025-01-19T10:30:00Z"
  }
}
```

#### Leave Chatroom
```http
POST /proximity-chatrooms/{id}/leave
```

**Response:**
```json
{
  "success": true,
  "message": "Successfully left chatroom"
}
```

#### Update Location
```http
PUT /proximity-chatrooms/{id}/location
```

**Request Body:**
```json
{
  "latitude": 40.7130,
  "longitude": -74.0062
}
```

**Response:**
```json
{
  "success": true,
  "message": "Location updated successfully"
}
```

#### Get Chatroom Members
```http
GET /proximity-chatrooms/{id}/members
```

**Query Parameters:**
- `networking_only` (optional): Filter for networking-focused members
- `social_only` (optional): Filter for social-focused members

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "proximity_chatroom_id": 1,
      "user_id": 1,
      "role": "admin",
      "joined_at": "2025-01-19T10:30:00Z",
      "last_seen_at": "2025-01-19T11:00:00Z",
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "profile": {
          "profile_complete": true,
          "completion_percentage": 85
        }
      }
    }
  ],
  "total": 1
}
```

#### Get Nearby Networking
```http
GET /proximity-chatrooms/{id}/nearby-networking
```

**Query Parameters:**
- `latitude` (required): User's latitude
- `longitude` (required): User's longitude
- `radius_meters` (optional): Search radius in meters

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Jane Smith",
      "email": "jane@example.com",
      "distance_meters": 300,
      "profile": {
        "industry": "Technology",
        "experience_level": "Senior",
        "interests": ["AI", "Machine Learning"]
      }
    }
  ],
  "total": 1
}
```

#### Get Analytics
```http
GET /proximity-chatrooms/{id}/analytics
```

**Response:**
```json
{
  "member_count": 25,
  "message_count": 150,
  "active_members": 20,
  "networking_messages": 75,
  "social_messages": 50,
  "professional_messages": 25,
  "average_session_duration": 1800,
  "peak_activity_hours": [18, 19, 20],
  "geographic_distribution": {
    "within_radius": 20,
    "nearby_users": 5
  }
}
```

### Proximity Chatroom Messages

#### Get Messages
```http
GET /proximity-chatrooms/{id}/messages
```

**Query Parameters:**
- `type` (optional): Filter by message type
- `limit` (optional): Number of messages to return (default: 50)
- `offset` (optional): Offset for pagination

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "proximity_chatroom_id": 1,
      "user_id": 1,
      "content": "Hello everyone! Looking forward to networking with you all.",
      "type": "networking",
      "is_pinned": false,
      "is_deleted": false,
      "created_at": "2025-01-19T10:30:00Z",
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "reactions": [
        {
          "emoji": "👍",
          "count": 5,
          "users": [1, 2, 3, 4, 5]
        }
      ]
    }
  ],
  "total": 1,
  "has_more": false
}
```

#### Send Message
```http
POST /proximity-chatrooms/{id}/messages
```

**Request Body:**
```json
{
  "content": "Hello everyone! Looking forward to networking with you all.",
  "type": "networking"
}
```

**Response:**
```json
{
  "id": 1,
  "proximity_chatroom_id": 1,
  "user_id": 1,
  "content": "Hello everyone! Looking forward to networking with you all.",
  "type": "networking",
  "is_pinned": false,
  "is_deleted": false,
  "created_at": "2025-01-19T10:30:00Z",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

#### Get Pinned Messages
```http
GET /proximity-chatrooms/{id}/messages/pinned
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "content": "Welcome to our networking chatroom!",
      "type": "networking",
      "is_pinned": true,
      "created_at": "2025-01-19T10:30:00Z",
      "user": {
        "id": 1,
        "name": "John Doe"
      }
    }
  ],
  "total": 1
}
```

#### Get Networking Messages
```http
GET /proximity-chatrooms/{id}/messages/networking
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "content": "Looking for a software engineer position",
      "type": "networking",
      "created_at": "2025-01-19T10:30:00Z",
      "user": {
        "id": 1,
        "name": "John Doe"
      }
    }
  ],
  "total": 1
}
```

#### Get Social Messages
```http
GET /proximity-chatrooms/{id}/messages/social
```

**Response:**
```json
{
  "data": [
    {
      "id": 2,
      "content": "Anyone up for coffee after work?",
      "type": "social",
      "created_at": "2025-01-19T10:30:00Z",
      "user": {
        "id": 2,
        "name": "Jane Smith"
      }
    }
  ],
  "total": 1
}
```

#### Add Reaction
```http
POST /proximity-chatrooms/{id}/messages/{messageId}/reactions
```

**Request Body:**
```json
{
  "emoji": "👍"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Reaction added successfully",
  "reaction": {
    "emoji": "👍",
    "count": 6,
    "users": [1, 2, 3, 4, 5, 6]
  }
}
```

#### Remove Reaction
```http
DELETE /proximity-chatrooms/{id}/messages/{messageId}/reactions
```

**Request Body:**
```json
{
  "emoji": "👍"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Reaction removed successfully"
}
```

#### Pin Message
```http
POST /proximity-chatrooms/{id}/messages/{messageId}/pin
```

**Response:**
```json
{
  "success": true,
  "message": "Message pinned successfully"
}
```

#### Unpin Message
```http
DELETE /proximity-chatrooms/{id}/messages/{messageId}/pin
```

**Response:**
```json
{
  "success": true,
  "message": "Message unpinned successfully"
}
```

## Error Handling

### Error Response Format
```json
{
  "error": "Validation failed",
  "message": "The given data was invalid.",
  "errors": {
    "latitude": ["The latitude field is required."],
    "longitude": ["The longitude field is required."]
  }
}
```

### HTTP Status Codes
- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Internal Server Error

## Rate Limiting
- Default: 60 requests per minute per user
- Proximity queries: 10 requests per minute per user
- Message sending: 30 requests per minute per user

## WebSocket Events
- `proximity_chatroom_joined` - User joined chatroom
- `proximity_chatroom_left` - User left chatroom
- `proximity_message_sent` - New message sent
- `proximity_message_updated` - Message updated
- `proximity_message_deleted` - Message deleted
- `proximity_reaction_added` - Reaction added
- `proximity_reaction_removed` - Reaction removed
- `proximity_message_pinned` - Message pinned
- `proximity_message_unpinned` - Message unpinned

This API specification provides comprehensive documentation for all proximity chatroom functionality, enabling developers to integrate with the system effectively.