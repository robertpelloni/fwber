<?php

namespace App\Providers;

use App\Services\MediaAnalysis\Drivers\AwsRekognitionDriver;
use App\Services\MediaAnalysis\Drivers\MockMediaAnalysisDriver;
use App\Services\MediaAnalysis\MediaAnalysisInterface;
use Illuminate\Support\ServiceProvider;

class MediaAnalysisServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(MediaAnalysisInterface::class, function ($app) {
            $driver = config('media_analysis.driver', 'mock');

            if ($driver === 'aws') {
                return new AwsRekognitionDriver();
            }

            return new MockMediaAnalysisDriver();
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
