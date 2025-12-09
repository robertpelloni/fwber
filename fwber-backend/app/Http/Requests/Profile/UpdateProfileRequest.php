<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'display_name' => 'sometimes|string|max:50',
            'bio' => 'sometimes|string|max:500',
            'birthdate' => 'sometimes|date|before_or_equal:' . now()->subYears(18)->toDateString() . '|after:1900-01-01',
            'gender' => 'sometimes|string|in:male,female,non-binary,mtf,ftm,other,prefer-not-to-say',
            'pronouns' => 'sometimes|string|in:he/him,she/her,they/them,he/they,she/they,other,prefer-not-to-say',
            'sexual_orientation' => 'sometimes|string|in:straight,gay,lesbian,bisexual,pansexual,asexual,demisexual,queer,questioning,other,prefer-not-to-say',
            'relationship_style' => 'sometimes|string|in:monogamous,non-monogamous,polyamorous,open,swinger,other,prefer-not-to-say',
            
            // Physical
            'height_cm' => 'sometimes|integer|min:50|max:300',
            'body_type' => 'sometimes|string|in:slim,athletic,average,curvy,plus-size,muscular',
            'ethnicity' => 'sometimes|string',
            'breast_size' => 'sometimes|string',
            'tattoos' => 'sometimes|string',
            'piercings' => 'sometimes|string',
            'hair_color' => 'sometimes|string',
            'eye_color' => 'sometimes|string',
            'skin_tone' => 'sometimes|string',
            'facial_hair' => 'sometimes|string',
            'dominant_hand' => 'sometimes|string',
            'fitness_level' => 'sometimes|string',
            'clothing_style' => 'sometimes|string',
            
            // Intimate
            'penis_length_cm' => 'sometimes|numeric|min:0|max:50',
            'penis_girth_cm' => 'sometimes|numeric|min:0|max:50',
            'fetishes' => 'sometimes|array',
            'sti_status' => 'sometimes|array',
            
            // Lifestyle
            'occupation' => 'sometimes|string|max:100',
            'education' => 'sometimes|string',
            'relationship_status' => 'sometimes|string',
            'smoking_status' => 'sometimes|string',
            'drinking_status' => 'sometimes|string',
            'cannabis_status' => 'sometimes|string',
            'dietary_preferences' => 'sometimes|string',
            'zodiac_sign' => 'sometimes|string',
            'relationship_goals' => 'sometimes|string',
            'has_children' => 'sometimes|boolean',
            'wants_children' => 'sometimes|boolean',
            'has_pets' => 'sometimes|boolean',
            'languages' => 'sometimes|array',
            'interests' => 'sometimes|array',
            
            // Social & Personality
            'love_language' => 'sometimes|string|in:words_of_affirmation,acts_of_service,receiving_gifts,quality_time,physical_touch',
            'personality_type' => 'sometimes|string|size:4', // MBTI
            'political_views' => 'sometimes|string',
            'religion' => 'sometimes|string',
            'sleep_schedule' => 'sometimes|string',
            'social_media' => 'sometimes|array',
            
            'looking_for' => 'sometimes|array',
            "looking_for.*" => 'string|in:friendship,dating,relationship,casual,marriage,networking',
            'interested_in' => 'sometimes|array',
            
            'location.latitude' => 'sometimes|numeric|between:-90,90',
            'location.longitude' => 'sometimes|numeric|between:-180,180',
            'location.max_distance' => 'sometimes|integer|min:1|max:500',
            'location.city' => 'sometimes|string|max:100',
            'location.state' => 'sometimes|string|max:100',
            'preferences' => 'sometimes|array',
            'preferences.smoking' => 'sometimes|string|in:non-smoker,occasional,regular,social,trying-to-quit',
            'preferences.drinking' => 'sometimes|string|in:non-drinker,occasional,regular,social,sober',
            'preferences.exercise' => 'sometimes|string|in:daily,several-times-week,weekly,occasional,rarely,never',
            'preferences.diet' => 'sometimes|string|in:omnivore,vegetarian,vegan,pescatarian,keto,paleo,gluten-free,other',
            'preferences.pets' => 'sometimes|string|in:love-pets,have-pets,allergic,prefer-no-pets,neutral',
            'preferences.children' => 'sometimes|string|in:have-children,want-children,dont-want-children,maybe-someday,not-sure',
            'preferences.education' => 'sometimes|string|in:high-school,some-college,associates,bachelors,masters,phd,other',
            'preferences.age_range_min' => 'sometimes|integer|min:18|max:99',
            'preferences.age_range_max' => 'sometimes|integer|min:18|max:99',
            'preferences.body_type' => 'sometimes|string|in:slim,athletic,average,curvy,plus-size,muscular',
            'preferences.religion' => 'sometimes|string|in:christian,catholic,jewish,muslim,hindu,buddhist,agnostic,atheist,spiritual,other',
            'preferences.politics' => 'sometimes|string|in:liberal,moderate,conservative,apolitical,other',
            'preferences.hobbies' => 'sometimes|array',
            'preferences.music' => 'sometimes|array',
            'preferences.sports' => 'sometimes|array',
            'preferences.communication_style' => 'sometimes|string|in:direct,diplomatic,humorous,serious,casual,formal',
            'preferences.response_time' => 'sometimes|string|in:immediate,within-hour,within-day,when-convenient,no-rush',
            'preferences.meeting_preference' => 'sometimes|string|in:public-places,coffee-dates,dinner-dates,outdoor-activities,virtual-first,flexible',
            'email' => 'sometimes|email|max:255',
        ];
    }
}
