<?php

namespace App\Jobs;

use App\Models\GovernanceProposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessGovernanceProposals implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $expiredProposals = GovernanceProposal::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expiredProposals as $proposal) {
            $this->finalizeProposal($proposal);
        }
    }

    protected function finalizeProposal(GovernanceProposal $proposal): void
    {
        // 1. Calculate Merkle Root of all votes for transparency
        $allVotes = DB::table('governance_votes')
            ->where('governance_proposal_id', $proposal->id)
            ->orderBy('id')
            ->get();

        $leaves = $allVotes->map(fn($v) => "{$v->user_id}:{$v->option_index}:{$v->token_weight}")->toArray();
        $merkleRoot = app(\App\Services\Governance\MerkleTreeService::class)->generateRoot($leaves);

        // 2. Sum up weighted votes
        $results = DB::table('governance_votes')
            ->where('governance_proposal_id', $proposal->id)
            ->select('option_index', DB::raw('SUM(token_weight) as total_weight'))
            ->groupBy('option_index')
            ->orderByDesc('total_weight')
            ->get();

        if ($results->isEmpty()) {
            $proposal->update(['status' => 'failed', 'merkle_root' => $merkleRoot]);
            return;
        }

        // The winner is the one with the highest total weight
        $winnerIndex = $results->first()->option_index;
        $winnerOption = $proposal->options[$winnerIndex] ?? 'Unknown';

        // 3. Determine if it passed
        $proposal->update([
            'status' => 'passed',
            'merkle_root' => $merkleRoot,
            'description' => $proposal->description . "\n\n--- GOVERNANCE RESULT ---\nPassed with winner: {$winnerOption}\nMerkle Root: {$merkleRoot}"
        ]);

        Log::info("Governance: Proposal {$proposal->id} ('{$proposal->title}') finalized as PASSED. Winner: {$winnerOption}");

        // 3. Notify Voters
        $voters = \App\Models\User::whereHas('governanceVotes', function($q) use ($proposal) {
            $q->where('governance_proposal_id', $proposal->id);
        })->get();

        foreach ($voters as $voter) {
            $voter->notify(new \App\Notifications\ProposalFinalizedNotification($proposal, $proposal->status, $winnerOption));
        }

        // 4. Trigger Policy Execution
        if ($proposal->status === 'passed' && !empty($proposal->execution_payload)) {
            Log::info("Governance: Triggering execution for proposal {$proposal->id}");
            $executor = new \App\Services\Governance\PolicyExecutor();
            $executor->execute($proposal);
        }
    }
}
