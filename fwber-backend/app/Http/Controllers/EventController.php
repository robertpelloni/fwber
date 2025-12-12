<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventRsvpRequest;
use App\Http\Requests\StoreEventRequest;
use App\Models\Event;
use App\Models\EventAttendee;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\TokenDistributionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    protected $paymentGateway;
    protected $tokenService;

    public function __construct(PaymentGatewayInterface $paymentGateway, TokenDistributionService $tokenService)
    {
        $this->paymentGateway = $paymentGateway;
        $this->tokenService = $tokenService;
    }
    /**
     * @OA\Get(
     *     path="/api/events",
     *     summary="List nearby events",
     *     tags={"Events"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="latitude",
     *         in="query",
     *         description="User's latitude for geospatial search",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="longitude",
     *         in="query",
     *         description="User's longitude for geospatial search",
     *         required=false,
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="radius",
     *         in="query",
     *         description="Search radius in kilometers (default: 10)",
     *         required=false,
     *         @OA\Schema(type="number", format="float", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by event status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"upcoming", "ongoing", "completed", "cancelled"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of events with attendee counts",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Event")),
     *             @OA\Property(property="current_page", type="integer"),
     *             @OA\Property(property="per_page", type="integer"),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Generate cache key based on query parameters
        $cacheKey = config('optimization.cache_version') . ':events:index:' . md5(json_encode([
            'lat' => $request->latitude,
            'lon' => $request->longitude,
            'radius' => $request->radius,
            'status' => $request->status,
            'page' => $request->input('page', 1),
        ]));

        // Cache for 5 minutes with tagged caching
        $events = Cache::tags(['events'])->remember($cacheKey, 300, function () use ($request) {
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

            return $query->withCount('attendees')->paginate(20);
        });

        return response()->json($events);
    }

    /**
     * @OA\Post(
     *     path="/api/events",
     *     summary="Create a new event",
     *     tags={"Events"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "description", "location_name", "latitude", "longitude", "starts_at", "ends_at"},
     *             @OA\Property(property="title", type="string", maxLength=255),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="location_name", type="string"),
     *             @OA\Property(property="latitude", type="number", format="float"),
     *             @OA\Property(property="longitude", type="number", format="float"),
     *             @OA\Property(property="starts_at", type="string", format="date-time"),
     *             @OA\Property(property="ends_at", type="string", format="date-time"),
     *             @OA\Property(property="max_attendees", type="integer", nullable=true),
     *             @OA\Property(property="price", type="number", format="float", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Event created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Event")
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(StoreEventRequest $request)
    {
        $validated = $request->validated();

        $event = Event::create([
            ...$validated,
            'created_by_user_id' => Auth::id(),
            'status' => 'upcoming',
        ]);

        // Invalidate events cache
        Cache::tags(['events'])->flush();

        return response()->json($event, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/events/{id}",
     *     summary="Get event details",
     *     tags={"Events"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Event details with creator and attendees",
     *         @OA\JsonContent(ref="#/components/schemas/Event")
     *     ),
     *     @OA\Response(response=404, description="Event not found")
     * )
     */
    public function show($id)
    {
        $event = Event::with(['creator', 'attendees.user'])
            ->withCount('attendees')
            ->findOrFail($id);
            
        return response()->json($event);
    }

    /**
     * @OA\Get(
     *     path="/api/events/my",
     *     summary="Get user's created and attending events",
     *     tags={"Events"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of user's events",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Event"))
     *         )
     *     )
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/api/events/{id}/rsvp",
     *     summary="RSVP to an event",
     *     tags={"Events"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"attending", "maybe", "declined"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="RSVP updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="rsvp", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Event is full"),
     *     @OA\Response(response=404, description="Event not found")
     * )
     */
    public function rsvp(EventRsvpRequest $request, $id)
    {
        // Eager load attendees to avoid N+1 queries
        $event = Event::with('attendees')->findOrFail($id);
        $user = Auth::user();

        // Check max attendees
        if ($request->status === 'attending' && $event->max_attendees) {
            $currentAttendees = $event->attendees->where('status', 'attending')->count();
            // If user is already attending, don't count them again
            $isAlreadyAttending = $event->attendees
                ->where('user_id', $user->id)
                ->where('status', 'attending')
                ->isNotEmpty();
                
            if (!$isAlreadyAttending && $currentAttendees >= $event->max_attendees) {
                return response()->json(['message' => 'Event is full'], 400);
            }
        }

        $existingAttendee = $event->attendees->where('user_id', $user->id)->first();
        $alreadyPaid = $existingAttendee && $existingAttendee->paid;
        
        $paymentData = [];

        // Handle Payment Logic if switching to attending and not paid
        if ($request->status === 'attending' && $event->price > 0 && !$alreadyPaid) {
            $paymentMethod = $request->input('payment_method', 'stripe');
            $transactionId = null;

            if ($paymentMethod === 'token') {
                // Conversion: 1 USD = 10 Tokens
                $tokenCost = $event->price * 10;
                
                try {
                    $this->tokenService->spendTokens($user, $tokenCost, "Ticket for event: {$event->title}");
                    $transactionId = 'token_' . uniqid();
                } catch (\Exception $e) {
                    return response()->json(['error' => $e->getMessage()], 400);
                }
            } else {
                // Stripe
                $paymentMethodId = $request->input('payment_method_id');
                if (!$paymentMethodId) {
                        return response()->json(['error' => 'Payment method ID required for paid events'], 400);
                }

                try {
                    $result = $this->paymentGateway->charge($event->price, 'USD', $paymentMethodId);
                    if (!$result->success) {
                        return response()->json(['error' => 'Payment failed: ' . $result->message], 400);
                    }
                    $transactionId = $result->transactionId;
                    
                    // Log Payment
                    Payment::create([
                        'user_id' => $user->id,
                        'amount' => $event->price,
                        'currency' => 'USD',
                        'payment_gateway' => config('services.payment.driver', 'mock'),
                        'transaction_id' => $result->transactionId,
                        'status' => 'succeeded',
                        'description' => "Ticket for event: {$event->title}",
                        'metadata' => $result->data,
                    ]);
                } catch (\Exception $e) {
                    return response()->json(['error' => 'Payment error: ' . $e->getMessage()], 500);
                }
            }

            $paymentData = [
                'paid' => true,
                'payment_method' => $paymentMethod,
                'transaction_id' => $transactionId,
            ];
        }

        $attendee = EventAttendee::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $user->id],
            array_merge(['status' => $request->status], $paymentData)
        );

        // Invalidate events cache (attendee counts changed)
        Cache::tags(['events'])->flush();

        return response()->json($attendee);
    }
}
