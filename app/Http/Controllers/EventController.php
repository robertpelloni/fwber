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
        $query = Event::query();

        // Geospatial filter
        if ($request->has(['latitude', 'longitude', 'radius'])) {
            $lat = $request->latitude;
            $lon = $request->longitude;
            $radius = $request->radius; // km

            if (DB::getDriverName() === 'sqlite') {
                // Simple box approximation for testing
                $latRange = $radius / 111;
                $lonRange = $radius / (111 * cos(deg2rad($lat)));
                
                $query->whereBetween('latitude', [$lat - $latRange, $lat + $latRange])
                      ->whereBetween('longitude', [$lon - $lonRange, $lon + $lonRange]);
            } else {
                $query->select('*')
                    ->selectRaw(
                        '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
                        [$lat, $lon, $lat]
                    )
                    ->having('distance', '<', $radius)
                    ->orderBy('distance');
            }
        } else {
            $query->orderBy('starts_at', 'asc');
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', '!=', 'cancelled');
        }

        $events = $query->withCount('attendees')->paginate(20);

        return response()->json($events);
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
        $event = Event::with(['creator', 'attendees.user'])
            ->withCount('attendees')
            ->findOrFail($id);
            
        return response()->json($event);
    }

    public function myEvents(Request $request)
    {
        $user = Auth::user();
        
        // Events created by user OR events user is attending
        $events = Event::where('created_by_user_id', $user->id)
            ->orWhereHas('attendees', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('status', 'attending');
            })
            ->withCount('attendees')
            ->orderBy('starts_at', 'desc')
            ->paginate(20);

        return response()->json($events);
    }

    public function rsvp(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:attending,maybe,declined',
        ]);

        $event = Event::findOrFail($id);
        $user = Auth::user();

        // Check max attendees
        if ($request->status === 'attending' && $event->max_attendees) {
            $currentAttendees = $event->attendees()->where('status', 'attending')->count();
            // If user is already attending, don't count them again
            $isAlreadyAttending = $event->attendees()
                ->where('user_id', $user->id)
                ->where('status', 'attending')
                ->exists();
                
            if (!$isAlreadyAttending && $currentAttendees >= $event->max_attendees) {
                return response()->json(['message' => 'Event is full'], 400);
            }
        }

        $attendee = EventAttendee::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            ['status' => $request->status]
        );

        return response()->json($attendee);
    }
}
