<?php

namespace App\Http\Controllers;

use App\Models\BulletinBoard;
use App\Models\BulletinMessage;
use App\Models\User;
use App\Services\MercurePublisher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;

class BulletinBoardController extends Controller
{
    protected MercurePublisher $mercurePublisher;

    public function __construct(MercurePublisher $mercurePublisher)
    {
        $this->mercurePublisher = $mercurePublisher;
    }
    /**
     * Get bulletin boards near a location
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'integer|min:100|max:50000', // 100m to 50km
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $radius = $request->input('radius', 5000); // Default 5km

        // Use PostGIS spatial query for accurate proximity search
        $point = DB::raw('ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography');
        
        $boards = DB::table('bulletin_boards')
            ->where('is_active', true)
            ->whereRaw('ST_DWithin(location, ?, ?)', [$point, $lng, $lat, $radius])
            ->orderByRaw('ST_Distance(location, ?)', [$point, $lng, $lat])
            ->orderBy('last_activity_at', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'boards' => $boards,
            'user_location' => ['lat' => $lat, 'lng' => $lng],
            'search_radius' => $radius,
        ]);
    }

    /**
     * Get a specific bulletin board with messages
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $board = BulletinBoard::with(['recentMessages.user'])
            ->findOrFail($id);

        // Check if user is within board radius
        if ($request->has('lat') && $request->has('lng')) {
            $lat = $request->input('lat');
            $lng = $request->input('lng');
            
            if (!$board->containsPoint($lat, $lng)) {
                return response()->json([
                    'error' => 'You are not within the bulletin board area'
                ], 403);
            }
        }

        return response()->json([
            'board' => $board,
            'messages' => $board->recentMessages,
        ]);
    }

    /**
     * Create or find a bulletin board for a location
     */
    public function createOrFind(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'integer|min:100|max:5000', // 100m to 5km
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $lat = $request->input('lat');
        $lng = $request->input('lng');
        $radius = $request->input('radius', 1000); // Default 1km

        // Generate geohash for this location
        $geohash = $this->generateGeohash($lat, $lng, 6); // 6-character precision

        // Try to find existing board or create new one
        $board = BulletinBoard::firstOrCreate(
            ['geohash' => $geohash],
            [
                'center_lat' => $lat,
                'center_lng' => $lng,
                'radius_meters' => $radius,
                'name' => "Board at {$geohash}",
                'description' => "Local bulletin board for this area",
                'is_active' => true,
            ]
        );
        
        // Update PostGIS location if it's null
        if (!$board->location) {
            DB::statement(
                'UPDATE bulletin_boards SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
                [$lng, $lat, $board->id]
            );
        }

        return response()->json([
            'board' => $board,
            'created' => $board->wasRecentlyCreated,
        ]);
    }

    /**
     * Post a message to a bulletin board
     */
    public function postMessage(Request $request, int $boardId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
            'is_anonymous' => 'boolean',
            'expires_in_hours' => 'integer|min:1|max:168', // 1 hour to 1 week
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $board = BulletinBoard::findOrFail($boardId);
        $user = $request->user();

        // Check if user is within board radius
        $lat = $request->input('lat');
        $lng = $request->input('lng');
        
        if (!$board->containsPoint($lat, $lng)) {
            return response()->json([
                'error' => 'You are not within the bulletin board area'
            ], 403);
        }

        // Create the message
        $message = BulletinMessage::create([
            'bulletin_board_id' => $boardId,
            'user_id' => $user->id,
            'content' => $request->input('content'),
            'is_anonymous' => $request->input('is_anonymous', false),
            'expires_at' => $request->has('expires_in_hours') 
                ? now()->addHours($request->input('expires_in_hours'))
                : now()->addDays(7), // Default 7 days
        ]);
        
        // Set PostGIS location for the message
        DB::statement(
            'UPDATE bulletin_messages SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
            [$lng, $lat, $message->id]
        );

        // Publish to Redis for real-time updates
        $this->publishMessage($board, $message);

        return response()->json([
            'message' => $message->load('user'),
            'board' => $board,
        ], 201);
    }

    /**
     * Get messages for a bulletin board (with pagination)
     */
    public function getMessages(Request $request, int $boardId): JsonResponse
    {
        $board = BulletinBoard::findOrFail($boardId);
        
        $perPage = min($request->input('per_page', 20), 100);
        $since = $request->input('since'); // For SSE resume

        $query = $board->messages()
            ->notExpired()
            ->notModerated()
            ->with('user')
            ->orderBy('created_at', 'desc');

        if ($since) {
            $query->where('created_at', '>', $since);
        }

        $messages = $query->paginate($perPage);

        return response()->json([
            'messages' => $messages,
            'board' => $board,
        ]);
    }

    /**
     * Server-Sent Events endpoint for real-time message updates
     */
    public function stream(Request $request, int $boardId)
    {
        $board = BulletinBoard::findOrFail($boardId);
        
        // Set SSE headers
        $response = response()->stream(function () use ($board, $request) {
            $lastEventId = $request->header('Last-Event-ID');
            $redis = Redis::connection();
            
            // Subscribe to Redis channel for this board
            $channel = "bulletin_board:{$boardId}";
            
            // Send initial connection message
            echo "data: " . json_encode([
                'type' => 'connected',
                'board_id' => $boardId,
                'timestamp' => now()->toISOString(),
            ]) . "\n\n";
            
            // Send recent messages if resuming
            if ($lastEventId) {
                $recentMessages = $board->messages()
                    ->where('created_at', '>', $lastEventId)
                    ->notExpired()
                    ->notModerated()
                    ->with('user')
                    ->orderBy('created_at', 'asc')
                    ->get();
                
                foreach ($recentMessages as $message) {
                    echo "data: " . json_encode([
                        'type' => 'message',
                        'data' => $message,
                        'id' => $message->created_at->timestamp,
                    ]) . "\n\n";
                }
            }
            
            // Listen for new messages
            $redis->subscribe([$channel], function ($message) {
                echo "data: " . $message . "\n\n";
                ob_flush();
                flush();
            });
            
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Headers' => 'Cache-Control',
        ]);

        return $response;
    }

    /**
     * Generate geohash for a location
     */
    private function generateGeohash(float $lat, float $lng, int $precision = 6): string
    {
        // Simple geohash implementation
        // In production, use a proper geohash library
        $base32 = '0123456789bcdefghjkmnpqrstuvwxyz';
        
        $latRange = [-90.0, 90.0];
        $lngRange = [-180.0, 180.0];
        
        $geohash = '';
        $isEven = true;
        $bit = 0;
        $ch = 0;
        
        while (strlen($geohash) < $precision) {
            if ($isEven) {
                $mid = ($lngRange[0] + $lngRange[1]) / 2;
                if ($lng >= $mid) {
                    $ch |= (1 << (4 - $bit));
                    $lngRange[0] = $mid;
                } else {
                    $lngRange[1] = $mid;
                }
            } else {
                $mid = ($latRange[0] + $latRange[1]) / 2;
                if ($lat >= $mid) {
                    $ch |= (1 << (4 - $bit));
                    $latRange[0] = $mid;
                } else {
                    $latRange[1] = $mid;
                }
            }
            
            $isEven = !$isEven;
            
            if ($bit < 4) {
                $bit++;
            } else {
                $geohash .= $base32[$ch];
                $bit = 0;
                $ch = 0;
            }
        }
        
        return $geohash;
    }

    /**
     * Publish message to Redis for real-time updates
     */
    private function publishMessage(BulletinBoard $board, BulletinMessage $message): void
    {
        try {
            // Publish to Mercure for real-time updates
            $topic = "https://fwber.me/bulletin-boards/{$board->id}";
            
            $data = [
                'type' => 'new_message',
                'data' => $message->load('user'),
                'board_id' => $board->id,
                'timestamp' => now()->toISOString(),
            ];
            
            $this->mercurePublisher->publish($topic, $data, true);
            
            // Also publish to public topic for discovery
            $publicTopic = "https://fwber.me/public/bulletin-boards";
            $this->mercurePublisher->publish($publicTopic, [
                'type' => 'board_activity',
                'board_id' => $board->id,
                'message_count' => $board->message_count + 1,
                'timestamp' => now()->toISOString(),
            ], false);
            
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to publish message to Mercure', [
                'board_id' => $board->id,
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
