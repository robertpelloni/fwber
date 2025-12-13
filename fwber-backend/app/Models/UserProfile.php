<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'display_name',
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
        'penis_length_cm',
        'penis_girth_cm',
        'sti_status',
        'fetishes',
        'drinking_status',
        'breast_size',
        'tattoos',
        'piercings',
        'cannabis_status',
        'dietary_preferences',
        'zodiac_sign',
        'relationship_goals',
        'has_children',
        'wants_children',
        'has_pets',
        'languages',
        'interests',
        'pronouns',
        'sexual_orientation',
        'relationship_style',
        'hair_color',
        'eye_color',
        'skin_tone',
        'facial_hair',
        'dominant_hand',
        'fitness_level',
        'clothing_style',
        'avatar_prompt',
        'avatar_status',
        'preferences',
        'love_language',
        'personality_type',
        'political_views',
        'religion',
        'sleep_schedule',
        'social_media',
        'preferred_language',
        'is_travel_mode',
        'travel_latitude',
        'travel_longitude',
        'travel_location_name',
        'is_incognito',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_travel_mode' => 'boolean',
        'travel_latitude' => 'float',
        'travel_longitude' => 'float',
        'is_incognito' => 'boolean',
        'has_children' => 'boolean',
        'wants_children' => 'boolean',
        'has_pets' => 'boolean',
        'languages' => 'array',
        'interests' => 'array',
        'looking_for' => 'array',
        'interested_in' => 'array',
        'height_cm' => 'integer',
        'penis_length_cm' => 'decimal:2',
        'penis_girth_cm' => 'decimal:2',
        'sti_status' => 'array',
        'fetishes' => 'array',
        'tattoos' => 'array',
        'piercings' => 'array',
        'preferences' => 'array',
        'social_media' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
