<?php

namespace Tests\Feature;

use App\Jobs\ProcessGovernanceProposals;
use App\Models\GovernanceProposal;
use App\Models\GovernanceVote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GovernanceExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finalizes_expired_proposals_with_weighted_votes()
    {
        $creator = User::factory()->create();
        
        // 1. Create a proposal that expires NOW
        $proposal = GovernanceProposal::create([
            'creator_id' => $creator->id,
            'title' => 'Test Policy Change',
            'description' => 'Should we do X?',
            'category' => 'policy',
            'options' => ['Yes', 'No'],
            'starts_at' => now()->subDays(1),
            'expires_at' => now()->subMinute(),
            'status' => 'active',
        ]);

        // 2. Cast weighted votes
        $voter1 = User::factory()->create(['token_balance' => 500]);
        $voter2 = User::factory()->create(['token_balance' => 200]);

        GovernanceVote::create([
            'user_id' => $voter1->id,
            'governance_proposal_id' => $proposal->id,
            'option_index' => 0, // Yes
            'token_weight' => 500,
        ]);

        GovernanceVote::create([
            'user_id' => $voter2->id,
            'governance_proposal_id' => $proposal->id,
            'option_index' => 1, // No
            'token_weight' => 200,
        ]);

        // 3. Run the job
        (new ProcessGovernanceProposals())->handle();

        // 4. Assert results
        $proposal->refresh();
        $this->assertEquals('passed', $proposal->status);
        $this->assertStringContainsString('Passed with winner: Yes', $proposal->description);
    }

    public function test_it_ignores_active_proposals()
    {
        $creator = User::factory()->create();
        
        $proposal = GovernanceProposal::create([
            'creator_id' => $creator->id,
            'title' => 'Future Policy',
            'description' => 'Should we do Y?',
            'category' => 'policy',
            'options' => ['Yes', 'No'],
            'starts_at' => now(),
            'expires_at' => now()->addDays(5),
            'status' => 'active',
        ]);

        (new ProcessGovernanceProposals())->handle();

        $proposal->refresh();
        $this->assertEquals('active', $proposal->status);
    }
}
