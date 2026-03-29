<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $domain
 * @property string|null $software
 * @property string|null $version
 * @property bool $is_blocked
 * @property \Illuminate\Support\Carbon $discovered_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FederatedServer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FederatedServer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FederatedServer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FederatedServer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FederatedServer whereDiscoveredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FederatedServer whereDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FederatedServer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FederatedServer whereIsBlocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FederatedServer whereSoftware($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FederatedServer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FederatedServer whereVersion($value)
 *
 * @mixin \Eloquent
 */
class FederatedServer extends Model
{
    protected $fillable = [
        'domain',
        'software',
        'version',
        'is_blocked',
        'discovered_at',
    ];

    protected $casts = [
        'is_blocked' => 'boolean',
        'discovered_at' => 'datetime',
    ];
}
