<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchChatroomRequest;
use App\Http\Requests\StoreChatroomRequest;
use App\Http\Requests\UpdateChatroomRequest;
use App\Models\Chatroom;
use App\Models\ChatroomMessage;
use App\Models\User;
use App\Services\ContentModerationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ChatroomController extends Controller
{
    protected $contentModeration;

    public function __construct(ContentModerationService $contentModeration)
    {
        $this->contentModeration = $contentModeration;
    }

    /**
     * Get all available chatrooms with filtering
     *
     * @OA\Get(
     *   path="/chatrooms",
     *   tags={"Chatrooms"},
     *   summary="List chatrooms",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string", enum={"interest","city","event","private"})),
     *   @OA\Parameter(name="category", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="city", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="sort", in="query", required=false, @OA\Schema(type="string", enum={"activity","newest","most_active","most_members"})),
    *   @OA\Response(response=200, description="Paginated chatrooms list",
    *     @OA\JsonContent(type="object",
    *       @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Chatroom")),
    *       @OA\Property(property="current_page", type="integer"),
    *       @OA\Property(property="last_page", type="integer"),
    *       @OA\Property(property="per_page", type="integer"),
    *       @OA\Property(property="total", type="integer")
    *     )
    *   )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Chatroom::active()->public();

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by city
        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Sort by activity
        $sortBy = $request->get('sort', 'activity');
        switch ($sortBy) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'most_active':
                $query->orderBy('last_activity_at', 'desc');
                break;
            case 'most_members':
                $query->orderBy('member_count', 'desc');
                break;
            default:
                $query->orderBy('last_activity_at', 'desc');
        }

        $chatrooms = $query->with(['creator', 'recentMessages.user'])
            ->paginate(20);

        return response()->json($chatrooms);
    }

    /**
     * Get a specific chatroom with messages
     *
     * @OA\Get(
     *   path="/chatrooms/{id}",
     *   tags={"Chatrooms"},
     *   summary="Get a chatroom",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    *   @OA\Response(response=200, description="Chatroom and recent messages",
    *     @OA\JsonContent(type="object",
    *       @OA\Property(property="chatroom", ref="#/components/schemas/Chatroom"),
    *       @OA\Property(property="messages", ref="#/components/schemas/PaginatedChatMessages")
    *     )
    *   ),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
    *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $chatroom = Chatroom::with(['creator', 'members'])
            ->findOrFail($id);

        // Check if user is a member
        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        // Get recent messages
        $messages = $chatroom->recentMessages()
            ->with(['user', 'reactions', 'mentions.mentionedUser'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json([
            'chatroom' => $chatroom,
            'messages' => $messages,
        ]);
    }

    /**
     * Create a new chatroom
     *
     * @OA\Post(
     *   path="/chatrooms",
     *   tags={"Chatrooms"},
     *   summary="Create chatroom",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(required=true, @OA\JsonContent(
     *     required={"name","type"},
     *     @OA\Property(property="name", type="string", maxLength=100),
     *     @OA\Property(property="description", type="string", maxLength=500),
     *     @OA\Property(property="type", type="string", enum={"interest","city","event","private"}),
     *     @OA\Property(property="category", type="string"),
     *     @OA\Property(property="city", type="string"),
     *     @OA\Property(property="neighborhood", type="string"),
     *     @OA\Property(property="is_public", type="boolean"),
     *     @OA\Property(property="settings", type="object")
     *   )),
    *   @OA\Response(response=201, description="Created", @OA\JsonContent(ref="#/components/schemas/Chatroom"))
     * )
     */
    public function store(StoreChatroomRequest $request): JsonResponse
    {
        $chatroom = Chatroom::create([
            'name' => $request->name,
            'description' => $request->description,
            'type' => $request->type,
            'category' => $request->category,
            'city' => $request->city,
            'neighborhood' => $request->neighborhood,
            'created_by' => Auth::id(),
            'is_public' => $request->get('is_public', true),
            'is_active' => true,
            'member_count' => 1,
            'message_count' => 0,
            'last_activity_at' => now(),
            'settings' => $request->settings ?? [],
        ]);

        // Add creator as admin member
        $chatroom->addMember(Auth::user(), 'admin');

        Log::info('Chatroom created', [
            'chatroom_id' => $chatroom->id,
            'created_by' => Auth::id(),
            'type' => $chatroom->type,
        ]);

        return response()->json($chatroom->load('creator'), 201);
    }

    /**
     * Join a chatroom
     *
     * @OA\Post(
     *   path="/chatrooms/{id}/join",
     *   tags={"Chatrooms"},
     *   summary="Join",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    *   @OA\Response(response=200, description="Joined", @OA\JsonContent(ref="#/components/schemas/SimpleMessageResponse")),
     *   @OA\Response(response=400, description="Already member"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function join(Request $request, int $id): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($id);

        // Check if already a member
        if ($chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are already a member of this chatroom'], 400);
        }

        // Check if user is banned
        if ($chatroom->isBanned(Auth::user())) {
            return response()->json(['message' => 'You are banned from this chatroom'], 403);
        }

        // Add user as member
        $chatroom->addMember(Auth::user(), 'member');

        Log::info('User joined chatroom', [
            'chatroom_id' => $chatroom->id,
            'user_id' => Auth::id(),
        ]);

        return response()->json(['message' => 'Successfully joined chatroom']);
    }

    /**
     * Leave a chatroom
     *
     * @OA\Post(
     *   path="/chatrooms/{id}/leave",
     *   tags={"Chatrooms"},
     *   summary="Leave",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    *   @OA\Response(response=200, description="Left", @OA\JsonContent(ref="#/components/schemas/SimpleMessageResponse")),
     *   @OA\Response(response=400, description="Not a member")
     * )
     */
    public function leave(Request $request, int $id): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($id);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 400);
        }

        $chatroom->removeMember(Auth::user());

        Log::info('User left chatroom', [
            'chatroom_id' => $chatroom->id,
            'user_id' => Auth::id(),
        ]);

        return response()->json(['message' => 'Successfully left chatroom']);
    }

    /**
     * Get chatroom members
     *
     * @OA\Get(
     *   path="/chatrooms/{id}/members",
     *   tags={"Chatrooms"},
     *   summary="Members",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Paginated members"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function members(Request $request, int $id): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($id);

        if (!$chatroom->hasMember(Auth::user())) {
            return response()->json(['message' => 'You are not a member of this chatroom'], 403);
        }

        $members = $chatroom->activeMembers()
            ->with(['user'])
            ->orderBy('pivot_joined_at', 'desc')
            ->paginate(50);

        return response()->json($members);
    }

    /**
     * Update chatroom settings (admin/moderator only)
     *
     * @OA\Put(
     *   path="/chatrooms/{id}",
     *   tags={"Chatrooms"},
     *   summary="Update",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(@OA\JsonContent(
     *     @OA\Property(property="name", type="string", maxLength=100),
     *     @OA\Property(property="description", type="string", maxLength=500),
     *     @OA\Property(property="is_public", type="boolean"),
     *     @OA\Property(property="settings", type="object")
     *   )),
    *   @OA\Response(response=200, description="Updated chatroom", @OA\JsonContent(ref="#/components/schemas/Chatroom")),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function update(UpdateChatroomRequest $request, int $id): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($id);

        if (!$chatroom->hasModerator(Auth::user())) {
            return response()->json(['message' => 'You do not have permission to update this chatroom'], 403);
        }

        $chatroom->update($request->only(['name', 'description', 'is_public', 'settings']));

        return response()->json($chatroom);
    }

    /**
     * Delete chatroom (creator only)
     *
     * @OA\Delete(
     *   path="/chatrooms/{id}",
     *   tags={"Chatrooms"},
     *   summary="Delete",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
    *   @OA\Response(response=200, description="Deleted", @OA\JsonContent(ref="#/components/schemas/SimpleMessageResponse")),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($id);

        if ($chatroom->created_by !== Auth::id()) {
            return response()->json(['message' => 'You can only delete chatrooms you created'], 403);
        }

        $chatroom->update(['is_active' => false]);

        Log::info('Chatroom deactivated', [
            'chatroom_id' => $chatroom->id,
            'deactivated_by' => Auth::id(),
        ]);

        return response()->json(['message' => 'Chatroom deleted successfully']);
    }

    /**
     * Get user's chatrooms
     *
     * @OA\Get(
     *   path="/chatrooms/my",
     *   tags={"Chatrooms"},
     *   summary="My chatrooms",
     *   security={{"bearerAuth":{}}},
    *   @OA\Response(response=200, description="List",
    *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Chatroom"))
    *   )
     * )
     */
    public function myChatrooms(): JsonResponse
    {
        $chatrooms = Auth::user()
            ->chatrooms()
            ->active()
            ->with(['creator', 'recentMessages.user'])
            ->orderBy('last_activity_at', 'desc')
            ->get();

        return response()->json($chatrooms);
    }

    /**
     * Get chatroom categories
     *
     * @OA\Get(
     *   path="/chatrooms/categories",
     *   tags={"Chatrooms"},
     *   summary="Categories",
     *   security={{"bearerAuth":{}}},
    *   @OA\Response(response=200, description="List",
    *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Chatroom"))
    *   )
     * )
     */
    public function categories(): JsonResponse
    {
        $categories = Cache::remember('chatroom_categories', 3600, function () {
            return Chatroom::active()
                ->select('category')
                ->whereNotNull('category')
                ->distinct()
                ->pluck('category')
                ->sort()
                ->values();
        });

        return response()->json($categories);
    }

    /**
     * Get popular chatrooms
     *
     * @OA\Get(
     *   path="/chatrooms/popular",
     *   tags={"Chatrooms"},
     *   summary="Popular",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List")
     * )
     */
    public function popular(): JsonResponse
    {
        $popular = Chatroom::active()
            ->public()
            ->where('member_count', '>', 5)
            ->orderBy('member_count', 'desc')
            ->orderBy('last_activity_at', 'desc')
            ->limit(10)
            ->with(['creator'])
            ->get();

        return response()->json($popular);
    }

    /**
     * Search chatrooms
     *
     * @OA\Get(
     *   path="/chatrooms/search",
     *   tags={"Chatrooms"},
     *   summary="Search",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="q", in="query", required=true, @OA\Schema(type="string", minLength=2)),
     *   @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string", enum={"interest","city","event","private"})),
     *   @OA\Parameter(name="category", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Parameter(name="city", in="query", required=false, @OA\Schema(type="string")),
     *   @OA\Response(response=200, description="Paginated results")
     * )
     */
    public function search(SearchChatroomRequest $request): JsonResponse
    {
        $query = Chatroom::active()->public();

        // Search in name and description
        $searchTerm = $request->q;
        $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'like', "%{$searchTerm}%")
              ->orWhere('description', 'like', "%{$searchTerm}%");
        });

        // Apply filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        $results = $query->orderBy('member_count', 'desc')
            ->orderBy('last_activity_at', 'desc')
            ->with(['creator'])
            ->paginate(20);

        return response()->json($results);
    }
}
