<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
