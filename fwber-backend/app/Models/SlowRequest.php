<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Builder;

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
        'ip',
        'user_agent',
        'payload',
    ];

    protected $casts = [
        'duration_ms' => 'float',
        'db_query_count' => 'integer',
        'memory_usage_kb' => 'integer',
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
