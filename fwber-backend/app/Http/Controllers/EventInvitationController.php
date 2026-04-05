<?php

namespace App\Http\Controllers;

use App\Http\Requests\Event\RespondEventInvitationRequest;
use App\Http\Requests\Event\StoreEventInvitationRequest;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\EventInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventInvitationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invitations = EventInvitation::with(['event', 'inviter'])
            ->where('invitee_id', $request->user()->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        return response()->json($invitations);
    }

    public function store(StoreEventInvitationRequest $request, int $eventId): JsonResponse
    {
        $event = Event::findOrFail($eventId);
        $inviteeId = (int) $request->validated()['user_id'];

        $invitation = EventInvitation::updateOrCreate(
            [
                'event_id' => $event->id,
                'invitee_id' => $inviteeId,
            ],
            [
                'inviter_id' => $request->user()->id,
                'status' => 'pending',
            ]
        );

        return response()->json($invitation->load(['event', 'inviter', 'invitee']), 201);
    }

    public function respond(RespondEventInvitationRequest $request, int $id): JsonResponse
    {
        $invitation = EventInvitation::where('invitee_id', $request->user()->id)->findOrFail($id);
        $status = $request->validated()['status'];

        $invitation->update(['status' => $status]);

        if ($status === 'accepted') {
            EventAttendee::firstOrCreate(
                [
                    'event_id' => $invitation->event_id,
                    'user_id' => $invitation->invitee_id,
                ],
                [
                    'status' => 'attending',
                    'paid' => false,
                ]
            );
        }

        return response()->json(['message' => 'Invitation response recorded.']);
    }
}
