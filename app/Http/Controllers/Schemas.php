<?php

namespace App\Http\Controllers;

/**
 * @OA\Response(
 *     response="BadRequest",
 *     description="Bad request",
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 * )
 * 
 * @OA\Response(
 *     response="Forbidden",
 *     description="Forbidden",
 *     @OA\JsonContent(ref="#/components/schemas/ForbiddenError")
 * )
 * 
 * @OA\Response(
 *     response="Unauthorized",
 *     description="Unauthenticated",
 *     @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
 * )
 * 
 * @OA\Response(
 *     response="ValidationError",
 *     description="Validation error",
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 * )
 * 
 * @OA\Response(
 *     response="NotFound",
 *     description="Not found",
 *     @OA\JsonContent(ref="#/components/schemas/NotFoundError")
 * )
 * 
 * @OA\Response(
 *     response="ModerationError",
 *     description="Moderation blocked",
 *     @OA\JsonContent(ref="#/components/schemas/ModerationError")
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     required={"id", "email"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *     @OA\Property(property="avatar_url", type="string", nullable=true, example="https://cdn.fwber.com/avatars/123.jpg"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="The given data was invalid."),
 *     @OA\Property(property="errors", type="object")
 * )
 * 
 * @OA\Schema(
 *     schema="UnauthorizedError",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Unauthenticated")
 * )
 * 
 * @OA\Schema(
 *     schema="ForbiddenError",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Forbidden")
 * )
 * 
 * @OA\Schema(
 *     schema="NotFoundError",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Not Found")
 * )
 * 
 * @OA\Schema(
 *     schema="ModerationError",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Message blocked by content moderation"),
 *     @OA\Property(property="reason", type="string", example="Inappropriate content"),
 *     @OA\Property(property="category", type="string", nullable=true, example="toxicity")
 * )
 * 
 * @OA\Schema(
 *   schema="Reaction",
 *   type="object",
 *   @OA\Property(property="user_id", type="integer", example=42),
 *   @OA\Property(property="emoji", type="string", example="👍"),
 *   @OA\Property(property="created_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *   schema="ChatMessage",
 *   type="object",
 *   required={"id","content","message_type","user"},
 *   @OA\Property(property="id", type="integer", example=123),
 *   @OA\Property(property="user", ref="#/components/schemas/User"),
 *   @OA\Property(property="parent_id", type="integer", nullable=true),
 *   @OA\Property(property="content", type="string", example="Hello there!"),
 *   @OA\Property(property="message_type", type="string", example="text"),
 *   @OA\Property(property="metadata", type="object"),
 *   @OA\Property(property="is_edited", type="boolean"),
 *   @OA\Property(property="is_deleted", type="boolean"),
 *   @OA\Property(property="is_pinned", type="boolean"),
 *   @OA\Property(property="is_announcement", type="boolean"),
 *   @OA\Property(property="is_networking", type="boolean"),
 *   @OA\Property(property="is_social", type="boolean"),
 *   @OA\Property(property="reaction_count", type="integer"),
 *   @OA\Property(property="reply_count", type="integer"),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *   schema="PaginationMeta",
 *   type="object",
 *   @OA\Property(property="current_page", type="integer", example=1),
 *   @OA\Property(property="per_page", type="integer", example=50),
 *   @OA\Property(property="total", type="integer", example=1234),
 *   @OA\Property(property="last_page", type="integer", example=25)
 * )
 * 
 * @OA\Schema(
 *   schema="PaginatedChatMessages",
 *   type="object",
 *   @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ChatMessage")),
 *   @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta")
 * )
 * 
 * @OA\Schema(
 *   schema="ModerationAction",
 *   type="object",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="action_type", type="string", example="shadow_throttle"),
 *   @OA\Property(property="reason", type="string"),
 *   @OA\Property(property="moderator", ref="#/components/schemas/User"),
 *   @OA\Property(property="target_user_id", type="integer", nullable=true),
 *   @OA\Property(property="target_artifact_id", type="integer", nullable=true),
 *   @OA\Property(property="metadata", type="object"),
 *   @OA\Property(property="created_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *   schema="ShadowThrottle",
 *   type="object",
 *   @OA\Property(property="id", type="integer"),
 *   @OA\Property(property="user_id", type="integer"),
 *   @OA\Property(property="severity", type="integer", minimum=1, maximum=5),
 *   @OA\Property(property="started_at", type="string", format="date-time"),
 *   @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="reason", type="string")
 * )
 * 
 * @OA\Schema(
 *   schema="RecommendationItem",
 *   type="object",
 *   required={"id","type","title"},
 *   @OA\Property(property="id", type="string", example="rec_123"),
 *   @OA\Property(property="type", type="string", enum={"content","collaborative","ai","location"}),
 *   @OA\Property(property="title", type="string", example="Suggested Post"),
 *   @OA\Property(property="description", type="string", nullable=true),
 *   @OA\Property(property="score", type="number", format="float", example=0.91, nullable=true),
 *   @OA\Property(property="reason", type="string", example="Based on your interests", nullable=true),
 *   @OA\Property(property="metadata", type="object", nullable=true)
 * )
 * 
 * @OA\Schema(
 *   schema="RecommendationList",
 *   type="object",
 *   @OA\Property(property="recommendations", type="array", @OA\Items(ref="#/components/schemas/RecommendationItem")),
 *   @OA\Property(property="metadata", type="object",
 *     @OA\Property(property="total", type="integer", example=10),
 *     @OA\Property(property="types", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="context", type="object"),
 *     @OA\Property(property="generated_at", type="string", format="date-time"),
 *     @OA\Property(property="cache_hit", type="boolean", example=false)
 *   )
 * )
 * 
 * @OA\Schema(
 *   schema="TrendingItem",
 *   type="object",
 *   required={"id","type","title","engagement_score"},
 *   @OA\Property(property="id", type="string", example="trending_1"),
 *   @OA\Property(property="type", type="string", example="bulletin_board"),
 *   @OA\Property(property="title", type="string"),
 *   @OA\Property(property="description", type="string", nullable=true),
 *   @OA\Property(property="engagement_score", type="number", format="float", example=0.95),
 *   @OA\Property(property="trending_since", type="string", format="date-time", nullable=true)
 * )
 * 
 * @OA\Schema(
 *   schema="TrendingList",
 *   type="object",
 *   @OA\Property(property="trending", type="array", @OA\Items(ref="#/components/schemas/TrendingItem")),
 *   @OA\Property(property="metadata", type="object",
 *     @OA\Property(property="timeframe", type="string", example="24h"),
 *     @OA\Property(property="total", type="integer", example=10),
 *     @OA\Property(property="generated_at", type="string", format="date-time")
 *   )
 * )
 * 
 * @OA\Schema(
 *   schema="FeedItem",
 *   type="object",
 *   required={"id","type"},
 *   @OA\Property(property="id", type="string", example="feed_1"),
 *   @OA\Property(property="type", type="string", example="recommendation"),
 *   @OA\Property(property="content", type="string", nullable=true),
 *   @OA\Property(property="score", type="number", format="float", nullable=true),
 *   @OA\Property(property="reason", type="string", nullable=true),
 *   @OA\Property(property="timestamp", type="string", format="date-time", nullable=true)
 * )
 * 
 * @OA\Schema(
 *   schema="FeedResponse",
 *   type="object",
 *   @OA\Property(property="feed", type="array", @OA\Items(ref="#/components/schemas/FeedItem")),
 *   @OA\Property(property="pagination", type="object",
 *     @OA\Property(property="current_page", type="integer"),
 *     @OA\Property(property="per_page", type="integer"),
 *     @OA\Property(property="total", type="integer"),
 *     @OA\Property(property="has_more", type="boolean")
 *   ),
 *   @OA\Property(property="metadata", type="object",
 *     @OA\Property(property="generated_at", type="string", format="date-time"),
 *     @OA\Property(property="user_id", type="integer")
 *   )
 * )
 * 
 * @OA\Schema(
 *   schema="WebSocketStatus",
 *   type="object",
 *   @OA\Property(property="status", type="string", example="connected"),
 *   @OA\Property(property="connection_id", type="string", example="ws_abc123", nullable=true),
 *   @OA\Property(property="timestamp", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *   schema="WebSocketConnectionEstablished",
 *   type="object",
 *   @OA\Property(property="connection_id", type="string", example="conn_abc123"),
 *   @OA\Property(property="user_id", type="integer", example=42),
 *   @OA\Property(property="timestamp", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *   schema="RateLimitStatus",
 *   type="object",
 *   @OA\Property(property="action", type="string", example="message_send"),
 *   @OA\Property(property="allowed", type="boolean"),
 *   @OA\Property(property="remaining", type="integer"),
 *   @OA\Property(property="limit", type="integer"),
 *   @OA\Property(property="reset_in_seconds", type="integer")
 * )
 * 
 * @OA\Schema(
 *   schema="RateLimitStatusResponse",
 *   type="object",
 *   @OA\Property(property="action", type="string"),
 *   @OA\Property(property="status", ref="#/components/schemas/RateLimitStatus"),
 *   @OA\Property(property="timestamp", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *   schema="AllRateLimitStatusesResponse",
 *   type="object",
 *   @OA\Property(property="user_id", type="integer"),
 *   @OA\Property(property="statuses", type="object", additionalProperties={"$ref":"#/components/schemas/RateLimitStatus"}),
 *   @OA\Property(property="timestamp", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *   schema="ContentOptimizationResult",
 *   type="object",
 *   @OA\Property(property="original", type="string"),
 *   @OA\Property(property="optimized", type="string"),
 *   @OA\Property(property="notes", type="array", @OA\Items(type="string")),
 *   @OA\Property(property="score", type="number", format="float")
 * )
 * 
 * @OA\Schema(
 *   schema="ContentOptimizationResponse",
 *   type="object",
 *   @OA\Property(property="success", type="boolean", example=true),
 *   @OA\Property(property="data", ref="#/components/schemas/ContentOptimizationResult"),
 *   @OA\Property(property="user_id", type="integer"),
 *   @OA\Property(property="optimized_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *   schema="SimpleMessageResponse",
 *   type="object",
 *   @OA\Property(property="message", type="string", example="Operation completed successfully")
 * )
 * 
 * @OA\Schema(
 *   schema="GenerationStatsResponse",
 *   type="object",
 *   @OA\Property(property="success", type="boolean", example=true),
 *   @OA\Property(property="data", type="object",
 *     @OA\Property(property="total_generations", type="integer"),
 *     @OA\Property(property="successful_generations", type="integer"),
 *     @OA\Property(property="failed_generations", type="integer"),
 *     @OA\Property(property="average_generation_time", type="number"),
 *     @OA\Property(property="most_popular_types", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="user_satisfaction", type="number"),
 *     @OA\Property(property="generated_at", type="string", format="date-time")
 *   )
 * )
 * 
 * @OA\Schema(
 *   schema="OptimizationStatsResponse",
 *   type="object",
 *   @OA\Property(property="success", type="boolean", example=true),
 *   @OA\Property(property="data", type="object",
 *     @OA\Property(property="total_optimizations", type="integer"),
 *     @OA\Property(property="successful_optimizations", type="integer"),
 *     @OA\Property(property="failed_optimizations", type="integer"),
 *     @OA\Property(property="average_improvement_score", type="number"),
 *     @OA\Property(property="most_common_improvements", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="optimization_types_usage", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="generated_at", type="string", format="date-time")
 *   )
 * )
 * 
 * @OA\Schema(
 *   schema="Chatroom",
 *   type="object",
 *   required={"id","name","type","created_by"},
 *   @OA\Property(property="id", type="integer", example=101),
 *   @OA\Property(property="name", type="string", example="NYC Tech Founders"),
 *   @OA\Property(property="description", type="string", nullable=true, example="A place for NYC tech founders to connect"),
 *   @OA\Property(property="type", type="string", example="interest", enum={"interest","city","event","private"}),
 *   @OA\Property(property="category", type="string", nullable=true, example="technology"),
 *   @OA\Property(property="city", type="string", nullable=true, example="New York"),
 *   @OA\Property(property="neighborhood", type="string", nullable=true, example="Brooklyn"),
 *   @OA\Property(property="created_by", type="integer", example=5),
 *   @OA\Property(property="member_count", type="integer", example=245),
 *   @OA\Property(property="message_count", type="integer", example=1387),
 *   @OA\Property(property="is_public", type="boolean", example=true),
 *   @OA\Property(property="is_active", type="boolean", example=true),
 *   @OA\Property(property="last_activity_at", type="string", format="date-time"),
 *   @OA\Property(property="settings", type="object", nullable=true),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *   schema="Photo",
 *   type="object",
 *   required={"id","filename","url"},
 *   @OA\Property(property="id", type="integer", example=42),
 *   @OA\Property(property="filename", type="string", example="photo_abc123.jpg"),
 *   @OA\Property(property="original_filename", type="string", nullable=true, example="my_photo.jpg"),
 *   @OA\Property(property="url", type="string", example="https://cdn.fwber.com/photos/abc123.jpg"),
 *   @OA\Property(property="thumbnail_url", type="string", nullable=true, example="https://cdn.fwber.com/photos/thumb_abc123.jpg"),
 *   @OA\Property(property="mime_type", type="string", example="image/jpeg"),
 *   @OA\Property(property="file_size", type="integer", example=245678),
 *   @OA\Property(property="width", type="integer", nullable=true, example=1920),
 *   @OA\Property(property="height", type="integer", nullable=true, example=1080),
 *   @OA\Property(property="is_primary", type="boolean", example=false),
 *   @OA\Property(property="is_private", type="boolean", example=false),
 *   @OA\Property(property="sort_order", type="integer", example=0),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *   schema="Group",
 *   type="object",
 *   required={"id","name","created_by"},
 *   @OA\Property(property="id", type="integer", example=15),
 *   @OA\Property(property="name", type="string", example="Running Club"),
 *   @OA\Property(property="description", type="string", nullable=true, example="Weekly running meetups"),
 *   @OA\Property(property="created_by", type="integer", example=8),
 *   @OA\Property(property="member_count", type="integer", example=32),
 *   @OA\Property(property="is_public", type="boolean", example=true),
 *   @OA\Property(property="is_active", type="boolean", example=true),
 *   @OA\Property(property="settings", type="object", nullable=true),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *   schema="ProximityArtifact",
 *   type="object",
 *   required={"id","user_id","artifact_type","content"},
 *   @OA\Property(property="id", type="integer", example=78),
 *   @OA\Property(property="user_id", type="integer", example=12),
 *   @OA\Property(property="artifact_type", type="string", example="status"),
 *   @OA\Property(property="content", type="string", example="Looking for coffee recommendations nearby"),
 *   @OA\Property(property="latitude", type="number", format="float", example=40.7128),
 *   @OA\Property(property="longitude", type="number", format="float", example=-74.0060),
 *   @OA\Property(property="radius_meters", type="integer", example=500),
 *   @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="visibility", type="string", example="public"),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *   schema="DirectMessage",
 *   type="object",
 *   required={"id","sender_id","recipient_id","content"},
 *   @OA\Property(property="id", type="integer", example=456),
 *   @OA\Property(property="sender_id", type="integer", example=10),
 *   @OA\Property(property="recipient_id", type="integer", example=20),
 *   @OA\Property(property="content", type="string", example="Hey, how are you?"),
 *   @OA\Property(property="message_type", type="string", example="text"),
 *   @OA\Property(property="is_read", type="boolean", example=false),
 *   @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
 *   @OA\Property(property="metadata", type="object", nullable=true),
 *   @OA\Property(property="created_at", type="string", format="date-time"),
 *   @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class Schemas
{
    // This class only exists to hold OpenAPI schema definitions
}
