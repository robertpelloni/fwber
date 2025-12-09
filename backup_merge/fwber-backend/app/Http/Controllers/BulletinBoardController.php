<?php

namespace App\Http\Controllers;

use App\Models\BulletinBoard;
use App\Models\BulletinMessage;
use App\Models\User;
use App\Services\MercurePublisher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
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
     *
     * @OA\Get(
     *   path="/bulletin-boards",
     *   tags={"Bulletin Boards"},
     *   summary="List nearby bulletin boards",
     *   @OA\Parameter(name="lat", in="query", required=true, @OA\Schema(type="number", format="float", minimum=-90, maximum=90)),
     *   @OA\Parameter(name="lng", in="query", required=true, @OA\Schema(type="number", format="float", minimum=-180, maximum=180)),
     *   @OA\Parameter(name="radius", in="query", required=false, @OA\Schema(type="integer", minimum=100, maximum=50000), description="Meters (default 5000)"),
     *   @OA\Response(response=200, description="Nearby boards",
     *     @OA\JsonContent(type="object",
     *       @OA\Property(property="boards", type="array", @OA\Items(type="object")),
     *       @OA\Property(property="user_location", type="object",
     *         @OA\Property(property="lat", type="number"),
     *         @OA\Property(property="lng", type="number")
     *       ),
     *       @OA\Property(property="search_radius", type="integer")
     *     )
     *   ),
     *   @OA\Response(response=400, description="Validation error")
     * )
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

        // Use MySQL spatial query for proximity search
        $boards = DB::table('bulletin_boards')
            ->where('is_active', true)
            ->whereRaw('ST_Distance_Sphere(location, POINT(?, ?)) <= ?', [$lng, $lat, $radius])
            ->orderByRaw('ST_Distance_Sphere(location, POINT(?, ?))', [$lng, $lat])
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
     *
     * @OA\Get(
     *   path="/bulletin-boards/{id}",
     *   tags={"Bulletin Boards"},
     *   summary="Get bulletin board by ID",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="lat", in="query", required=false, @OA\Schema(type="number")),
     *   @OA\Parameter(name="lng", in="query", required=false, @OA\Schema(type="number")),
     *   @OA\Response(response=200, description="Board and messages",
     *     @OA\JsonContent(type="object",
     *       @OA\Property(property="board", type="object"),
     *       @OA\Property(property="messages", type="array", @OA\Items(type="object"))
     *     )
     *   ),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
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
     *
     * @OA\Post(
     *   path="/bulletin-boards",
     *   tags={"Bulletin Boards"},
     *   summary="Create or find a board for a location",
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"lat","lng"},
     *     @OA\Property(property="lat", type="number", format="float"),
     *     @OA\Property(property="lng", type="number", format="float"),
     *     @OA\Property(property="radius", type="integer", minimum=100, maximum=5000)
     *   )),
     *   @OA\Response(response=200, description="Found/created",
     *     @OA\JsonContent(type="object",
     *       @OA\Property(property="board", type="object"),
     *       @OA\Property(property="created", type="boolean")
     *     )
     *   ),
     *   @OA\Response(response=400, description="Validation error")
     * )
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
        
        // Update MySQL spatial location if it's null
        if (!$board->location) {
            DB::statement(
                'UPDATE bulletin_boards SET location = POINT(?, ?) WHERE id = ?',
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
     *
     * @OA\Post(
     *   path="/bulletin-boards/{boardId}/messages",
     *   tags={"Bulletin Boards"},
     *   summary="Post a message",
    *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="boardId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"content","lat","lng"},
     *     @OA\Property(property="content", type="string", maxLength=1000),
     *     @OA\Property(property="is_anonymous", type="boolean"),
     *     @OA\Property(property="expires_in_hours", type="integer", minimum=1, maximum=168),
     *     @OA\Property(property="lat", type="number"),
     *     @OA\Property(property="lng", type="number")
     *   )),
     *   @OA\Response(response=201, description="Created",
     *     @OA\JsonContent(type="object",
     *       @OA\Property(property="message", type="object"),
     *       @OA\Property(property="board", type="object")
     *     )
     *   ),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=400, description="Validation error"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
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
        
        // Set MySQL spatial location for the message
        DB::statement(
            'UPDATE bulletin_messages SET location = POINT(?, ?) WHERE id = ?',
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
     *
     * @OA\Get(
     *   path="/bulletin-boards/{boardId}/messages",
     *   tags={"Bulletin Boards"},
     *   summary="List messages for a board",
     *   @OA\Parameter(name="boardId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=100)),
     *   @OA\Parameter(name="since", in="query", required=false, @OA\Schema(type="string", format="date-time")),
     *   @OA\Response(response=200, description="Messages",
     *     @OA\JsonContent(type="object",
     *       @OA\Property(property="messages", type="object"),
     *       @OA\Property(property="board", type="object")
     *     )
     *   ),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
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
