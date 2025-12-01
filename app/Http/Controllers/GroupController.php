<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::where('privacy', 'public')
            ->orWhere('visibility', 'visible')
            ->withCount('members')
            ->get();
        return response()->json($groups);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'privacy' => 'required|in:public,private',
        ]);

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

        return response()->json($group, 201);
    }

    public function show($id)
    {
        $group = Group::with(['members.user', 'posts.user'])->findOrFail($id);
        return response()->json($group);
    }

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

        return response()->json(['message' => 'Joined group successfully']);
    }

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

        return response()->json(['message' => 'Left group successfully']);
    }

    public function myGroups()
    {
        $user = Auth::user();
        $groups = Group::whereHas('members', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->get();

        return response()->json($groups);
    }
}
