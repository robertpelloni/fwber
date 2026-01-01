<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventInvitation;
use App\Models\EventAttendee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EventInvitationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/events/invitations",
     *     summary="Get my event invitations",
     *     tags={"Events"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of invitations",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/EventInvitation"))
     *     )
     * )
     */
    public function index(Request $request)
    {
        $invitations = EventInvitation::with(['event', 'inviter'])
            ->where('invitee_id', Auth::id())
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($invitations);
    }

    /**
     * @OA\Post(
     *     path="/api/events/{id}/invite",
     *     summary="Invite a user or group to an event",
     *     tags={"Events"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="user_id", type="integer", example=1, description="Optional if group_id is provided"),
     *             @OA\Property(property="group_id", type="integer", example=5, description="Optional if user_id is provided")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Invitation(s) sent",
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(ref="#/components/schemas/EventInvitation"),
     *                 @OA\Schema(
     *                     type="object",
     *                     @OA\Property(property="message", type="string"),
     *                     @OA\Property(property="count", type="integer")
     *                 )
     *             }
     *         )
     *     )
     * )
     */
    public function store(Request $request, $eventId)
    {
        $request->validate([
            'user_id' => 'required_without:group_id|exists:users,id',
            'group_id' => 'required_without:user_id|exists:groups,id',
        ]);

        $event = Event::findOrFail($eventId);
        $inviterId = Auth::id();

        if ($request->has('group_id')) {
            $group = \App\Models\Group::with('members')->findOrFail($request->group_id);
            $count = 0;

            foreach ($group->members as $member) {
                // Skip self (inviter)
                if ($member->user_id === $inviterId) {
                    continue;
                }

                $inviteeId = $member->user_id;

                // Check if already attending
                $isAttending = EventAttendee::where('event_id', $eventId)
                    ->where('user_id', $inviteeId)
                    ->exists();

                if ($isAttending) {
                    continue;
                }

                // Check if already invited
                $existingInvitation = EventInvitation::where('event_id', $eventId)
                    ->where('invitee_id', $inviteeId)
                    ->first();

                if ($existingInvitation) {
                    if ($existingInvitation->status !== 'pending') {
                        // Re-invite if previously declined/responded
                        $existingInvitation->update(['status' => 'pending', 'inviter_id' => $inviterId]);
                        $existingInvitation->load(['event', 'inviter', 'invitee']);
                        $existingInvitation->invitee->notify(new \App\Notifications\EventInvitationReceived($existingInvitation));
                        $count++;
                    }
                    continue;
                }

                $invitation = EventInvitation::create([
                    'event_id' => $eventId,
                    'inviter_id' => $inviterId,
                    'invitee_id' => $inviteeId,
                    'status' => 'pending',
                ]);

                $invitation->load(['event', 'inviter', 'invitee']);
                $invitation->invitee->notify(new \App\Notifications\EventInvitationReceived($invitation));
                $count++;
            }

            return response()->json([
                'message' => "Invitations sent to {$count} group members.",
                'count' => $count
            ], 201);
        }

        $inviteeId = $request->user_id;

        // Check if already attending
        $isAttending = EventAttendee::where('event_id', $eventId)
            ->where('user_id', $inviteeId)
            ->exists();

        if ($isAttending) {
            return response()->json(['message' => 'User is already attending this event.'], 400);
        }

        // Check if already invited
        $existingInvitation = EventInvitation::where('event_id', $eventId)
            ->where('invitee_id', $inviteeId)
            ->first();

        if ($existingInvitation) {
            if ($existingInvitation->status === 'pending') {
                return response()->json(['message' => 'User is already invited.'], 400);
            }
            // If declined previously, we might want to allow re-inviting, or just update status
            // For now, let's just update the existing one to pending
            $existingInvitation->update(['status' => 'pending', 'inviter_id' => $inviterId]);
            return response()->json($existingInvitation);
        }

        $invitation = EventInvitation::create([
            'event_id' => $eventId,
            'inviter_id' => $inviterId,
            'invitee_id' => $inviteeId,
            'status' => 'pending',
        ]);

        $invitation->load(['event', 'inviter', 'invitee']);
        $invitation->invitee->notify(new \App\Notifications\EventInvitationReceived($invitation));

        return response()->json($invitation, 201);
    }

    /**
     * @OA\Post(
     *     path="/api/events/invitations/{id}/respond",
     *     summary="Respond to an invitation",
     *     tags={"Events"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", enum={"accepted", "declined"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Response recorded"
     *     )
     * )
     */
    public function respond(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:accepted,declined',
        ]);

        $invitation = EventInvitation::where('invitee_id', Auth::id())->findOrFail($id);

        if ($invitation->status !== 'pending') {
            return response()->json(['message' => 'Invitation already responded to.'], 400);
        }

        DB::transaction(function () use ($invitation, $request) {
            $invitation->update(['status' => $request->status]);

            if ($request->status === 'accepted') {
                // Add to attendees
                EventAttendee::firstOrCreate([
                    'event_id' => $invitation->event_id,
                    'user_id' => $invitation->invitee_id,
                ], [
                    'status' => 'attending', // Default status
                ]);
            }
        });

        return response()->json(['message' => 'Invitation ' . $request->status]);
    }
}
