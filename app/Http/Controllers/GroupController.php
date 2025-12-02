<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGroupRequest;
use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class GroupController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/groups",
     *     summary="List public and visible groups",
     *     tags={"Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of groups with member counts",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Group")
     *         )
     *     )
     * )
     */
    public function index()
    {
        // Cache for 10 minutes with tagged caching
        $cacheKey = config('optimization.cache_version') . ':groups:index:public';
        $groups = Cache::tags(['groups'])->remember($cacheKey, 600, function () {
            return Group::where('privacy', 'public')
                ->orWhere('visibility', 'visible')
                ->withCount('members')
                ->get();
        });
        
        return response()->json($groups);
    }

    /**
     * @OA\Post(
     *     path="/api/groups",
     *     summary="Create a new group",
     *     tags={"Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "privacy"},
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="description", type="string", nullable=true),
     *             @OA\Property(property="icon", type="string", nullable=true),
     *             @OA\Property(property="privacy", type="string", enum={"public", "private"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Group created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Group")
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreGroupRequest $request)
    {
        $validated = $request->validated();

        $group = Group::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'icon' => $validated['icon'],
            'privacy' => $validated['privacy'],
            'created_by_user_id' => Auth::id(),
            'creator_id' => Auth::id(), // For compatibility
            'member_count' => 1,
            'visibility' => 'visible', // Default to visible
            'is_active' => true,
        ]);

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => Auth::id(),
            'role' => 'admin',
            'joined_at' => now(),
        ]);

        // Invalidate groups cache
        Cache::tags(['groups'])->flush();

        return response()->json($group, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/groups/{id}",
     *     summary="Get group details",
     *     tags={"Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Group details with members and posts",
     *         @OA\JsonContent(ref="#/components/schemas/Group")
     *     ),
     *     @OA\Response(response=404, description="Group not found")
     * )
     */
    public function show($id)
    {
        $group = Group::with(['members.user', 'posts.user'])->findOrFail($id);
        return response()->json($group);
    }

    /**
     * @OA\Post(
     *     path="/api/groups/{id}/join",
     *     summary="Join a group",
     *     tags={"Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Joined group successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="group", ref="#/components/schemas/Group")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Already a member"),
     *     @OA\Response(response=404, description="Group not found")
     * )
     */
    public function join($id)
    {
        $group = Group::findOrFail($id);
        $user = Auth::user();

        if ($group->hasMember($user->id)) {
            return response()->json(['message' => 'Already a member'], 400);
        }

        GroupMember::create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $group->increment('member_count');

        // Invalidate groups cache (member count changed)
        Cache::tags(['groups'])->flush();

        return response()->json(['message' => 'Joined group successfully']);
    }

    /**
     * @OA\Post(
     *     path="/api/groups/{id}/leave",
     *     summary="Leave a group",
     *     tags={"Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Left group successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Not a member"),
     *     @OA\Response(response=404, description="Group not found")
     * )
     */
    public function leave($id)
    {
        $group = Group::findOrFail($id);
        $user = Auth::user();

        $member = GroupMember::where('group_id', $group->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$member) {
            return response()->json(['message' => 'Not a member'], 400);
        }

        $member->delete(); // Or set is_active = false
        $group->decrement('member_count');

        // Invalidate groups cache (member count changed)
        Cache::tags(['groups'])->flush();

        return response()->json(['message' => 'Left group successfully']);
    }

    /**
     * @OA\Get(
     *     path="/api/groups/my",
     *     summary="Get user's joined groups",
     *     tags={"Groups"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of user's groups",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Group")
     *         )
     *     )
     * )
     */
    public function myGroups()
    {
        $user = Auth::user();
        $groups = Group::whereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        return response()->json($groups);
    }
}
