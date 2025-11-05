<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\ApiToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            "name" => $data["name"],
            "email" => $data["email"],
            "password" => $data["password"],
        ]);

        $profileData = $data["profile"] ?? [];
        $user->profile()->create($profileData);

        $token = ApiToken::generateForUser($user, "api");

        return response()->json([
            "token" => $token,
            "user" => UserResource::make($user->load("profile")),
        ], JsonResponse::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = User::query()->where("email", $data["email"])->first();

        if (! $user || ! Hash::check($data["password"], $user->password)) {
            return response()->json([
                "message" => "Invalid credentials.",
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $token = ApiToken::generateForUser($user, "api");

        return response()->json([
            "token" => $token,
            "user" => UserResource::make($user->load("profile")),
        ]);
    }

    public function logout(): JsonResponse
    {
        $token = request()->bearerToken();

        if ($token) {
            ApiToken::query()->where("token", hash("sha256", $token))->delete();
        }

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
