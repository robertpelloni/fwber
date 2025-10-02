<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        $user = auth()->user()->loadMissing("profile");

        return response()->json([
            "user" => UserResource::make($user),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $request->validated();

        if (array_key_exists("name", $data)) {
            $user->fill(["name" => $data["name"]]);
            $user->save();
        }

        $profileData = $data["profile"] ?? [];
        $user->profile()->updateOrCreate([], $profileData);

        return response()->json([
            "user" => UserResource::make($user->fresh("profile")),
        ]);
    }
}
