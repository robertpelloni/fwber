<?php

namespace Tests\Feature;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PersonalAccessTokenTouchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\App\Http\Middleware\TrackUserActivity::class);
        $this->withoutMiddleware(\App\Http\Middleware\CheckDailyBonus::class);
    }

    public function test_bearer_auth_sets_last_used_at_on_first_request(): void
    {
        config(['auth.api_token_touch_interval_seconds' => 300]);

        $user = User::factory()->create();
        [$plainTextToken, $token] = $this->issueToken($user);

        Carbon::setTestNow(Carbon::parse('2026-04-01 21:50:00'));

        $this->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $user->id);

        $this->assertTrue($token->fresh()->last_used_at->equalTo(now()));
    }

    public function test_bearer_auth_does_not_touch_last_used_at_again_within_throttle_window(): void
    {
        config(['auth.api_token_touch_interval_seconds' => 300]);

        $user = User::factory()->create();
        [, $token] = $this->issueToken($user);

        Carbon::setTestNow(Carbon::parse('2026-04-01 21:50:00'));
        $token->forceFill(['last_used_at' => now()])->save();
        $firstTouch = $token->fresh()->last_used_at;

        Carbon::setTestNow($firstTouch->copy()->addSeconds(60));
        $token->forceFill(['last_used_at' => now()])->save();

        $this->assertTrue($token->fresh()->last_used_at->equalTo($firstTouch));
    }

    public function test_token_touch_updates_again_after_throttle_window(): void
    {
        config(['auth.api_token_touch_interval_seconds' => 300]);

        $user = User::factory()->create();
        [, $token] = $this->issueToken($user);

        Carbon::setTestNow(Carbon::parse('2026-04-01 21:50:00'));
        $token->forceFill(['last_used_at' => now()])->save();
        $firstTouch = $token->fresh()->last_used_at;

        Carbon::setTestNow($firstTouch->copy()->addSeconds(301));
        $token->forceFill(['last_used_at' => now()])->save();

        $this->assertTrue($token->fresh()->last_used_at->greaterThan($firstTouch));
    }

    /**
     * @return array{0: string, 1: PersonalAccessToken}
     */
    private function issueToken(User $user): array
    {
        $plainTextToken = $user->createToken('test-token')->plainTextToken;
        $token = $user->tokens()->latest('id')->first();

        $this->assertInstanceOf(PersonalAccessToken::class, $token);

        return [$plainTextToken, $token];
    }
}
