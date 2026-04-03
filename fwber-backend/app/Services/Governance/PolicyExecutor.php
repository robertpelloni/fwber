<?php

namespace App\Services\Governance;

use App\Models\GovernanceProposal;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Log;

class PolicyExecutor
{
    /**
     * Execute a passed proposal based on its payload.
     */
    public function execute(GovernanceProposal $proposal): bool
    {
        if ($proposal->status !== 'passed' || $proposal->executed_at) {
            return false;
        }

        $payload = $proposal->execution_payload;
        if (!$payload || !is_array($payload)) {
            return false;
        }

        $action = $payload['action'] ?? null;
        
        try {
            switch ($action) {
                case 'update_site_setting':
                    $this->updateSiteSetting($payload['params']);
                    break;

                case 'ban_actor':
                    $this->banActor($proposal, $payload['params']);
                    break;

                case 'ban_user':
                    $this->banUser($proposal, $payload['params']);
                    break;

                default:
                    Log::warning("Governance Executor: Unknown action '{$action}' for proposal {$proposal->id}");
                    return false;
            }

            $proposal->update(['executed_at' => now()]);
            return true;

        } catch (\Exception $e) {
            Log::error("Governance Executor: Execution failed for proposal {$proposal->id}: " . $e->getMessage());
            return false;
        }
    }

    protected function banActor(GovernanceProposal $proposal, array $params): void
    {
        $actorUri = $params['actor_uri'];
        $reason = $params['reason'] ?? "Community Vote: {$proposal->title}";

        \App\Models\GlobalBan::updateOrCreate(
            ['bannable_identifier' => $actorUri],
            [
                'type' => 'actor',
                'reason' => $reason,
                'proposal_id' => $proposal->id,
            ]
        );

        Log::info("Governance Executor: Federated actor '{$actorUri}' banned via community vote.");
    }

    protected function banUser(GovernanceProposal $proposal, array $params): void
    {
        $userId = $params['user_id'];
        $reason = $params['reason'] ?? "Community Vote: {$proposal->title}";

        \App\Models\GlobalBan::updateOrCreate(
            ['bannable_identifier' => (string) $userId],
            [
                'type' => 'user',
                'reason' => $reason,
                'proposal_id' => $proposal->id,
            ]
        );

        // Also mark user as inactive in local DB
        $user = \App\Models\User::find($userId);
        if ($user) {
            $user->update(['is_active' => false]);
        }

        Log::info("Governance Executor: Local user '{$userId}' banned via community vote.");
    }

    protected function updateSiteSetting(array $params): void
    {
        $key = $params['key'];
        $value = $params['value'];

        SiteSetting::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );

        Log::info("Governance Executor: Site setting '{$key}' updated to '{$value}'");
    }
}
