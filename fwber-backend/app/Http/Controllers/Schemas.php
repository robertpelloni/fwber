<?php

namespace App\Http\Controllers;

/**
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
 */
class Schemas
{
    // This class only exists to hold OpenAPI schema definitions
}
