# Proximity Chatroom Database Schema - Complete Implementation

## Core Tables

### proximity_chatrooms Table
```sql
CREATE TABLE proximity_chatrooms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    location POINT NOT NULL,
    radius_meters INT NOT NULL DEFAULT 500,
    type ENUM('networking', 'social', 'dating', 'professional', 'casual') NOT NULL,
    is_public BOOLEAN NOT NULL DEFAULT TRUE,
    owner_id BIGINT UNSIGNED NOT NULL,
    last_activity_at TIMESTAMP NULL,
    member_count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_location (location),
    INDEX idx_type (type),
    INDEX idx_owner (owner_id),
    INDEX idx_public (is_public),
    INDEX idx_activity (last_activity_at),
    
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### proximity_chatroom_members Table
```sql
CREATE TABLE proximity_chatroom_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proximity_chatroom_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('member', 'moderator', 'admin') NOT NULL DEFAULT 'member',
    joined_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_membership (proximity_chatroom_id, user_id),
    INDEX idx_chatroom (proximity_chatroom_id),
    INDEX idx_user (user_id),
    INDEX idx_role (role),
    
    FOREIGN KEY (proximity_chatroom_id) REFERENCES proximity_chatrooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### proximity_chatroom_messages Table
```sql
CREATE TABLE proximity_chatroom_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proximity_chatroom_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    type ENUM('general', 'networking', 'social', 'professional') NOT NULL DEFAULT 'general',
    is_pinned BOOLEAN NOT NULL DEFAULT FALSE,
    is_deleted BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    INDEX idx_chatroom (proximity_chatroom_id),
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_pinned (is_pinned),
    INDEX idx_created (created_at),
    
    FOREIGN KEY (proximity_chatroom_id) REFERENCES proximity_chatrooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

## Spatial Queries

### Find Nearby Chatrooms
```sql
SELECT 
    *,
    ST_Distance_Sphere(
        location,
        ST_GeomFromText(?, 4326)
    ) as distance_meters
FROM proximity_chatrooms
WHERE ST_DWithin(
    location,
    ST_GeomFromText(?, 4326),
    ?
)
ORDER BY distance_meters;
```

### Spatial Indexes
```sql
-- Create spatial index for efficient geospatial queries
ALTER TABLE proximity_chatrooms ADD SPATIAL INDEX idx_location_spatial (location);

-- Create composite indexes for performance
CREATE INDEX idx_type_public ON proximity_chatrooms(type, is_public);
CREATE INDEX idx_owner_type ON proximity_chatrooms(owner_id, type);
CREATE INDEX idx_activity_type ON proximity_chatrooms(last_activity_at, type);
```

## Data Relationships

### Model Relationships
```php
// ProximityChatroom Model
class ProximityChatroom extends Model
{
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members()
    {
        return $this->hasMany(ProximityChatroomMember::class);
    }

    public function messages()
    {
        return $this->hasMany(ProximityChatroomMessage::class);
    }
}

// ProximityChatroomMember Model
class ProximityChatroomMember extends Model
{
    public function proximityChatroom()
    {
        return $this->belongsTo(ProximityChatroom::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// ProximityChatroomMessage Model
class ProximityChatroomMessage extends Model
{
    public function proximityChatroom()
    {
        return $this->belongsTo(ProximityChatroom::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

## Performance Optimization

### Spatial Query Optimization
- Use spatial indexes for efficient geospatial queries
- Implement proper query patterns for proximity searches
- Cache frequently accessed location data
- Optimize radius-based queries with proper indexing

### Database Performance
- Connection pooling for database efficiency
- Query optimization for spatial operations
- Proper indexing strategy for all query patterns
- Caching strategy for frequently accessed data

### Scalability Considerations
- Horizontal scaling with database sharding
- Load balancing for multiple server instances
- CDN integration for static assets
- Database clustering for high availability

## Security Implementation

### Data Protection
- Encrypted location data storage
- Secure transmission over HTTPS
- GDPR compliance with data retention policies
- User consent for location sharing

### Access Control
- Role-based permissions (member, moderator, admin)
- Location verification for proximity features
- Private chatroom access controls
- User blocking and muting capabilities

### Content Moderation
- AI-powered content filtering
- User reporting system
- Admin moderation tools
- Automated abuse detection

## Migration Strategy

### Database Migrations
```php
// Create proximity_chatrooms table
Schema::create('proximity_chatrooms', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description');
    $table->point('location');
    $table->integer('radius_meters')->default(500);
    $table->enum('type', ['networking', 'social', 'dating', 'professional', 'casual']);
    $table->boolean('is_public')->default(true);
    $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
    $table->timestamp('last_activity_at')->nullable();
    $table->integer('member_count')->default(0);
    $table->timestamps();
    
    $table->spatialIndex('location');
    $table->index(['type', 'is_public']);
    $table->index('owner_id');
    $table->index('last_activity_at');
});
```

### Data Seeding
```php
// Seed proximity chatrooms with sample data
ProximityChatroom::factory()->count(50)->create([
    'type' => 'networking',
    'is_public' => true,
    'radius_meters' => 1000
]);
```

This database schema provides a robust foundation for the proximity chatroom system with efficient spatial queries, proper relationships, and optimized performance for location-based networking and social interaction.