<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRsvpRequest;
use App\Http\Requests\StoreEventRequest;
use App\Models\Event;
use App\Models\EventAttendee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::query()->with('creator')->withCount('attendees');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        } else {
            $query->where('status', '!=', 'cancelled');
        }

        $events = $query->orderBy('starts_at')->paginate(20);

        return response()->json($events);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = Event::create([
            ...$request->validated(),
            'created_by_user_id' => $request->user()->id,
            'status' => 'upcoming',
        ]);

        EventAttendee::firstOrCreate([
            'event_id' => $event->id,
            'user_id' => $request->user()->id,
        ], [
            'status' => 'attending',
            'paid' => true,
        ]);

        return response()->json($event->load('creator')->loadCount('attendees'), 201);
    }

    public function show(int $id): JsonResponse
    {
        $event = Event::with(['creator', 'attendees.user'])
            ->withCount('attendees')
            ->findOrFail($id);

        return response()->json($event);
    }

    public function myEvents(Request $request): JsonResponse
    {
        $user = $request->user();

        $events = Event::query()
            ->withCount('attendees')
            ->where('created_by_user_id', $user->id)
            ->orWhereHas('attendees', function ($query) use ($user): void {
                $query->where('user_id', $user->id)->where('status', 'attending');
            })
            ->orderByDesc('starts_at')
            ->paginate(20);

        return response()->json($events);
    }

    public function rsvp(EventRsvpRequest $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $user = $request->user();
        $status = $request->validated()['status'];

        $attendee = EventAttendee::updateOrCreate(
            [
                'event_id' => $event->id,
                'user_id' => $user->id,
            ],
            [
                'status' => $status,
                'paid' => (float) ($event->price ?? 0) <= 0,
                'payment_method' => $request->input('payment_method'),
                'transaction_id' => $request->input('payment_method_id'),
            ]
        );

        return response()->json([
            'message' => 'RSVP updated successfully.',
            'status' => $attendee->status,
        ]);
    }
}
