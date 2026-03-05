<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\UserLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DecoyProfileController extends Controller
{
    /**
     * Set up a decoy profile for the authenticated user.
     */
    public function setup(Request $request)
    {
        $request->validate([
            'decoy_password' => 'required|string|min:8',
        ]);

        $mainUser = $request->user();

        // If a decoy user already exists, we might want to update it, but for simplicity, let's just update the password
        if ($mainUser->decoy_user_id) {
            $mainUser->update([
                'decoy_password' => Hash::make($request->decoy_password),
            ]);
            
            return response()->json([
                'message' => 'Decoy password updated successfully.',
            ]);
        }

        // 1. Create the decoy user
        $fakeName = 'User' . rand(1000, 9999);
        $fakeEmail = 'user_' . Str::random(10) . '@example.com';
        
        $decoyUser = clone $mainUser; // Quick way to copy some attributes, but safer to explicitly set them
        
        $decoyUser = User::create([
            'name' => $fakeName,
            'email' => $fakeEmail,
            'password' => Hash::make(Str::random(32)), // It's impossible to log into this account directly with email
            'is_decoy' => true,
        ]);

        // 2. Create decoy profile
        UserProfile::create([
            'user_id' => $decoyUser->id,
            'bio' => 'Just looking around. Not super active.',
            'birthdate' => now()->subYears(30)->format('Y-m-d'), // Fake age
            'gender' => 'other',
        ]);

        // 3. Create generic location (e.g., city center) if the main user has one
        $mainLocation = UserLocation::where('user_id', $mainUser->id)->first();
        if ($mainLocation) {
            // Fuzz the location slightly
            UserLocation::create([
                'user_id' => $decoyUser->id,
                'latitude' => $mainLocation->latitude + (rand(-100, 100) / 10000),
                'longitude' => $mainLocation->longitude + (rand(-100, 100) / 10000),
                'is_fuzzed' => true,
            ]);
        }

        // 4. Link back to main user
        $mainUser->update([
            'decoy_password' => Hash::make($request->decoy_password),
            'decoy_user_id' => $decoyUser->id,
        ]);

        return response()->json([
            'message' => 'Decoy profile created and linked successfully.',
            'decoy_user_id' => $decoyUser->id,
        ]);
    }

    /**
     * Remove the decoy profile and password from the authenticated user.
     */
    public function remove(Request $request)
    {
        $mainUser = $request->user();
        
        if ($mainUser->decoy_user_id) {
            // Optional: Hard delete the decoy user and its relationships to clean up
            $decoyUser = User::find($mainUser->decoy_user_id);
            if ($decoyUser) {
                // Cascading deletes should handle Profile, Location, etc. if set up correctly
                $decoyUser->delete();
            }
        }

        $mainUser->update([
            'decoy_password' => null,
            'decoy_user_id' => null,
        ]);

        return response()->json([
            'message' => 'Decoy profile removed successfully.',
        ]);
    }
}
