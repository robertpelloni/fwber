<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Payment;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PremiumController extends Controller
{
    protected $paymentGateway;

    public function __construct(PaymentGatewayInterface $paymentGateway)
    {
        $this->paymentGateway = $paymentGateway;
    }

    public function getWhoLikesYou(Request $request)
    {
        $user = $request->user();
        
        // Get users who liked the current user
        $likerIds = DB::table('match_actions')
            ->where('target_user_id', $user->id)
            ->where('action', 'like')
            ->pluck('user_id');
            
        $likers = User::with(['profile', 'photos'])->whereIn('id', $likerIds)->get();

        return response()->json($likers);
    }

    public function purchasePremium(Request $request)
    {
        $user = $request->user();
        $amount = 19.99; // Price for premium
        $currency = 'USD';
        $paymentMethodId = $request->input('payment_method_id', 'tok_visa'); // Default for mock

        try {
            $result = $this->paymentGateway->charge($amount, $currency, $paymentMethodId);

            if ($result->success) {
                // Log payment
                Payment::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'payment_gateway' => config('services.payment.driver', 'mock'),
                    'transaction_id' => $result->transactionId,
                    'status' => 'succeeded',
                    'description' => 'Premium Subscription',
                    'metadata' => $result->data,
                ]);

                // Grant premium
                $user->tier = 'gold';
                $user->tier_expires_at = Carbon::now()->addDays(30);
                $user->unlimited_swipes = true;
                $user->save();

                return response()->json([
                    'message' => 'Premium purchased successfully',
                    'tier' => $user->tier,
                    'expires_at' => $user->tier_expires_at
                ]);
            } else {
                 Payment::create([
                    'user_id' => $user->id,
                    'amount' => $amount,
                    'currency' => $currency,
                    'payment_gateway' => config('services.payment.driver', 'mock'),
                    'transaction_id' => null,
                    'status' => 'failed',
                    'description' => 'Premium Subscription Failed',
                    'metadata' => ['error' => $result->message],
                ]);

                return response()->json(['error' => 'Payment failed: ' . $result->message], 400);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Payment error: ' . $e->getMessage()], 500);
        }
    }

    public function getPremiumStatus(Request $request)
    {
        $user = $request->user();
        
        $isPremium = $user->tier === 'gold' && 
                     $user->tier_expires_at && 
                     Carbon::parse($user->tier_expires_at)->isFuture();

        return response()->json([
            'is_premium' => $isPremium,
            'tier' => $user->tier,
            'expires_at' => $user->tier_expires_at,
            'unlimited_swipes' => $user->unlimited_swipes
        ]);
    }
}
