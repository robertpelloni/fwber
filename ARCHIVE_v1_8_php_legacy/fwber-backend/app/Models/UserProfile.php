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
 * @property string|null $voice_intro_url
 * @property bool $is_confessional_mode
 * @property \Illuminate\Support\Carbon|null $birthdate
 * @property string|null $gender
 * @property string|null $pronouns
 * @property string|null $sexual_orientation
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $location_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $is_verified
 * @property int $is_id_verified
 * @property string|null $zk_id_issuer
 * @property string|null $id_verified_at
 * @property string|null $verified_at
 * @property string|null $verification_photo_path
 * @property string|null $smoking_status
 * @property string|null $drinking_status
 * @property string|null $cannabis_status
 * @property string|null $dietary_preferences
 * @property string|null $zodiac_sign
 * @property string|null $relationship_goals
 * @property bool $has_children
 * @property bool $wants_children
 * @property bool $has_pets
 * @property array<array-key, mixed>|null $languages
 * @property array<array-key, mixed>|null $interests
 * @property array<array-key, mixed>|null $preferences
 * @property string|null $love_language
 * @property string|null $personality_type
 * @property string|null $political_views
 * @property string|null $religion
 * @property string|null $sleep_schedule
 * @property array<array-key, mixed>|null $social_media
 * @property int|null $height_cm
 * @property string|null $body_type
 * @property string|null $hair_color
 * @property string|null $eye_color
 * @property string|null $skin_tone
 * @property string|null $facial_hair
 * @property string|null $dominant_hand
 * @property string|null $fitness_level
 * @property string|null $clothing_style
 * @property string|null $ethnicity
 * @property string|null $occupation
 * @property string|null $education
 * @property string|null $relationship_status
 * @property string|null $relationship_style
 * @property array<array-key, mixed>|null $looking_for
 * @property array<array-key, mixed>|null $interested_in
 * @property numeric|null $penis_length_cm
 * @property numeric|null $penis_girth_cm
 * @property array<array-key, mixed>|null $sti_status
 * @property array<array-key, mixed>|null $fetishes
 * @property string|null $breast_size
 * @property array<array-key, mixed>|null $tattoos
 * @property array<array-key, mixed>|null $piercings
 * @property string|null $avatar_prompt
 * @property string $avatar_status
 * @property string|null $preferred_language
 * @property bool $is_travel_mode
 * @property bool $is_incognito
 * @property int|null $subscription_price
 * @property float|null $travel_latitude
 * @property float|null $travel_longitude
 * @property string|null $travel_location_name
 * @property string|null $current_emotion
 * @property bool $is_federated
 * @property string|null $emotion_updated_at
 * @property string $journal_visibility_default
 * @property int|null $journal_circle_group_id
 * @property-read \App\Models\User $user
 *
 * @method static \Database\Factories\UserProfileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereAvatarPrompt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereAvatarStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereBirthdate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereBodyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereBreastSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereCannabisStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereClothingStyle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereCurrentEmotion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereDietaryPreferences($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereDominantHand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereDrinkingStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereEducation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereEmotionUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereEthnicity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereEyeColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereFacialHair($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereFetishes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereFitnessLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereHairColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereHasChildren($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereHasPets($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereHeightCm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereIdVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereInterestedIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereInterests($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereIsConfessionalMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereIsFederated($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereIsIdVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereIsIncognito($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereIsTravelMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereIsVerified($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereLanguages($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereLocationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereLookingFor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereLoveLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereOccupation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile wherePenisGirthCm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile wherePenisLengthCm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile wherePersonalityType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile wherePiercings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile wherePoliticalViews($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile wherePreferences($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile wherePreferredLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile wherePronouns($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereRelationshipGoals($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereRelationshipStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereRelationshipStyle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereReligion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereSexualOrientation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereSkinTone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereSleepSchedule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereSmokingStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereSocialMedia($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereStiStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereSubscriptionPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereTattoos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereTravelLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereTravelLocationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereTravelLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereVerificationPhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereVoiceIntroUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereWantsChildren($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereZkIdIssuer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereZodiacSign($value)
 *
 * @mixin \Eloquent
 */
class UserProfile extends Model
{
    use HasFactory, SafelyHydratesAttributes;

    protected $appends = ['age'];

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
        'current_emotion',
        'emotion_updated_at',
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
        'is_confessional_mode',
        'is_federated',
        'voice_intro_url',
        'journal_visibility_default',
        'journal_circle_group_id',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
        'is_travel_mode' => 'boolean',
        'travel_latitude' => 'float',
        'travel_longitude' => 'float',
        'is_incognito' => 'boolean',
        'is_confessional_mode' => 'boolean',
        'is_federated' => 'boolean',
        'journal_circle_group_id' => 'integer',
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

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAgeAttribute(): ?int
    {
        return $this->birthdate?->age;
    }

    public function setInterestsAttribute($value): void
    {
        $this->attributes['interests'] = json_encode($this->normalizeInterestValues($value));
    }

    protected static function booted()
    {
        static::saved(function ($profile) {
            // Dispatch job to update vector embedding
            \App\Jobs\ProcessProfileEmbedding::dispatch($profile);

            // Dispatch out-of-band Rust microservice spatial index sync
            // Only trigger if we actually hold coordinates.
            if ($profile->location_lat && $profile->location_lng) {
                \App\Jobs\SyncLocationToGeoScreener::dispatch(
                    $profile->user_id,
                    (float) $profile->location_lat,
                    (float) $profile->location_lng
                );
            }
        });
    }

    /**
     * Keep freeform interest arrays canonical so matching can reason over them consistently.
     *
     * @param  mixed  $value
     * @return array<int, string>
     */
    private function normalizeInterestValues($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $normalized = array_map(function ($interest) {
            if (! is_string($interest)) {
                return null;
            }

            $interest = preg_replace('/\s+/', ' ', trim($interest));
            if ($interest === null || $interest === '') {
                return null;
            }

            return mb_strtolower($interest);
        }, $value);

        return array_values(array_unique(array_filter($normalized)));
    }
}
