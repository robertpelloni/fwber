<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $name
 * @property string $topic
 * @property int $host_id
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $host
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AudioRoomParticipant> $participants
 * @property-read int|null $participants_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoom newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoom newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoom query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoom whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoom whereHostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoom whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoom whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoom whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoom whereTopic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AudioRoom whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class AudioRoom extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'topic',
        'host_id',
        'status',
    ];

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function participants()
    {
        return $this->hasMany(AudioRoomParticipant::class);
    }
}
