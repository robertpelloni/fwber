<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $height_cm
 * @property string|null $body_type
 * @property string|null $hair_color
 * @property string|null $eye_color
 * @property string|null $skin_tone
 * @property string|null $ethnicity
 * @property string|null $facial_hair
 * @property string|null $tattoos
 * @property string|null $piercings
 * @property string|null $dominant_hand
 * @property string|null $fitness_level
 * @property string|null $clothing_style
 * @property string|null $avatar_prompt
 * @property string|null $avatar_image_url
 * @property string $avatar_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Database\Factories\UserPhysicalProfileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereAvatarImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereAvatarPrompt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereAvatarStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereBodyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereClothingStyle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereDominantHand($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereEthnicity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereEyeColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereFacialHair($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereFitnessLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereHairColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereHeightCm($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile wherePiercings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereSkinTone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereTattoos($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPhysicalProfile whereUserId($value)
 * @mixin \Eloquent
 */
class UserPhysicalProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'height_cm', 'body_type', 'hair_color', 'eye_color', 'skin_tone',
        'ethnicity', 'facial_hair', 'tattoos', 'piercings', 'dominant_hand', 'fitness_level',
        'clothing_style', 'avatar_prompt', 'avatar_image_url', 'avatar_status',
    ];
}
