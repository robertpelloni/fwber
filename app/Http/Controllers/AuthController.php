<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\ApiToken;
use App\Models\User;
use App\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/register",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     description="Create a new user account with email, password, and optional profile information. Automatically generates API token for immediate authentication.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name", "email", "password", "password_confirmation"},
     *                 @OA\Property(property="name", type="string", maxLength=255, example="John Doe"),
     *                 @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *                 @OA\Property(property="password", type="string", format="password", minLength=8, example="SecurePass123!"),
     *                 @OA\Property(property="password_confirmation", type="string", format="password", example="SecurePass123!"),
     *                 @OA\Property(property="avatar", type="string", format="binary", description="Profile photo"),
     *                 @OA\Property(
     *                     property="profile",
     *                     type="object",
     *                     description="Optional profile information",
     *                     @OA\Property(property="date_of_birth", type="string", format="date", example="1990-05-15"),
     *                     @OA\Property(property="gender", type="string", enum={"male", "female", "non-binary", "other"}, example="male"),
     *                     @OA\Property(property="bio", type="string", maxLength=500, example="Software developer passionate about technology")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ValidationError")
     *     )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            "name" => $data["name"],
            "email" => $data["email"],
            "password" => $data["password"],
        ]);

        $profileData = $data["profile"] ?? [];

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $path = $file->store("photos/{$user->id}", 'public');
            $url = Storage::url($path);
            
            $profileData['avatar_url'] = $url;

            // Create Photo record
            Photo::create([
                'user_id' => $user->id,
                'filename' => basename($path),
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'thumbnail_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'width' => 0,
                'height' => 0,
                'is_primary' => true,
                'is_private' => false,
                'sort_order' => 0,
                'metadata' => [
                    'uploaded_at' => now()->toISOString(),
                    'source' => 'registration',
                ],
            ]);
        }

        $user->profile()->create($profileData);

        $token = ApiToken::generateForUser($user, "api");

        // Emit telemetry
        app(\App\Services\TelemetryService::class)->emit('user.signup', [
            'user_id' => $user->id,
            'method' => 'email',
        ]);

        return response()->json([
            "token" => $token,
            "user" => UserResource::make($user->load("profile")),
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Authentication"},
     *     summary="Login user",
     *     description="Authenticate user with email and password. Returns API token for subsequent requests.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="SecurePass123!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid credentials",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Invalid credentials.")
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     tags={"Authentication"},
     *     summary="Logout user",
     *     description="Revoke current API token and end user session",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=204,
     *         description="Logout successful (no content)"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     *     )
     * )
     */
    public function logout(): JsonResponse
    {
        $token = request()->bearerToken();

        if ($token) {
            ApiToken::query()->where("token", hash("sha256", $token))->delete();
        }

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
