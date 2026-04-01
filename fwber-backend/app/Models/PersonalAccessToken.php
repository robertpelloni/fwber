<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public function save(array $options = []): bool
    {
        if ($this->shouldSkipLastUsedAtTouch()) {
            return true;
        }

        return parent::save($options);
    }

    private function shouldSkipLastUsedAtTouch(): bool
    {
        $touchIntervalSeconds = (int) config('auth.api_token_touch_interval_seconds', 300);

        if ($touchIntervalSeconds <= 0 || ! $this->exists || ! $this->isDirty('last_used_at')) {
            return false;
        }

        $dirtyAttributes = $this->getDirty();

        if (count($dirtyAttributes) !== 1 || ! array_key_exists('last_used_at', $dirtyAttributes)) {
            return false;
        }

        $originalLastUsedAt = $this->getOriginal('last_used_at');

        if ($originalLastUsedAt === null) {
            return false;
        }

        $lastUsedAt = $this->asDateTime($originalLastUsedAt);
        $currentLastUsedAt = $this->asDateTime($this->getAttribute('last_used_at'));

        if (($currentLastUsedAt->getTimestamp() - $lastUsedAt->getTimestamp()) >= $touchIntervalSeconds) {
            return false;
        }

        $this->attributes['last_used_at'] = $originalLastUsedAt;
        $this->syncOriginalAttribute('last_used_at');
        $this->syncChanges();

        return true;
    }
}
