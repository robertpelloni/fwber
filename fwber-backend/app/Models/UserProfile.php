<?php

namespace App\Models;

use App\Models\Traits\SafelyHydratesAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $display_name
 * @property string|null $bio
 * @property \Illuminate\Support\Carbon|null $birthdate
 * @property string|null $gender
 * @property string|null $pronouns
 * @property string|null $sexual_orientation
 * @property string|null $relationship_status
 * @property string|null $relationship_style
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $location_name
 * @property bool $is_travel_mode
 * @property float|null $travel_latitude
 * @property float|null $travel_longitude
 * @property string|null $travel_location_name
 * @property int|null $height_cm
 * @property string|null $body_type
 * @property string|null $hair_color
 * @property string|null $eye_color
 * @property string|null $ethnicity
 * @property string|null $occupation
 * @property string|null $education
 * @property string|null $smoking_status
 * @property string|null $drinking_status
 * @property string|null $cannabis_status
 * @property string|null $zodiac_sign
 * @property bool $has_children
 * @property bool $wants_children
 * @property bool $has_pets
 * @property array|null $interests
 * @property array|null $looking_for
 * @property array|null $interested_in
 * @property array|null $preferences
 * @property string|null $preferred_language
 * @property string|null $personality_type
 * @property bool $is_verified
 * @property bool $is_id_verified
 * @property \Illuminate\Support\Carbon|null $id_verified_at
 * @property string|null $zk_id_issuer
 * @property string|null $verification_photo_path
 * @property bool $is_incognito
 * @property bool $is_confessional_mode
 * @property string|null $voice_intro_url
 * @property array|null $sti_status
 * @property array|null $fetishes
 */
class UserProfile extends Model
{
    use HasFactory, SafelyHydratesAttributes;

    protected $fillable = [
        'user_id',
        'display_name',
        'bio',
        'birthdate',
        'gender',
        'pronouns',
        'sexual_orientation',
        'relationship_status',
        'relationship_style',
        'latitude',
        'longitude',
        'location_name',
        'is_travel_mode',
        'travel_latitude',
        'travel_longitude',
        'travel_location_name',
        'height_cm',
        'body_type',
        'hair_color',
        'eye_color',
        'ethnicity',
        'occupation',
        'education',
        'smoking_status',
        'drinking_status',
        'cannabis_status',
        'zodiac_sign',
        'has_children',
        'wants_children',
        'has_pets',
        'interests',
        'looking_for',
        'interested_in',
        'preferences',
        'preferred_language',
        'personality_type',
        'is_verified',
        'is_id_verified',
        'id_verified_at',
        'zk_id_issuer',
        'verification_photo_path',
        'is_incognito',
        'is_confessional_mode',
        'voice_intro_url',
        'sti_status',
        'fetishes',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'id_verified_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'travel_latitude' => 'float',
        'travel_longitude' => 'float',
        'is_travel_mode' => 'boolean',
        'has_children' => 'boolean',
        'wants_children' => 'boolean',
        'has_pets' => 'boolean',
        'is_verified' => 'boolean',
        'is_id_verified' => 'boolean',
        'is_incognito' => 'boolean',
        'is_confessional_mode' => 'boolean',
        'interests' => 'array',
        'looking_for' => 'array',
        'interested_in' => 'array',
        'preferences' => 'array',
        'sti_status' => 'array',
        'fetishes' => 'array',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
