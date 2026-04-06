<?php

namespace App\Http\Controllers;

use App\Http\Requests\VenueLoginRequest;
use App\Http\Requests\VenueRegisterRequest;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VenueAuthController extends Controller
{
    public function register(VenueRegisterRequest $request)
    {
        $validated = $request->validated();

        $venue = Venue::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'business_type' => $validated['business_type'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'state' => $validated['state'],
            'zip_code' => $validated['zip_code'],
            'verification_status' => 'pending',
        ]);

        $token = $venue->createToken('venue-token')->plainTextToken;

        return response()->json([
            'venue' => $venue,
            'token' => $token,
        ], 201);
    }

    public function login(VenueLoginRequest $request)
    {
        $validated = $request->validated();

        $venue = Venue::where('email', $validated['email'])->first();

        if (! $venue || ! Hash::check($validated['password'], $venue->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        $token = $venue->createToken('venue-token')->plainTextToken;

        return response()->json([
            'venue' => $venue,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function me(Request $request)
    {
        return $request->user();
    }
}
