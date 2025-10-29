<?php

namespace App\Http\Controllers;

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
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:interest,city,event,private',
            'category' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'neighborhood' => 'nullable|string|max:100',
            'is_public' => 'boolean',
            'settings' => 'nullable|array',
        ]);

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
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $chatroom = Chatroom::findOrFail($id);

        if (!$chatroom->hasModerator(Auth::user())) {
            return response()->json(['message' => 'You do not have permission to update this chatroom'], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_public' => 'boolean',
            'settings' => 'nullable|array',
        ]);

        $chatroom->update($request->only(['name', 'description', 'is_public', 'settings']));

        return response()->json($chatroom);
    }

    /**
     * Delete chatroom (creator only)
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
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'type' => 'nullable|in:interest,city,event,private',
            'category' => 'nullable|string',
            'city' => 'nullable|string',
        ]);

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
