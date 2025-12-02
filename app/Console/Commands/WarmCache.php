<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\Group;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WarmCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up frequently accessed cache keys';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cache warming...');

        $this->warmGroups();
        $this->warmEvents();

        $this->info('Cache warming completed.');
    }

    protected function warmGroups()
    {
        $this->info('Warming groups cache...');
        
        $start = microtime(true);
        
        // Replicate GroupController::index logic for public groups
        $groups = Group::where('privacy', 'public')
            ->orWhere('visibility', 'visible')
            ->withCount('members')
            ->get();

        $cacheKey = config('optimization.cache_version') . ':groups:index:public';
        Cache::tags(['groups'])->put($cacheKey, $groups, 600);

        $duration = round((microtime(true) - $start) * 1000, 2);
        $this->info("Groups cache warmed in {$duration}ms. Count: {$groups->count()}");
    }

    protected function warmEvents()
    {
        $this->info('Warming events cache (global listing)...');

        $start = microtime(true);

        // Replicate EventController::index logic for default view (no geo, page 1)
        // Key generation must match controller exactly
        $cacheKey = config('optimization.cache_version') . ':events:index:' . md5(json_encode([
            'lat' => null,
            'lon' => null,
            'radius' => null, // Controller uses $request->radius which is null if not present
            'status' => null,
            'page' => 1,
        ]));

        $events = Event::query()
            ->where('status', '!=', 'cancelled')
            ->orderBy('starts_at', 'asc')
            ->withCount('attendees')
            ->paginate(20);

        Cache::tags(['events'])->put($cacheKey, $events, 300);

        $duration = round((microtime(true) - $start) * 1000, 2);
        $this->info("Events cache warmed in {$duration}ms. Count: {$events->count()}");
    }
}
