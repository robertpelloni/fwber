<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'display_name',
        'date_of_birth',
        'gender',
        'pronouns',
        'sexual_orientation',
        'relationship_style',
        'bio',
        'location_latitude',
        'location_longitude',
        'location_description',
        'sti_status',
        'preferences',
        'avatar_url',
        'looking_for',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'location_latitude' => 'float',
        'location_longitude' => 'float',
        'sti_status' => 'array',
        'preferences' => 'array',
        'looking_for' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
