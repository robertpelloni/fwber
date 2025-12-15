<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\RequestAvatarRequest;
use App\Http\Requests\Api\UpsertPhysicalProfileRequest;
use App\Models\UserPhysicalProfile;
use App\Jobs\GenerateAvatar;
use Illuminate\Support\Facades\Auth;

class UserPhysicalProfileController extends Controller
{
    /**
     * Get the authenticated user's physical profile
     *
     * @OA\Get(
     *   path="/physical-profile",
     *   tags={"Physical Profile"},
     *   summary="Get physical profile",
    *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Profile",
     *     @OA\JsonContent(type="object",
     *       @OA\Property(property="data", type="object",
     *         @OA\Property(property="height_cm", type="integer", nullable=true),
     *         @OA\Property(property="body_type", type="string", nullable=true),
     *         @OA\Property(property="hair_color", type="string", nullable=true),
     *         @OA\Property(property="eye_color", type="string", nullable=true),
     *         @OA\Property(property="skin_tone", type="string", nullable=true),
     *         @OA\Property(property="ethnicity", type="string", nullable=true),
     *         @OA\Property(property="facial_hair", type="string", nullable=true),
     *         @OA\Property(property="tattoos", type="boolean", nullable=true),
     *         @OA\Property(property="piercings", type="boolean", nullable=true),
     *         @OA\Property(property="dominant_hand", type="string", nullable=true, enum={"left","right","ambi"}),
     *         @OA\Property(property="fitness_level", type="string", nullable=true, enum={"low","average","fit","athletic"}),
     *         @OA\Property(property="clothing_style", type="string", nullable=true),
     *         @OA\Property(property="avatar_prompt", type="string", nullable=true),
     *         @OA\Property(property="avatar_status", type="string", nullable=true)
     *       )
     *     )
     *   ),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show()
    {
        $profile = UserPhysicalProfile::where('user_id', Auth::id())->first();
        return response()->json(['data' => $profile]);
    }

    /**
     * Create or update the authenticated user's physical profile
     *
     * @OA\Put(
     *   path="/physical-profile",
     *   tags={"Physical Profile"},
     *   summary="Upsert physical profile",
    *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(@OA\JsonContent(
     *     @OA\Property(property="height_cm", type="integer", minimum=80, maximum=250),
     *     @OA\Property(property="body_type", type="string"),
     *     @OA\Property(property="hair_color", type="string"),
     *     @OA\Property(property="eye_color", type="string"),
     *     @OA\Property(property="skin_tone", type="string"),
     *     @OA\Property(property="ethnicity", type="string"),
     *     @OA\Property(property="facial_hair", type="string"),
     *     @OA\Property(property="tattoos", type="boolean"),
     *     @OA\Property(property="piercings", type="boolean"),
     *     @OA\Property(property="dominant_hand", type="string", enum={"left","right","ambi"}),
     *     @OA\Property(property="fitness_level", type="string", enum={"low","average","fit","athletic"}),
     *     @OA\Property(property="clothing_style", type="string"),
     *     @OA\Property(property="avatar_prompt", type="string", maxLength=500)
     *   )),
     *   @OA\Response(response=200, description="Upserted",
     *     @OA\JsonContent(type="object",
     *       @OA\Property(property="data", type="object")
     *     )
     *   ),
    *   @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function upsert(UpsertPhysicalProfileRequest $request)
    {
        $data = $request->validated();

        $profile = UserPhysicalProfile::updateOrCreate(
            ['user_id' => Auth::id()],
            $data
        );

        return response()->json(['data' => $profile]);
    }

    /**
     * Request avatar generation using the saved avatar_prompt
     *
     * @OA\Post(
     *   path="/physical-profile/avatar/request",
     *   tags={"Physical Profile"},
     *   summary="Request avatar generation",
    *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(@OA\JsonContent(
     *     @OA\Property(property="style", type="string", enum={"realistic", "anime", "fantasy", "sci-fi", "cartoon", "pixel-art"})
     *   )),
     *   @OA\Response(response=200, description="Requested",
     *     @OA\JsonContent(type="object",
     *       @OA\Property(property="data", type="object",
     *         @OA\Property(property="avatar_status", type="string", example="requested")
     *       )
     *     )
    *   ),
    *   @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *   @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function requestAvatar(RequestAvatarRequest $request)
    {
        $data = $request->validated();

        $profile = UserPhysicalProfile::firstOrNew(['user_id' => Auth::id()]);
        if (!$profile->avatar_prompt) {
            return response()->json(['error' => 'Set avatar_prompt first'], 422);
        }
        $profile->avatar_status = 'requested';
        $profile->save();
        // Dispatch async generation job (queue driver 'sync' will process immediately in dev)
        GenerateAvatar::dispatch($profile->id, $data['style']);
        return response()->json(['data' => $profile]);
    }
}
