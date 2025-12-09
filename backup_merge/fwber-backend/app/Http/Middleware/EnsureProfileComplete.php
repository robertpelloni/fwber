<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Check if profile exists
        $profile = $user->profile;
        
        if (!$profile) {
            return response()->json([
                'message' => 'Profile not found. Please complete your profile to continue.',
                'required_fields' => [
                    'display_name',
                    'date_of_birth',
                    'gender',
                    'location',
                ],
            ], 400);
        }

        // Define required fields for profile completion
        $requiredFields = [
            'display_name' => 'Display name',
            'date_of_birth' => 'Date of birth',
            'gender' => 'Gender',
        ];

        // Check for missing required fields
        $missingFields = [];
        foreach ($requiredFields as $field => $label) {
            if (empty($profile->$field)) {
                $missingFields[] = [
                    'field' => $field,
                    'label' => $label,
                ];
            }
        }

        // Check for location (either description or coordinates)
        $hasLocation = !empty($profile->location_description) || 
                      (!empty($profile->location_latitude) && !empty($profile->location_longitude));
        
        if (!$hasLocation) {
            $missingFields[] = [
                'field' => 'location',
                'label' => 'Location',
            ];
        }

        if (!empty($missingFields)) {
            return response()->json([
                'message' => 'Please complete your profile before accessing this feature.',
                'missing_fields' => $missingFields,
            ], 400);
        }

        return $next($request);
    }
}
