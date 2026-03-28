<?php

namespace App\Http\Controllers;

use App\Models\HardwareToken;
use App\Models\User;
use App\Services\AIMatchingService;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HardwareTokenController extends Controller
{
    public function __construct(
        private readonly AIMatchingService $matchingService,
        private readonly PushNotificationService $pushService
    ) {}

    /**
     * Register a new hardware token to the authenticated user.
     */
    public function register(Request $request)
    {
        $request->validate([
            'token_uuid' => 'required|string|unique:hardware_tokens,token_uuid',
            'hardware_model' => 'nullable|string',
        ]);

        $user = Auth::user();

        // Deactivate old tokens for this user
        HardwareToken::where('user_id', $user->id)->update(['is_active' => false]);

        $token = HardwareToken::create([
            'user_id' => $user->id,
            'token_uuid' => $request->token_uuid,
            'hardware_model' => $request->hardware_model ?? 'fwber_token_v1',
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Hardware token paired successfully',
            'token' => $token,
        ]);
    }

    /**
     * The background BLE bridge (mobile app) reports a detected token in physical proximity.
     */
    public function ping(Request $request)
    {
        $request->validate([
            'detected_token_uuid' => 'required|string',
            'rssi' => 'required|integer', // Signal strength
        ]);

        $detectorUser = Auth::user();

        // Find the user who owns the detected token
        $detectedToken = HardwareToken::where('token_uuid', $request->detected_token_uuid)
            ->where('is_active', true)
            ->first();

        if (! $detectedToken) {
            return response()->json(['message' => 'Token not recognized'], 404);
        }

        $detectedToken->update(['last_seen_at' => now()]);

        $targetUser = $detectedToken->user;

        // Don't ping yourself
        if ($detectorUser->id === $targetUser->id) {
            return response()->json(['message' => 'Self ping ignored']);
        }

        // Calculate Compatibility
        $score = $this->matchingService->calculateCompatibility($detectorUser->id, $targetUser->id);

        // If highly compatible (>85%), trigger the hardware token to Vibrate/Glow
        if ($score >= 85) {
            // We use the PushNotificationService to send a silent data payload to the target user's phone,
            // which then relays the "Vibrate" command over Bluetooth to their physical token.
            $this->pushService->sendSilentDataPush($targetUser->id, [
                'type' => 'hardware_token_trigger',
                'action' => 'glow_and_vibrate',
                'intensity' => 'high',
                'match_id' => $detectorUser->id, // Optional: let them open the app to see who
            ]);

            // Also alert the detector
            $this->pushService->sendPushNotification($detectorUser->id, 'High Compatibility Nearby! 🚨', 'Someone you have an 85%+ match with is within 50 feet. Look around!');

            Log::info("Hardware Token Match Triggered: User {$detectorUser->id} detected User {$targetUser->id} (Score: {$score})");

            return response()->json([
                'message' => 'High compatibility detected. Hardware trigger dispatched.',
                'match_score' => $score,
            ]);
        }

        return response()->json([
            'message' => 'Token ping recorded. Score below threshold.',
            'match_score' => $score,
        ]);
    }

    /**
     * Get the status of the user's active hardware token.
     */
    public function status(Request $request)
    {
        $token = HardwareToken::where('user_id', Auth::id())
            ->where('is_active', true)
            ->first();

        if (! $token) {
            return response()->json(['message' => 'No active token found'], 404);
        }

        return response()->json(['token' => $token]);
    }
}
