<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $event
 * @property array<array-key, mixed>|null $payload
 * @property \Illuminate\Support\Carbon $recorded_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelemetryEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelemetryEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelemetryEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelemetryEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelemetryEvent whereEvent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelemetryEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelemetryEvent wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelemetryEvent whereRecordedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelemetryEvent whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TelemetryEvent whereUserId($value)
 * @mixin \Eloquent
 */
class TelemetryEvent extends Model
{
    use HasFactory, Prunable;

    protected $fillable = [
        'user_id',
        'event',
        'payload',
        'recorded_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'recorded_at' => 'datetime',
    ];

    /**
     * Get the prunable model query.
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(30));
    }
}
