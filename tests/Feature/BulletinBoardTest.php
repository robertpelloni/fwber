<?php

namespace Tests\Feature;

use App\Models\BulletinBoard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BulletinBoardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Register trig functions for SQLite if needed for other calculations
        if (DB::connection()->getDriverName() === 'sqlite') {
            $db = DB::connection()->getPdo();
            $db->sqliteCreateFunction('acos', 'acos', 1);
            $db->sqliteCreateFunction('cos', 'cos', 1);
            $db->sqliteCreateFunction('sin', 'sin', 1);
            $db->sqliteCreateFunction('radians', 'deg2rad', 1);
            $db->sqliteCreateFunction('sqrt', 'sqrt', 1);
            $db->sqliteCreateFunction('atan2', 'atan2', 2);
        }
    }

    public function test_can_create_bulletin_board()
    {
        $board = BulletinBoard::factory()->create([
            'name' => 'Local News',
            'center_lat' => 40.7128,
            'center_lng' => -74.0060,
            'radius_meters' => 2000,
        ]);

        $this->assertDatabaseHas('bulletin_boards', [
            'id' => $board->id,
            'name' => 'Local News',
        ]);
    }

    public function test_distance_calculation_logic()
    {
        $board = BulletinBoard::factory()->create([
            'center_lat' => 40.7128,
            'center_lng' => -74.0060,
            'radius_meters' => 1000,
        ]);

        // Point approx 111m away (0.001 deg lat)
        $nearbyLat = 40.7128 + 0.001;
        $nearbyLng = -74.0060;

        $distance = $board->distanceFrom($nearbyLat, $nearbyLng);
        
        // Allow some margin of error for float calculation
        $this->assertTrue($distance > 100 && $distance < 120);
        $this->assertTrue($board->containsPoint($nearbyLat, $nearbyLng));

        // Point far away
        $farLat = 41.0;
        $farLng = -74.0;

        $this->assertFalse($board->containsPoint($farLat, $farLng));
    }

    public function test_scope_near_location_skipped_on_sqlite()
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('ST_Distance_Sphere not supported in SQLite');
        }

        $board = BulletinBoard::factory()->create([
            'center_lat' => 40.7128,
            'center_lng' => -74.0060,
            'radius_meters' => 5000,
        ]);

        $nearby = BulletinBoard::nearLocation(40.7128, -74.0060, 10000)->get();
        $this->assertTrue($nearby->contains($board));
    }
}
