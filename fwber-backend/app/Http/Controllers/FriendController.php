<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class FriendController extends Controller
{
    /**
     * @OA\Get(
     *   path="/friends",
     *   tags={"Social"},
     *   summary="List friends",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="List of friends")
     * )
     */
    public function index()
    {
        $user = Auth::user();
        // Assuming a simple friends relationship or query
        // For MVP, we might just return matches that are 'established' or similar
        // Or if there is a specific friends table.
        // Based on context, let's assume we return matches for now or empty if not implemented fully.
        
        // If there is a friends() relation:
        // $friends = $user->friends;
        
        // Placeholder implementation based on typical social app structure
        return response()->json(['data' => []]);
    }

    /**
     * @OA\Post(
     *   path="/friends/invite",
     *   tags={"Social"},
     *   summary="Invite a friend",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"email"},
     *       @OA\Property(property="email", type="string", format="email")
     *     )
     *   ),
     *   @OA\Response(response=200, description="Invitation sent")
     * )
     */
    public function invite(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        // Logic to send invitation email
        
        return response()->json(['message' => 'Invitation sent']);
    }
}
