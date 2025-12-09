<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'bio',
        'birthdate',
        'gender',
        'latitude',
        'longitude',
        'location_name',
        'height_cm',
        'body_type',
        'ethnicity',
        'occupation',
        'education',
        'relationship_status',
        'looking_for',
        'interested_in',
        'smoking_status',
        'drinking_status',
        'cannabis_status',
        'dietary_preferences',
        'zodiac_sign',
        'relationship_goals',
        'has_children',
        'wants_children',
        'has_pets',
        'languages',
        'interests',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
        'has_children' => 'boolean',
        'wants_children' => 'boolean',
        'has_pets' => 'boolean',
        'languages' => 'array',
        'interests' => 'array',
        'looking_for' => 'array',
        'interested_in' => 'array',
        'height_cm' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
