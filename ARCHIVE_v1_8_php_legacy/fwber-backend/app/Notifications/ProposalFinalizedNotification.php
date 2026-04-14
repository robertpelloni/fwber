<?php

namespace App\Notifications;

use App\Models\GovernanceProposal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class ProposalFinalizedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected GovernanceProposal $proposal,
        protected string $status,
        protected string $winnerOption
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'governance_finalized',
            'proposal_id' => $this->proposal->id,
            'title' => $this->proposal->title,
            'status' => $this->status,
            'winner' => $this->winnerOption,
            'message' => "Proposal '{$this->proposal->title}' has {$this->status}. Results: {$this->winnerOption}",
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'governance_finalized',
            'proposal_id' => $this->proposal->id,
            'title' => $this->proposal->title,
            'status' => $this->status,
            'message' => "Voting ended for '{$this->proposal->title}'. It was {$this->status}!",
        ]);
    }
}
