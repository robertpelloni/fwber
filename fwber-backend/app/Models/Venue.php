<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $business_type
 * @property string|null $description
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string $country
 * @property string|null $zip_code
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property string|null $phone
 * @property string|null $website
 * @property array<array-key, mixed>|null $operating_hours
 * @property int $max_capacity
 * @property numeric $commission_rate
 * @property string $verification_status
 * @property string $subscription_tier
 * @property \Illuminate\Support\Carbon|null $subscription_expires_at
 * @property bool $is_active
 * @property array<array-key, mixed>|null $features
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\VenueCheckin> $checkins
 * @property-read int|null $checkins_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\VenueFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereBusinessType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereCommissionRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereFeatures($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereMaxCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereOperatingHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereSubscriptionExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereSubscriptionTier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereVerificationStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereWebsite($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Venue whereZipCode($value)
 * @mixin \Eloquent
 */
class Venue extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'business_type',
        'description',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'latitude',
        'longitude',
        'phone',
        'website',
        'operating_hours',
        'max_capacity',
        'commission_rate',
        'verification_status',
        'subscription_tier',
        'subscription_expires_at',
        'is_active',
        'features',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'operating_hours' => 'array',
        'features' => 'array',
        'subscription_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'commission_rate' => 'decimal:2',
    ];

    public function checkins()
    {
        return $this->hasMany(VenueCheckin::class);
    }
}
