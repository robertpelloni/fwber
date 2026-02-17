<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\IpIntelligence\IpIntelligenceInterface;
use App\Services\IpIntelligence\Drivers\IpApiDriver;
use App\Services\IpIntelligence\Drivers\MockIpIntelligenceDriver;

class IpIntelligenceServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(IpIntelligenceInterface::class, function ($app) {
            $driver = config('ip_intelligence.driver', 'mock');

            return match ($driver) {
                'ip-api' => new IpApiDriver(),
                'ipinfo' => new \App\Services\IpIntelligence\Drivers\IpInfoDriver(),
                default => new MockIpIntelligenceDriver(),
            };
        });
    }

    public function boot()
    {
        //
    }
}
