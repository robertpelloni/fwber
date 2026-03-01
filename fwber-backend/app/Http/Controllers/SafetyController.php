<?php

namespace App\Http\Controllers;

use App\Models\EmergencyContact;
use App\Models\SafeWalk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SafetyController extends Controller
{
    // ── Emergency Contacts ──────────────────────────────

    public function getContacts(): JsonResponse
    {
        $contacts = EmergencyContact::where('user_id', Auth::id())
            ->orderByDesc('is_primary')
            ->get();

        return response()->json(['contacts' => $contacts]);
    }

    public function addContact(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'relationship' => 'nullable|string|max:50',
            'is_primary' => 'boolean',
        ]);

        // If setting as primary, unset others
        if ($request->boolean('is_primary')) {
            EmergencyContact::where('user_id', Auth::id())
                ->update(['is_primary' => false]);
        }

        $contact = EmergencyContact::create([
            'user_id' => Auth::id(),
            ...$request->only(['name', 'phone', 'email', 'relationship', 'is_primary']),
        ]);

        return response()->json(['contact' => $contact], 201);
    }

    public function deleteContact(string $id): JsonResponse
    {
        $contact = EmergencyContact::where('user_id', Auth::id())->findOrFail($id);
        $contact->delete();
        return response()->json(['message' => 'Contact removed.']);
    }

    // ── Panic Button ────────────────────────────────────

    public function triggerPanic(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $user = Auth::user();
        $contacts = EmergencyContact::where('user_id', $user->id)->get();

        // Mark any active safe walk as panic
        SafeWalk::where('user_id', $user->id)
            ->active()
            ->update(['status' => 'panic']);

        // In production: send SMS/email/push to emergency contacts
        // For now, log and return confirmation
        $alertsSent = $contacts->count();

        return response()->json([
            'status' => 'panic_triggered',
            'alerts_sent' => $alertsSent,
            'contacts_notified' => $contacts->pluck('name'),
            'location' => [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    // ── Safe Walk ───────────────────────────────────────

    public function startSafeWalk(Request $request): JsonResponse
    {
        $request->validate([
            'destination' => 'nullable|string|max:255',
            'start_lat' => 'required|numeric',
            'start_lng' => 'required|numeric',
            'dest_lat' => 'nullable|numeric',
            'dest_lng' => 'nullable|numeric',
        ]);

        // End any existing active walks
        SafeWalk::where('user_id', Auth::id())
            ->active()
            ->update(['status' => 'completed', 'ended_at' => now()]);

        $walk = SafeWalk::create([
            'user_id' => Auth::id(),
            'status' => 'active',
            'destination' => $request->input('destination'),
            'start_lat' => $request->input('start_lat'),
            'start_lng' => $request->input('start_lng'),
            'current_lat' => $request->input('start_lat'),
            'current_lng' => $request->input('start_lng'),
            'dest_lat' => $request->input('dest_lat'),
            'dest_lng' => $request->input('dest_lng'),
            'started_at' => now(),
        ]);

        return response()->json(['walk' => $walk], 201);
    }

    public function updateLocation(Request $request, string $walkId): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $walk = SafeWalk::where('user_id', Auth::id())->findOrFail($walkId);

        $walk->update([
            'current_lat' => $request->input('latitude'),
            'current_lng' => $request->input('longitude'),
        ]);

        return response()->json(['walk' => $walk]);
    }

    public function endSafeWalk(string $walkId): JsonResponse
    {
        $walk = SafeWalk::where('user_id', Auth::id())->findOrFail($walkId);

        $walk->update([
            'status' => 'completed',
            'ended_at' => now(),
        ]);

        return response()->json(['walk' => $walk]);
    }

    public function getActiveWalk(): JsonResponse
    {
        $walk = SafeWalk::where('user_id', Auth::id())
            ->active()
            ->latest()
            ->first();

        return response()->json(['walk' => $walk]);
    }
}
