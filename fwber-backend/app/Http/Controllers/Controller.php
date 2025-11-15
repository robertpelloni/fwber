<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="FWBer API Documentation",
 *     description="Comprehensive API documentation for FWBer - A proximity-based dating platform with real-time features, AI-powered matching, and privacy-first design.",
 *     @OA\Contact(
 *         name="FWBer Team",
 *         email="support@fwber.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8000/api",
 *     description="Local Development Server"
 * )
 * 
 * @OA\Server(
 *     url="https://api.fwber.com/api",
 *     description="Production Server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your bearer token in the format: Bearer {token}"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and session management"
 * )
 * 
 * @OA\Tag(
 *     name="Profile",
 *     description="User profile management and customization"
 * )
 * 
 * @OA\Tag(
 *     name="Dashboard",
 *     description="User dashboard statistics and activity feeds"
 * )
 * 
 * @OA\Tag(
 *     name="Matches",
 *     description="Match discovery and compatibility scoring"
 * )
 * 
 * @OA\Tag(
 *     name="Messages",
 *     description="Direct messaging between matched users"
 * )
 * 
 * @OA\Tag(
 *     name="Physical Profile",
 *     description="AI avatar generation and physical attributes"
 * )
 * 
 * @OA\Tag(
 *     name="Profile Views",
 *     description="Track and manage profile view analytics"
 * )
 * 
 * @OA\Tag(
 *     name="Groups",
 *     description="Community groups and group messaging"
 * )
 * 
 * @OA\Tag(
 *     name="Chatrooms",
 *     description="Real-time proximity-based chatrooms"
 * )
 * 
 * @OA\Tag(
 *     name="Bulletin Boards",
 *     description="Location-based community bulletin boards"
 * )
 * 
 * @OA\Tag(
 *     name="Health",
 *     description="System health and monitoring endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Analytics",
 *     description="Platform analytics, trends, and real-time metrics"
 * )
 * 
 * @OA\Tag(
 *     name="Moderation",
 *     description="Moderator tools for reviewing content and enforcing policy"
 * )
 * 
 * @OA\Tag(
 *     name="Photos",
 *     description="Photo upload and management"
 * )
 * 
 * @OA\Tag(
 *     name="Proximity Artifacts",
 *     description="Ephemeral location-based content (Local Pulse)"
 * )
 * 
 * @OA\Tag(
 *     name="Relationship Tiers",
 *     description="Progressive photo unlock system for matches"
 * )
 * 
 * @OA\Tag(
 *     name="Safety",
 *     description="User blocking and reporting"
 * )
 * 
 * @OA\Tag(
 *     name="Recommendations",
 *     description="AI-powered personalized recommendations"
 * )
 * 
 * @OA\Tag(
 *     name="WebSocket",
 *     description="Real-time bidirectional communication"
 * )
 * 
 * @OA\Tag(
 *     name="Mercure",
 *     description="Server-Sent Events (SSE) for real-time updates"
 * )
 * 
 * @OA\Tag(
 *     name="Content Generation",
 *     description="AI-powered content creation and optimization"
 * )
 * 
 * @OA\Tag(
 *     name="Rate Limiting",
 *     description="Rate limit management and monitoring"
 * )
 */
abstract class Controller
{
    //
}
