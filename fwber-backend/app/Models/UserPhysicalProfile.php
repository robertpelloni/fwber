<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPhysicalProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'height_cm', 'body_type', 'hair_color', 'eye_color', 'skin_tone',
        'ethnicity', 'facial_hair', 'tattoos', 'piercings', 'dominant_hand', 'fitness_level',
        'clothing_style', 'avatar_prompt', 'avatar_image_url', 'avatar_status',
    ];
}
