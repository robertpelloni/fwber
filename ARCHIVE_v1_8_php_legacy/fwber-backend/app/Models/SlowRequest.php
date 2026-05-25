<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $method
 * @property string $url
 * @property string|null $route_name
 * @property string|null $action
 * @property float $duration_ms
 * @property int|null $db_query_count
 * @property int|null $memory_usage_kb
 * @property array<array-key, mixed>|null $slowest_queries
 * @property string|null $ip
 * @property string|null $user_agent
 * @property array<array-key, mixed>|null $payload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $user
 * @property-read float $avg_queries
 * @property-read float $avg_memory
 * @property-read float $avg_duration
 * @property-read int $count
 * @property-read string $endpoint
 *
 * @method static Builder<static>|SlowRequest newModelQuery()
 * @method static Builder<static>|SlowRequest newQuery()
 * @method static Builder<static>|SlowRequest query()
 * @method static Builder<static>|SlowRequest whereAction($value)
 * @method static Builder<static>|SlowRequest whereCreatedAt($value)
 * @method static Builder<static>|SlowRequest whereDbQueryCount($value)
 * @method static Builder<static>|SlowRequest whereDurationMs($value)
 * @method static Builder<static>|SlowRequest whereId($value)
 * @method static Builder<static>|SlowRequest whereIp($value)
 * @method static Builder<static>|SlowRequest whereMemoryUsageKb($value)
 * @method static Builder<static>|SlowRequest whereMethod($value)
 * @method static Builder<static>|SlowRequest wherePayload($value)
 * @method static Builder<static>|SlowRequest whereRouteName($value)
 * @method static Builder<static>|SlowRequest whereSlowestQueries($value)
 * @method static Builder<static>|SlowRequest whereUpdatedAt($value)
 * @method static Builder<static>|SlowRequest whereUrl($value)
 * @method static Builder<static>|SlowRequest whereUserAgent($value)
 * @method static Builder<static>|SlowRequest whereUserId($value)
 *
 * @mixin \Eloquent
 */
class SlowRequest extends Model
{
    use Prunable;

    protected $fillable = [
        'user_id',
        'method',
        'url',
        'route_name',
        'action',
        'duration_ms',
        'db_query_count',
        'memory_usage_kb',
        'slowest_queries',
        'ip',
        'user_agent',
        'payload',
    ];

    protected $casts = [
        'duration_ms' => 'float',
        'db_query_count' => 'integer',
        'memory_usage_kb' => 'integer',
        'slowest_queries' => 'array',
        'payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the prunable model query.
     */
    public function prunable(): Builder
    {
        // Prune records older than 30 days
        return static::where('created_at', '<=', now()->subDays(30));
    }
}
