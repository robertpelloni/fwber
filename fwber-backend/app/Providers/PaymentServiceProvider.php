<?php

namespace App\Providers;

use App\Services\Payment\MockPaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\StripePaymentGateway;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, function ($app) {
            $driver = config('services.payment.driver', 'mock');

            if ($driver === 'stripe') {
                return new StripePaymentGateway();
            }

            return new MockPaymentGateway();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
