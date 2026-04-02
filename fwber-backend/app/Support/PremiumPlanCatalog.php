<?php

namespace App\Support;

class PremiumPlanCatalog
{
    public function defaultPlanId(): string
    {
        return array_key_first($this->plans()) ?? 'gold_monthly';
    }

    public function find(?string $planId): ?array
    {
        $resolvedPlanId = $planId ?: $this->defaultPlanId();
        $plan = $this->plans()[$resolvedPlanId] ?? null;

        if (! $plan) {
            return null;
        }

        return [
            'id' => $resolvedPlanId,
            ...$plan,
        ];
    }

    public function resolveOrDefault(?string $planId): array
    {
        return $this->find($planId) ?? $this->find($this->defaultPlanId()) ?? [
            'id' => 'gold_monthly',
            'name' => 'gold',
            'display_name' => 'Gold Monthly',
            'description' => 'Premium Subscription',
            'price_usd' => 19.99,
            'currency' => 'USD',
            'duration_days' => 30,
            'token_cost' => 200,
            'stripe_price' => 'price_premium_monthly',
            'interval' => 'month',
        ];
    }

    public function plans(): array
    {
        return config('premium.plans', []);
    }
}
