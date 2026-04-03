<?php

namespace App\Jobs;

use App\Models\FederatedPost;
use App\Models\Following;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchRemoteOutbox implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $actorUri
    ) {}

    public function handle(): void
    {
        try {
            // 1. Resolve Actor
            $actorResponse = Http::timeout(5)
                ->withHeaders(['Accept' => 'application/activity+json'])
                ->get($this->actorUri);

            if (!$actorResponse->successful()) return;
            $actor = $actorResponse->json();
            
            $outboxUrl = $actor['outbox'] ?? null;
            if (!$outboxUrl) return;

            // 2. Fetch first page of Outbox
            // Mastodon usually requires a separate request for the first page
            $outboxResponse = Http::timeout(5)
                ->withHeaders(['Accept' => 'application/activity+json'])
                ->get($outboxUrl);
            
            if (!$outboxResponse->successful()) return;
            $outbox = $outboxResponse->json();

            $pageUrl = $outbox['first'] ?? null;
            if (is_array($pageUrl)) $pageUrl = $pageUrl['id'] ?? null;
            
            if (!$pageUrl) {
                // Outbox might be a single page
                $items = $outbox['orderedItems'] ?? $outbox['items'] ?? [];
            } else {
                $pageResponse = Http::timeout(5)
                    ->withHeaders(['Accept' => 'application/activity+json'])
                    ->get($pageUrl);
                if (!$pageResponse->successful()) return;
                $page = $pageResponse->json();
                $items = $page['orderedItems'] ?? $page['items'] ?? [];
            }

            // 3. Process items
            foreach ($items as $item) {
                if (($item['type'] ?? '') !== 'Create') continue;
                
                $object = $item['object'] ?? null;
                if (!$object || ($object['type'] ?? '') !== 'Note') continue;

                FederatedPost::updateOrCreate(
                    ['guid' => $object['id']],
                    [
                        'actor_uri' => $this->actorUri,
                        'actor_username' => $actor['preferredUsername'] ?? 'unknown',
                        'actor_domain' => parse_url($this->actorUri, PHP_URL_HOST),
                        'actor_avatar' => $actor['icon']['url'] ?? null,
                        'content' => $object['content'] ?? '',
                        'url' => $object['url'] ?? null,
                        'metadata' => json_encode($object),
                        'published_at' => isset($object['published']) ? date('Y-m-d H:i:s', strtotime($object['published'])) : now(),
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::warning("Outbox Sync: Failed for {$this->actorUri}: ".$e->getMessage());
        }
    }
}
