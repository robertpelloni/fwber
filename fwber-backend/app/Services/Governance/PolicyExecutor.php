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
