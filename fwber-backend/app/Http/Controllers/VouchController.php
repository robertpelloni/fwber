<?php

namespace App\Http\Controllers;

use App\Http\Requests\Social\StoreVouchRequest;
use App\Models\User;
use App\Models\Vouch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VouchController extends Controller
{
    /**
     * Generate a unique vouch link for the user.
     */
    public function generateLink(Request $request): JsonResponse
    {
        $user = $request->user();
        $url = rtrim((string) config('app.url', 'https://fwber.me'), '/').'/vouch/'.$user->id;

        return response()->json([
            'url' => $url,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Submit a public vouch for a user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|in:safe,fun,hot',
            'relationship_type' => 'nullable|string',
            'comment' => 'nullable|string|max:500',
            'voucher_name' => 'nullable|string|max:100',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $ip = $request->ip();

        // Prevent duplicate vouch of same type from same IP
        $exists = Vouch::where('to_user_id', $user->id)
            ->where('ip_address', $ip)
            ->where('type', $validated['type'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Vouch recorded successfully.']);
        }

        Vouch::create([
            'to_user_id' => $user->id,
            'type' => $validated['type'],
            'relationship_type' => $validated['relationship_type'] ?? 'friend',
            'comment' => $validated['comment'] ?? '',
            'voucher_name' => $validated['voucher_name'] ?? 'Someone',
            'ip_address' => $ip,
        ]);

        // Simple notification (non-push for now)
        try {
            $voucher = $validated['voucher_name'] ?? 'Someone';
            // Logic for notification could be added here
        } catch (\Exception $e) {}

        return response()->json(['message' => 'Vouch recorded successfully.']);
    }
}
