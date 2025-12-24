<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PaymentRequestController extends Controller
{
    protected $pushService;

    public function __construct(PushNotificationService $pushService)
    {
        $this->pushService = $pushService;
    }

    /**
     * List payment requests (incoming and outgoing)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $incoming = PaymentRequest::where('payer_id', $user->id)
            ->with('requester:id,name,email') // Privacy: limit fields
            ->orderByDesc('created_at')
            ->get();

        $outgoing = PaymentRequest::where('requester_id', $user->id)
            ->with('payer:id,name,email')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'incoming' => $incoming,
            'outgoing' => $outgoing,
        ]);
    }

    /**
     * Create a new payment request
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'payer_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'note' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $payerId = $request->payer_id;

        if ($user->id == $payerId) {
            return response()->json(['error' => 'Cannot request money from yourself'], 400);
        }

        $paymentRequest = PaymentRequest::create([
            'requester_id' => $user->id,
            'payer_id' => $payerId,
            'amount' => $request->amount,
            'note' => $request->note,
            'status' => 'pending',
        ]);

        // Notify Payer
        $payer = User::find($payerId);
        $this->pushService->send(
            $payer,
            [
                'title' => 'New Payment Request',
                'body' => "{$user->name} requested {$request->amount} FWB: \"{$request->note}\"",
                'url' => '/wallet?tab=requests'
            ],
            'transaction'
        );

        return response()->json($paymentRequest, 201);
    }

    /**
     * Pay a request
     */
    public function pay(Request $request, int $id): JsonResponse
    {
        $paymentRequest = PaymentRequest::findOrFail($id);
        $payer = $request->user();

        if ($paymentRequest->payer_id !== $payer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($paymentRequest->status !== 'pending') {
            return response()->json(['error' => 'Request already processed'], 400);
        }

        $amount = $paymentRequest->amount;
        $requester = $paymentRequest->requester;

        // Check balance
        if ($payer->token_balance < $amount) {
            return response()->json(['error' => 'Insufficient balance'], 400);
        }

        try {
            DB::transaction(function () use ($payer, $requester, $amount, $paymentRequest) {
                // Atomic transfer
                $deducted = User::where('id', $payer->id)
                    ->where('token_balance', '>=', $amount)
                    ->decrement('token_balance', $amount);

                if (!$deducted) {
                    throw new \Exception('Insufficient balance');
                }

                $requester->increment('token_balance', $amount);

                // Log transactions
                $payer->tokenTransactions()->create([
                    'amount' => -$amount,
                    'type' => 'request_paid',
                    'description' => "Paid request to {$requester->name}",
                    'metadata' => ['request_id' => $paymentRequest->id],
                ]);

                $requester->tokenTransactions()->create([
                    'amount' => $amount,
                    'type' => 'request_received',
                    'description' => "Payment from {$payer->name}",
                    'metadata' => ['request_id' => $paymentRequest->id],
                ]);

                // Update request
                $paymentRequest->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            });

            // Notify Requester
            $this->pushService->send(
                $requester,
                [
                    'title' => 'Payment Received!',
                    'body' => "{$payer->name} paid your request of {$amount} FWB.",
                    'url' => '/wallet'
                ],
                'transaction'
            );

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['message' => 'Payment successful', 'request' => $paymentRequest->fresh()]);
    }

    /**
     * Cancel/Decline a request
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $paymentRequest = PaymentRequest::findOrFail($id);
        $user = $request->user();

        // Allow Requester to cancel OR Payer to decline
        if ($paymentRequest->requester_id !== $user->id && $paymentRequest->payer_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($paymentRequest->status !== 'pending') {
            return response()->json(['error' => 'Request already processed'], 400);
        }

        $status = ($user->id === $paymentRequest->payer_id) ? 'declined' : 'cancelled';

        $paymentRequest->update([
            'status' => $status
        ]);

        // Notify other party if declined
        if ($status === 'declined') {
            $this->pushService->send(
                $paymentRequest->requester,
                [
                    'title' => 'Request Declined',
                    'body' => "{$user->name} declined your payment request.",
                    'url' => '/wallet'
                ],
                'transaction'
            );
        }

        return response()->json(['message' => "Request {$status}", 'request' => $paymentRequest]);
    }
}
