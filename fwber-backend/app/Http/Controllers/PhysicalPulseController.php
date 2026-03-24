<?php

namespace App\Http\Controllers;

use App\Models\PhysicalNode;
use App\Services\LocalPulseAnalyticsService;
use Illuminate\Http\Request;

class PhysicalPulseController extends Controller
{
    public function __construct(
        private readonly LocalPulseAnalyticsService $vibeService
    ) {}

    /**
     * Public endpoint for physical nodes (screens/sensors) to fetch live vibe data.
     * Authenticated via Node UUID.
     */
    public function node(string $uuid)
    {
        $node = PhysicalNode::where('node_uuid', $uuid)->firstOrFail();

        // Update node heartbeat
        $node->update([
            'is_online' => true,
            'last_heartbeat_at' => now(),
        ]);

        // Get Vibe around the physical node's location
        $radius = $node->config['radius'] ?? 1000; // Default 1km for physical venues
        $analysis = $this->vibeService->getNeighborhoodVibe($node->latitude, $node->longitude, $radius);

        return response()->json([
            'node_name' => $node->name,
            'venue_name' => $node->merchant->business_name ?? 'Local Venue',
            'analysis' => $analysis,
            'config' => $node->config,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
