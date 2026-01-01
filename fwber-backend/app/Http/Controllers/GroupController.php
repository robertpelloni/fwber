<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\DiscoverGroupsRequest;
use App\Http\Requests\Api\MuteGroupMemberRequest;
use App\Http\Requests\Api\SetGroupRoleRequest;
use App\Http\Requests\Api\StoreGroupRequest;
use App\Http\Requests\Api\TransferGroupOwnershipRequest;
use App\Http\Requests\Api\UpdateGroupRequest;
use App\Models\Group;
use App\Services\GroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

use App\Services\GroupMatchingService;

class GroupController extends Controller
{
    protected GroupService $groupService;
    protected GroupMatchingService $matchingService;

    public function __construct(GroupService $groupService, GroupMatchingService $matchingService)
    {
        $this->groupService = $groupService;
        $this->matchingService = $matchingService;
    }

    /**
     * Get matching groups suggestions
     *
     * @OA\Get(
     *   path="/groups/{groupId}/matches",
     *   tags={"Groups"},
     *   summary="Get matching groups for a group (owner/admin)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="radius", in="query", required=false, @OA\Schema(type="integer", default=50)),
     *   @OA\Response(response=200, description="List of matching groups"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function matches(int $groupId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $userId = Auth::id();

        // Ensure user is admin or owner
        $member = $group->activeMembers()->where('user_id', $userId)->first();
        if (!$member || !$member->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $radius = request()->input('radius', 50);
        $matches = $this->matchingService->findMatches($group, $radius);

        // Calculate scores for each match
        $scoredMatches = $matches->map(function ($match) use ($group) {
            $match->match_score = $this->matchingService->calculateCompatibilityScore($group, $match);
            return $match;
        })->sortByDesc('match_score')->values();

        return response()->json(['matches' => $scoredMatches]);
    }

    /**
     * Request a match with another group
     *
     * @OA\Post(
     *   path="/groups/{groupId}/matches/{targetGroupId}/connect",
     *   tags={"Groups"},
     *   summary="Request a match with another group",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="targetGroupId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=201, description="Match requested"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=400, description="Invalid request (e.g. already matched)")
     * )
     */
    public function requestMatch(int $groupId, int $targetGroupId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $targetGroup = Group::findOrFail($targetGroupId);
        $user = Auth::user();

        try {
            $match = $this->matchingService->requestMatch($group, $targetGroup, $user);
            return response()->json(['message' => 'Match request sent', 'match' => $match], 201);
        } catch (\Exception $e) {
             return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get pending match requests for a group (Incoming and Outgoing)
     *
     * @OA\Get(
     *   path="/groups/{groupId}/matches/requests",
     *   tags={"Groups"},
     *   summary="Get pending match requests",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="List of requests"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function matchRequests(int $groupId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $user = Auth::user();

        // Check admin permission
        $member = $group->activeMembers()->where('user_id', $user->id)->first();
        if (!$member || !$member->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $allPending = $this->matchingService->getPendingRequests($group);

        // Separate into Incoming vs Outgoing
        $incoming = [];
        $outgoing = [];

        foreach ($allPending as $match) {
            // If initiator is a member of THIS group, it's outgoing.
            // Note: $match->initiated_by_user_id
            $isInitiatedByThisGroup = $group->members()->where('user_id', $match->initiated_by_user_id)->exists();
            
            // Format the "other" group for display
            $otherGroup = ($match->group_id_1 === $group->id) ? $match->group2 : $match->group1;
            
            $data = [
                'id' => $match->id,
                'status' => $match->status,
                'created_at' => $match->created_at,
                'other_group' => $otherGroup,
                'initiator' => $match->initiator
            ];

            if ($isInitiatedByThisGroup) {
                $outgoing[] = $data;
            } else {
                $incoming[] = $data;
            }
        }

        return response()->json([
            'incoming' => $incoming,
            'outgoing' => $outgoing
        ]);
    }

    /**
     * Get connected groups (accepted matches)
     *
     * @OA\Get(
     *   path="/groups/{groupId}/matches/connected",
     *   tags={"Groups"},
     *   summary="Get connected groups",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="List of connected groups"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function connectedMatches(int $groupId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $user = Auth::user();

        // Check membership (any member can see connected groups?)
        // Let's say yes, any member can see who they are matched with.
        if (!$group->hasMember($user->id)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $connectedGroups = $this->matchingService->getConnectedGroups($group);

        return response()->json(['connected' => $connectedGroups]);
    }

    /**
     * Accept a match request
     *
     * @OA\Post(
     *   path="/groups/{groupId}/matches/requests/{matchId}/accept",
     *   tags={"Groups"},
     *   summary="Accept a match request",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="matchId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Match accepted"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function acceptMatchRequest(int $groupId, int $matchId): JsonResponse
    {
        $match = \App\Models\GroupMatch::findOrFail($matchId);
        $user = Auth::user();
        
        // Verify group context (ensure request url matches logic, though service handles auth)
        if ($match->group_id_1 !== $groupId && $match->group_id_2 !== $groupId) {
             return response()->json(['error' => 'Match does not involve this group'], 404);
        }

        try {
            $this->matchingService->acceptMatch($match, $user);
            return response()->json(['message' => 'Match accepted']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Reject a match request
     *
     * @OA\Post(
     *   path="/groups/{groupId}/matches/requests/{matchId}/reject",
     *   tags={"Groups"},
     *   summary="Reject a match request",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Parameter(name="matchId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Match rejected"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function rejectMatchRequest(int $groupId, int $matchId): JsonResponse
    {
        $match = \App\Models\GroupMatch::findOrFail($matchId);
        $user = Auth::user();

        if ($match->group_id_1 !== $groupId && $match->group_id_2 !== $groupId) {
             return response()->json(['error' => 'Match does not involve this group'], 404);
        }

        try {
            $this->matchingService->rejectMatch($match, $user);
            return response()->json(['message' => 'Match rejected']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * List user's groups
    *
    * @OA\Get(
    *   path="/groups",
    *   tags={"Groups"},
    *   summary="List groups the authenticated user belongs to",
    *   security={{"bearerAuth":{}}},
    *   @OA\Response(
    *     response=200,
    *     description="List of groups",
    *     @OA\JsonContent(
    *       type="object",
    *       @OA\Property(property="groups", type="array", @OA\Items(ref="#/components/schemas/Group"))
    *     )
    *   ),
    *   @OA\Response(response=401, ref="#/components/responses/Unauthorized")
    * )
     */
    public function index(): JsonResponse
    {
        // Return all public/discoverable groups
        $groups = Group::public()
            ->active()
            ->with(['creator'])
            ->withCount('activeMembers')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['groups' => $groups]);
    }

    /**
     * List user's groups
     *
     * @OA\Get(
     *   path="/groups/my-groups",
     *   tags={"Groups"},
     *   summary="List groups the authenticated user belongs to",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="List of groups",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="groups", type="array", @OA\Items(ref="#/components/schemas/Group"))
     *     )
     *   ),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function myGroups(): JsonResponse
    {
        $userId = Auth::id();
        
        $groups = Group::whereHas('activeMembers', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->active()
        ->with(['creator', 'activeMembers'])
        ->withCount('activeMembers')
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json(['groups' => $groups]);
    }

    /**
     * Create a new group
        *
        * @OA\Post(
        *   path="/groups",
        *   tags={"Groups"},
        *   summary="Create a new group",
        *   security={{"bearerAuth":{}}},
        *   @OA\RequestBody(
        *     required=true,
        *     @OA\JsonContent(
        *       required={"name"},
        *       @OA\Property(property="name", type="string", maxLength=100),
        *       @OA\Property(property="description", type="string", nullable=true, maxLength=1000),
        *       @OA\Property(property="visibility", type="string", enum={"public","private"}),
        *       @OA\Property(property="max_members", type="integer", minimum=2, maximum=500)
        *     )
        *   ),
        *   @OA\Response(
        *     response=201,
        *     description="Group created",
        *     @OA\JsonContent(type="object",
        *       @OA\Property(property="group", ref="#/components/schemas/Group")
        *     )
        *   ),
        *   @OA\Response(response=422, ref="#/components/responses/ValidationError"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function store(StoreGroupRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $group = $this->groupService->createGroup(Auth::id(), $validated);
            return response()->json(['group' => $group->load('creator', 'activeMembers')], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get group details
        *
        * @OA\Get(
        *   path="/groups/{groupId}",
        *   tags={"Groups"},
        *   summary="Get group details",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Group details",
        *     @OA\JsonContent(type="object",
        *       @OA\Property(property="group", type="object"),
        *       @OA\Property(property="is_member", type="boolean"),
        *       @OA\Property(property="user_role", type="string", nullable=true)
        *     )
        *   ),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function show(int $groupId): JsonResponse
    {
        $group = Group::with(['creator', 'activeMembers.user'])
            ->withCount('activeMembers')
            ->findOrFail($groupId);

        $userId = Auth::id();

        // Check visibility
        if ($group->visibility === 'private' && !$group->hasMember($userId)) {
            return response()->json(['error' => 'Group not found'], 404);
        }

        return response()->json([
            'group' => $group,
            'is_member' => $group->hasMember($userId),
            'user_role' => $group->getMemberRole($userId),
        ]);
    }

    /**
     * Update group
        *
        * @OA\Put(
        *   path="/groups/{groupId}",
        *   tags={"Groups"},
        *   summary="Update group settings",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\RequestBody(@OA\JsonContent(
        *     @OA\Property(property="name", type="string"),
        *     @OA\Property(property="description", type="string", nullable=true),
        *     @OA\Property(property="visibility", type="string", enum={"public","private"}),
        *     @OA\Property(property="max_members", type="integer")
        *   )),
        *   @OA\Response(response=200, description="Updated",
        *     @OA\JsonContent(type="object",
        *       @OA\Property(property="group", type="object")
        *     )
        *   ),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function update(UpdateGroupRequest $request, int $groupId): JsonResponse
    {
        $validated = $request->validated();

        $group = Group::findOrFail($groupId);
        $userId = Auth::id();

        $member = $group->activeMembers()->where('user_id', $userId)->first();
        
        if (!$member || !$member->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $group->update($validated);

        return response()->json(['group' => $group->load('creator', 'activeMembers')]);
    }

    /**
     * Delete/deactivate group
        *
        * @OA\Delete(
        *   path="/groups/{groupId}",
        *   tags={"Groups"},
        *   summary="Deactivate a group (owner only)",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Deactivated",
        *     @OA\JsonContent(type="object",
        *       @OA\Property(property="message", type="string")
        *     )
        *   ),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function destroy(int $groupId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $userId = Auth::id();

        $member = $group->activeMembers()->where('user_id', $userId)->first();
        
        if (!$member || !$member->isOwner()) {
            return response()->json(['error' => 'Only owner can delete group'], 403);
        }

        $group->is_active = false;
        $group->save();

        return response()->json(['message' => 'Group deactivated']);
    }

    /**
     * Join a group
        *
        * @OA\Post(
        *   path="/groups/{groupId}/join",
        *   tags={"Groups"},
        *   summary="Join a public group",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Joined"),
        *   @OA\Response(response=400, description="Already a member or full"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function join(int $groupId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        try {
            $this->groupService->joinGroup($group, Auth::id());
            return response()->json(['message' => 'Joined group successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], ($c = (int)$e->getCode()) && $c >= 100 && $c < 600 ? $c : 400);
        }
    }

    /**
     * Leave a group
        *
        * @OA\Post(
        *   path="/groups/{groupId}/leave",
        *   tags={"Groups"},
        *   summary="Leave a group",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Left group"),
        *   @OA\Response(response=400, description="Not a member or owner cannot leave"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function leave(int $groupId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        try {
            $this->groupService->leaveGroup($group, Auth::id());
            return response()->json(['message' => 'Left group successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], ($c = (int)$e->getCode()) && $c >= 100 && $c < 600 ? $c : 400);
        }
    }

    /**
     * Set member role (owner can set any non-owner; admin can set moderator/member)
        *
        * @OA\Post(
        *   path="/groups/{groupId}/members/{memberUserId}/role",
        *   tags={"Groups"},
        *   summary="Set a member's role",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Parameter(name="memberUserId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\RequestBody(required=true, @OA\JsonContent(
        *     required={"role"},
        *     @OA\Property(property="role", type="string", enum={"admin","moderator","member"})
        *   )),
        *   @OA\Response(response=200, description="Role updated or unchanged"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=400, description="Cannot change owner role or invalid assignment"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function setRole(SetGroupRoleRequest $request, int $groupId, int $memberUserId): JsonResponse
    {
        $validated = $request->validated();

        $group = Group::findOrFail($groupId);
        try {
            $this->groupService->setMemberRole($group, Auth::id(), $memberUserId, $validated['role']);
            return response()->json(['message' => 'Role updated']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], ($c = (int)$e->getCode()) && $c >= 100 && $c < 600 ? $c : 400);
        }
    }

    /**
     * Transfer ownership to another active member
        *
        * @OA\Post(
        *   path="/groups/{groupId}/ownership/transfer",
        *   tags={"Groups"},
        *   summary="Transfer group ownership",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\RequestBody(required=true, @OA\JsonContent(
        *     required={"new_owner_user_id"},
        *     @OA\Property(property="new_owner_user_id", type="integer")
        *   )),
        *   @OA\Response(response=200, description="Ownership transferred"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=400, description="Target not active member or already owner"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function transferOwnership(TransferGroupOwnershipRequest $request, int $groupId): JsonResponse
    {
        $validated = $request->validated();

        $group = Group::findOrFail($groupId);
        try {
            $this->groupService->transferOwnership($group, Auth::id(), $validated['new_owner_user_id']);
            return response()->json(['message' => 'Ownership transferred']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], ($c = (int)$e->getCode()) && $c >= 100 && $c < 600 ? $c : 400);
        }
    }

    /**
     * Ban a member (owner/admin). Banned members are deactivated and cannot rejoin until unbanned.
        *
        * @OA\Post(
        *   path="/groups/{groupId}/members/{memberUserId}/ban",
        *   tags={"Groups"},
        *   summary="Ban a member",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Parameter(name="memberUserId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Member banned or already banned"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function banMember(int $groupId, int $memberUserId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        try {
            $this->groupService->banMember($group, Auth::id(), $memberUserId, request()->input('reason'));
            return response()->json(['message' => 'Member banned']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], ($c = (int)$e->getCode()) && $c >= 100 && $c < 600 ? $c : 400);
        }
    }

    /**
     * Unban a member (owner/admin)
        *
        * @OA\Post(
        *   path="/groups/{groupId}/members/{memberUserId}/unban",
        *   tags={"Groups"},
        *   summary="Unban a member",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Parameter(name="memberUserId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Member unbanned or not banned"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function unbanMember(int $groupId, int $memberUserId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        try {
            $this->groupService->unbanMember($group, Auth::id(), $memberUserId);
            return response()->json(['message' => 'Member unbanned']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], ($c = (int)$e->getCode()) && $c >= 100 && $c < 600 ? $c : 400);
        }
    }

    /**
     * Mute a member temporarily (owner/admin). Accepts either duration_minutes or until timestamp.
        *
        * @OA\Post(
        *   path="/groups/{groupId}/members/{memberUserId}/mute",
        *   tags={"Groups"},
        *   summary="Mute a member temporarily",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Parameter(name="memberUserId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\RequestBody(@OA\JsonContent(
        *     @OA\Property(property="duration_minutes", type="integer", minimum=1, maximum=10080),
        *     @OA\Property(property="until", type="string", format="date-time"),
        *     @OA\Property(property="reason", type="string", maxLength=255)
        *   )),
        *   @OA\Response(response=200, description="Muted or unchanged"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function muteMember(MuteGroupMemberRequest $request, int $groupId, int $memberUserId): JsonResponse
    {
        $validated = $request->validated();

        $group = Group::findOrFail($groupId);
        try {
            $mutedUntil = $this->groupService->muteMember(
                $group, 
                Auth::id(), 
                $memberUserId, 
                $validated['duration_minutes'] ?? null, 
                $validated['until'] ?? null, 
                $validated['reason'] ?? null
            );
            return response()->json(['message' => 'Member muted', 'muted_until' => $mutedUntil]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], ($c = (int)$e->getCode()) && $c >= 100 && $c < 600 ? $c : 400);
        }
    }

    /**
     * Unmute a member.
        *
        * @OA\Post(
        *   path="/groups/{groupId}/members/{memberUserId}/unmute",
        *   tags={"Groups"},
        *   summary="Unmute a member",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Parameter(name="memberUserId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Unmuted or not muted"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function unmuteMember(int $groupId, int $memberUserId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        try {
            $this->groupService->unmuteMember($group, Auth::id(), $memberUserId);
            return response()->json(['message' => 'Member unmuted']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], ($c = (int)$e->getCode()) && $c >= 100 && $c < 600 ? $c : 400);
        }
    }

    /**
     * Simple analytics endpoint for group (owner/admin only).
        *
        * @OA\Get(
        *   path="/groups/{groupId}/stats",
        *   tags={"Groups"},
        *   summary="Group statistics (admin/owner)",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Stats",
        *     @OA\JsonContent(type="object",
        *       @OA\Property(property="group_id", type="integer"),
        *       @OA\Property(property="roles", type="object"),
        *       @OA\Property(property="total_messages", type="integer"),
        *       @OA\Property(property="banned_members", type="integer"),
        *       @OA\Property(property="muted_members", type="integer"),
        *       @OA\Property(property="max_members", type="integer"),
        *       @OA\Property(property="is_full", type="boolean")
        *     )
        *   ),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function stats(int $groupId): JsonResponse
    {
        $group = Group::with(['activeMembers'])->findOrFail($groupId);
        $actorId = Auth::id();
        $actor = $group->activeMembers()->where('user_id', $actorId)->first();
        if (!$actor || !$actor->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $roleCounts = $group->activeMembers()
            ->selectRaw('role, COUNT(*) as count')
            ->groupBy('role')
            ->pluck('count', 'role');

        $totalMessages = \App\Models\GroupMessage::where('group_id', $groupId)->count();
        $bannedCount = $group->members()->where('is_banned', true)->count();
        $mutedCount = $group->activeMembers()->where('is_muted', true)->count();

        return response()->json([
            'group_id' => $groupId,
            'roles' => $roleCounts,
            'total_messages' => $totalMessages,
            'banned_members' => $bannedCount,
            'muted_members' => $mutedCount,
            'max_members' => $group->max_members,
            'is_full' => $group->isFull(),
        ]);
    }

    /**
     * Kick a member (owner/admin). Makes member inactive but not banned.
        *
        * @OA\Post(
        *   path="/groups/{groupId}/members/{memberUserId}/kick",
        *   tags={"Groups"},
        *   summary="Kick a member",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Parameter(name="memberUserId", in="path", required=true, @OA\Schema(type="integer")),
        *   @OA\Response(response=200, description="Member removed"),
        *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
        *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
        *   @OA\Response(response=401, description="Unauthenticated")
        * )
     */
    public function kickMember(int $groupId, int $memberUserId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        try {
            $this->groupService->kickMember($group, Auth::id(), $memberUserId);
            return response()->json(['message' => 'Member removed']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], ($c = (int)$e->getCode()) && $c >= 100 && $c < 600 ? $c : 400);
        }
    }

    /**
     * Discover public groups
        *
        * @OA\Get(
        *   path="/groups/discover",
        *   tags={"Groups"},
        *   summary="Discover public groups",
        *   security={{"bearerAuth":{}}},
        *   @OA\Parameter(name="search", in="query", required=false, @OA\Schema(type="string")),
        *   @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", minimum=1, maximum=50)),
        *   @OA\Response(response=200, description="Paginated groups list")
        * )
     */
    public function discover(DiscoverGroupsRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Group::public()
            ->active()
            ->with(['creator'])
            ->withCount('activeMembers');

        if (isset($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $groups = $query->orderBy('created_at', 'desc')
            ->paginate($validated['per_page'] ?? 20);

        return response()->json($groups);
    }

    /**
     * Get events for a group
     *
     * @OA\Get(
     *   path="/groups/{groupId}/events",
     *   tags={"Groups"},
     *   summary="Get events for a group (shared and owned)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="groupId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="List of events"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function events(int $groupId): JsonResponse
    {
        $group = Group::findOrFail($groupId);
        $user = Auth::user();

        // Check if user has permission to view group events
        // If private, must be a member
        if ($group->visibility === 'private' && !$group->hasMember($user->id)) {
             return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Fetch events associated with the group (via pivot table)
        // Also fetch events created by the group if we add that column later, but for now relies on event_groups
        $events = $group->events()
            ->with(['creator', 'attendees'])
            ->withCount('attendees')
            ->orderBy('start_time', 'asc')
            ->where('end_time', '>', now()) // Only future/ongoing events? Or all? Let's do upcoming by default
            ->get();

        return response()->json(['events' => $events]);
    }
}
