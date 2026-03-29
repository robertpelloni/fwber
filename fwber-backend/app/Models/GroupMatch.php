<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $group_id_1
 * @property int $group_id_2
 * @property string $status
 * @property int $initiated_by_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Group $group1
 * @property-read \App\Models\Group $group2
 * @property-read \App\Models\User $initiator
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMatch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMatch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMatch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMatch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMatch whereGroupId1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMatch whereGroupId2($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMatch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMatch whereInitiatedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMatch whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GroupMatch whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class GroupMatch extends Model
{
    protected $fillable = [
        'group_id_1',
        'group_id_2',
        'status',
        'initiated_by_user_id',
    ];

    public function group1()
    {
        return $this->belongsTo(Group::class, 'group_id_1');
    }

    public function group2()
    {
        return $this->belongsTo(Group::class, 'group_id_2');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'initiated_by_user_id');
    }
}
