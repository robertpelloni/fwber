<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $latitude = $request->query('latitude');
        $longitude = $request->query('longitude');
        $radius = $request->query('radius', 50); // km

        $query = Event::query()->where('status', 'upcoming');

        if ($latitude && $longitude) {
            // Haversine formula
            $query->select('*')
                ->selectRaw(
                    '( 6371 * acos( cos( radians(?) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians(?) ) + sin( radians(?) ) * sin( radians( latitude ) ) ) ) AS distance',
                    [$latitude, $longitude, $latitude]
                )
                ->having('distance', '<', $radius)
                ->orderBy('distance');
        } else {
            $query->orderBy('starts_at');
        }

        return response()->json($query->withCount('attendees')->paginate(20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location_name' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'max_attendees' => 'nullable|integer|min:1',
            'price' => 'nullable|numeric|min:0',
        ]);

        $event = Event::create([
            ...$validated,
            'created_by_user_id' => Auth::id(),
            'status' => 'upcoming',
        ]);

        return response()->json($event, 201);
    }

    public function show($id)
    {
        $event = Event::with(['creator', 'attendees.user'])->withCount('attendees')->findOrFail($id);
        return response()->json($event);
    }

    public function rsvp(Request $request, $id)
    {
        $event = Event::findOrFail($id);
        $validated = $request->validate([
            'status' => 'required|in:attending,maybe,declined',
        ]);

        $attendee = EventAttendee::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => Auth::id()],
            ['status' => $validated['status']]
        );

        return response()->json($attendee);
    }

    public function myEvents()
    {
        $user = Auth::user();
        
        $attending = Event::whereHas('attendees', function ($q) use ($user) {
            $q->where('user_id', $user->id)->where('status', 'attending');
        })->withCount('attendees')->get();

        $created = Event::where('created_by_user_id', $user->id)->withCount('attendees')->get();

        return response()->json([
            'attending' => $attending,
            'created' => $created,
        ]);
    }
}
