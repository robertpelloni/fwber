<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class FwberUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'fwber_users';

    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'body_type',
        'ethnicity',
        'hair_color',
        'hair_length',
        'tattoos',
        'overall_looks',
        'intelligence',
        'bedroom_personality',
        'pubic_hair',
        'penis_size',
        'body_hair',
        'breast_size',
        'smoke_cigarettes',
        'light_drinker',
        'heavy_drinker',
        'smoke_marijuana',
        'psychedelics',
        'other_drugs',
        'have_warts',
        'have_herpes',
        'have_hepatitis',
        'have_other_sti',
        'have_hiv',
        'married_they_know',
        'married_secret',
        'poly',
        'disability',
        'postal_code',
        'country',
        'iso_country_code',
        'city',
        'state',
        'lat',
        'lon',
        'public_text',
        'private_text',
        'avatar_url',
        'verified',
    ];

    protected $hidden = [
        'password',
        'verify_hash',
        'remember_token',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_joined' => 'datetime',
        'date_last_signed_in' => 'datetime',
        'date_last_seen' => 'datetime',
        'email_verified_at' => 'datetime',
        'lat' => 'decimal:8',
        'lon' => 'decimal:8',
        'smoke_cigarettes' => 'boolean',
        'light_drinker' => 'boolean',
        'heavy_drinker' => 'boolean',
        'smoke_marijuana' => 'boolean',
        'psychedelics' => 'boolean',
        'other_drugs' => 'boolean',
        'have_warts' => 'boolean',
        'have_herpes' => 'boolean',
        'have_hepatitis' => 'boolean',
        'have_other_sti' => 'boolean',
        'have_hiv' => 'boolean',
        'married_they_know' => 'boolean',
        'married_secret' => 'boolean',
        'poly' => 'boolean',
        'disability' => 'boolean',
        'verified' => 'boolean',
    ];

    public function preferences()
    {
        return $this->hasOne(UserPreference::class, 'user_id');
    }

    public function matches()
    {
        return $this->hasMany(UserMatch::class, 'user1_id')
                    ->orWhere('user2_id', $this->id);
    }

    public function getAgeAttribute()
    {
        return $this->date_of_birth->age;
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getDisplayNameAttribute()
    {
        return $this->first_name;
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopeActive($query)
    {
        return $query->where('date_last_seen', '>=', now()->subDays(30));
    }

    public function scopeNearby($query, $lat, $lon, $radius = 50)
    {
        $milesPerDegree = 69;
        $degreeDiff = $radius / $milesPerDegree;
        
        return $query->whereBetween('lat', [$lat - $degreeDiff, $lat + $degreeDiff])
                    ->whereBetween('lon', [$lon - $degreeDiff, $lon + $degreeDiff]);
    }
}
