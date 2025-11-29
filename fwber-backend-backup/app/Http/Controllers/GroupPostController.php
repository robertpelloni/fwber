<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GroupPostController extends Controller
{
    public function index($groupId)
    {
        $posts = GroupPost::where('group_id', $groupId)
            ->with('user')
            ->latest()
            ->get();
        return response()->json($posts);
    }

    public function store(Request $request, $groupId)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $group = Group::findOrFail($groupId);
        
        // Check if user is member
        if (!$group->hasMember(Auth::id())) {
            return response()->json(['message' => 'Must be a member to post'], 403);
        }

        $post = GroupPost::create([
            'group_id' => $groupId,
            'user_id' => Auth::id(),
            'content' => $validated['content'],
        ]);

        return response()->json($post->load('user'), 201);
    }

    public function destroy($postId)
    {
        $post = GroupPost::findOrFail($postId);
        
        if ($post->user_id !== Auth::id()) {
            // Check if admin
            $group = $post->group;
            $role = $group->getMemberRole(Auth::id());
            if (!in_array($role, ['admin', 'moderator'])) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $post->delete();
        return response()->json(['message' => 'Post deleted']);
    }
}
