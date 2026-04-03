<?php

namespace Tests\Feature;

use App\Jobs\ProcessGovernanceProposals;
use App\Models\GovernanceProposal;
use App\Models\GovernanceVote;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PolicyExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_site_settings_on_passed_proposal()
    {
        $creator = User::factory()->create(['token_balance' => 1000]);
        
        // 1. Create a proposal with an execution payload to update daily bonus
        $proposal = GovernanceProposal::create([
            'creator_id' => $creator->id,
            'title' => 'Increase Daily Bonus',
            'description' => 'Should we increase the bonus to 25.0?',
            'category' => 'policy',
            'options' => ['Yes', 'No'],
            'execution_payload' => [
                'action' => 'update_site_setting',
                'params' => ['key' => 'daily_token_bonus', 'value' => '25.0']
            ],
            'starts_at' => now()->subDays(1),
            'expires_at' => now()->subMinute(),
            'status' => 'active',
        ]);

        // 2. Vote 'Yes' (Option 0)
        GovernanceVote::create([
            'user_id' => $creator->id,
            'governance_proposal_id' => $proposal->id,
            'option_index' => 0,
            'token_weight' => 1000,
        ]);

        // 3. Process the proposal
        (new ProcessGovernanceProposals())->handle();

        // 4. Assert setting was updated
        $this->assertDatabaseHas('site_settings', [
            'key' => 'daily_token_bonus',
            'value' => '25.0'
        ]);

        $proposal->refresh();
        $this->assertNotNull($proposal->executed_at);
        $this->assertEquals('passed', $proposal->status);
    }
}
