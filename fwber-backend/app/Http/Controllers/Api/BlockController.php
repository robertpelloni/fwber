<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Block;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'blocked_id' => 'required|integer|exists:users,id'
        ]);
        if ($data['blocked_id'] == Auth::id()) {
            return response()->json(['error' => 'Cannot block yourself'], 422);
        }
        $block = Block::firstOrCreate([
            'blocker_id' => Auth::id(),
            'blocked_id' => $data['blocked_id']
        ]);
        return response()->json(['data' => $block]);
    }

    public function destroy(int $blockedId)
    {
        Block::where('blocker_id', Auth::id())->where('blocked_id', $blockedId)->delete();
        return response()->json(['ok' => true]);
    }
}
