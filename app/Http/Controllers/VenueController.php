<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VenueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Venue::query();

        // Filter by business type
        if ($request->has('business_type')) {
            $query->where('business_type', $request->business_type);
        }

        // Search by name or address
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate(15));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Not used in API
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Creation is handled via Auth/Register for now.
        return response()->json(['message' => 'Use /api/venue/register to create a new venue account.'], 405);
    }

    /**
     * Display the specified resource.
     */
    public function show(Venue $venue)
    {
        return response()->json($venue);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Venue $venue)
    {
        // Not used in API
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Venue $venue)
    {
        $user = $request->user();

        // Authorization: Only the venue itself or an admin can update
        if (!($user instanceof Venue) || $user->id !== $venue->id) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'address' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'sometimes|integer|min:1',
            'business_type' => 'sometimes|string|in:bar,club,restaurant,cafe,other',
            'operating_hours' => 'nullable|array',
            'commission_rate' => 'sometimes|numeric|min:0|max:100',
        ]);

        $venue->update($validated);

        return response()->json($venue);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Venue $venue)
    {
        $user = request()->user();

        if (!($user instanceof Venue) || $user->id !== $venue->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $venue->delete();

        return response()->json(['message' => 'Venue deleted successfully']);
    }
}
