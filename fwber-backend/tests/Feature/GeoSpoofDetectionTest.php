<?php

namespace Tests\Feature;

use App\Models\GeoSpoofDetection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GeoSpoofDetectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_geo_spoof_detection()
    {
        $user = User::factory()->create();
        
        $detection = GeoSpoofDetection::factory()->create([
            'user_id' => $user->id,
            'suspicion_score' => 85,
        ]);

        $this->assertDatabaseHas('geo_spoof_detections', [
            'id' => $detection->id,
            'user_id' => $user->id,
            'suspicion_score' => 85,
        ]);
    }

    public function test_is_high_risk_logic()
    {
        $highRisk = GeoSpoofDetection::factory()->create(['suspicion_score' => 80]);
        $lowRisk = GeoSpoofDetection::factory()->create(['suspicion_score' => 50]);

        $this->assertTrue($highRisk->isHighRisk());
        $this->assertFalse($lowRisk->isHighRisk());
    }

    public function test_scopes()
    {
        $highRisk = GeoSpoofDetection::factory()->create(['suspicion_score' => 80, 'is_confirmed_spoof' => false]);
        $lowRisk = GeoSpoofDetection::factory()->create(['suspicion_score' => 50, 'is_confirmed_spoof' => true]);

        $highRiskResults = GeoSpoofDetection::highRisk()->get();
        $this->assertTrue($highRiskResults->contains($highRisk));
        $this->assertFalse($highRiskResults->contains($lowRisk));

        $unconfirmedResults = GeoSpoofDetection::unconfirmed()->get();
        $this->assertTrue($unconfirmedResults->contains($highRisk));
        $this->assertFalse($unconfirmedResults->contains($lowRisk));
    }
}
