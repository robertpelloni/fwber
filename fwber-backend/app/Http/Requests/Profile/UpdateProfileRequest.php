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
            'display_name' => 'sometimes|nullable|string|max:50',
            'bio' => 'sometimes|nullable|string|max:500',
            'birthdate' => 'sometimes|nullable|date|before_or_equal:' . now()->subYears(18)->toDateString() . '|after:1900-01-01',
            'gender' => 'sometimes|nullable|string|in:male,female,non-binary,mtf,ftm,other,prefer-not-to-say',
            'pronouns' => 'sometimes|nullable|string|in:he/him,she/her,they/them,he/they,she/they,other,prefer-not-to-say',
            'sexual_orientation' => 'sometimes|nullable|string|in:straight,gay,lesbian,bisexual,pansexual,asexual,demisexual,queer,questioning,other,prefer-not-to-say',
            'relationship_style' => 'sometimes|nullable|string|in:monogamous,non-monogamous,polyamorous,open,swinger,other,prefer-not-to-say',
            
            // Physical
            'height_cm' => 'sometimes|nullable|integer|min:50|max:300',
            'body_type' => 'sometimes|nullable|string|in:slim,athletic,average,curvy,plus-size,muscular',
            'ethnicity' => 'sometimes|nullable|string',
            'breast_size' => 'sometimes|nullable|string',
            'tattoos' => 'sometimes|nullable|string',
            'piercings' => 'sometimes|nullable|string',
            'hair_color' => 'sometimes|nullable|string',
            'eye_color' => 'sometimes|nullable|string',
            'skin_tone' => 'sometimes|nullable|string',
            'facial_hair' => 'sometimes|nullable|string',
            'dominant_hand' => 'sometimes|nullable|string',
            'fitness_level' => 'sometimes|nullable|string',
            'clothing_style' => 'sometimes|nullable|string',
            
            // Intimate
            'penis_length_cm' => 'sometimes|nullable|numeric|min:0|max:50',
            'penis_girth_cm' => 'sometimes|nullable|numeric|min:0|max:50',
            'fetishes' => 'sometimes|nullable|array',
            'sti_status' => 'sometimes|nullable|array',
            
            // Lifestyle
            'occupation' => 'sometimes|nullable|string|max:100',
            'education' => 'sometimes|nullable|string',
            'relationship_status' => 'sometimes|nullable|string',
            'smoking_status' => 'sometimes|nullable|string',
            'drinking_status' => 'sometimes|nullable|string',
            'cannabis_status' => 'sometimes|nullable|string',
            'dietary_preferences' => 'sometimes|nullable|string',
            'zodiac_sign' => 'sometimes|nullable|string',
            'relationship_goals' => 'sometimes|nullable|string',
            'has_children' => 'sometimes|nullable|boolean',
            'wants_children' => 'sometimes|nullable|boolean',
            'has_pets' => 'sometimes|nullable|boolean',
            'languages' => 'sometimes|nullable|array',
            'interests' => 'sometimes|nullable|array',
            
            // Social & Personality
            'love_language' => 'sometimes|nullable|string|in:words_of_affirmation,acts_of_service,receiving_gifts,quality_time,physical_touch',
            'personality_type' => 'sometimes|nullable|string|size:4', // MBTI
            'political_views' => 'sometimes|nullable|string',
            'religion' => 'sometimes|nullable|string',
            'sleep_schedule' => 'sometimes|nullable|string',
            'social_media' => 'sometimes|nullable|array',
            
            'looking_for' => 'sometimes|nullable|array',
            "looking_for.*" => 'string|in:friendship,friends,dating,relationship,casual,marriage,networking',
            'interested_in' => 'sometimes|nullable|array',
            
            'location' => 'sometimes|nullable|array',
            'location.latitude' => 'sometimes|numeric|between:-90,90',
            'location.longitude' => 'sometimes|numeric|between:-180,180',
            'location.max_distance' => 'sometimes|integer|min:1|max:500',
            'location.city' => 'sometimes|nullable|string|max:100',
            'location.state' => 'sometimes|nullable|string|max:100',

            // Travel Mode
            'is_travel_mode' => 'sometimes|boolean',
            'is_incognito' => 'sometimes|boolean',
            'subscription_price' => 'sometimes|integer|min:0',
            'travel_location' => 'sometimes|nullable|array',
            'travel_location.latitude' => 'sometimes|numeric|between:-90,90',
            'travel_location.longitude' => 'sometimes|numeric|between:-180,180',
            'travel_location.name' => 'sometimes|nullable|string|max:100',

            'preferences' => 'sometimes|nullable|array',
            'preferences.smoking' => 'sometimes|nullable|string|in:non-smoker,occasional,regular,social,trying-to-quit',
            'preferences.drinking' => 'sometimes|nullable|string|in:non-drinker,occasional,regular,social,sober',
            'preferences.exercise' => 'sometimes|nullable|string|in:daily,several-times-week,weekly,occasional,rarely,never',
            'preferences.diet' => 'sometimes|nullable|string|in:omnivore,vegetarian,vegan,pescatarian,keto,paleo,gluten-free,other',
            'preferences.pets' => 'sometimes|nullable|string|in:love-pets,have-pets,allergic,prefer-no-pets,neutral',
            'preferences.children' => 'sometimes|nullable|string|in:have-children,want-children,dont-want-children,maybe-someday,not-sure',
            'preferences.education' => 'sometimes|nullable|string|in:high-school,some-college,associates,bachelors,masters,phd,other',
            'preferences.age_range_min' => 'sometimes|nullable|integer|min:18|max:99',
            'preferences.age_range_max' => 'sometimes|nullable|integer|min:18|max:99',
            'preferences.body_type' => 'sometimes|nullable|string|in:slim,athletic,average,curvy,plus-size,muscular',
            'preferences.religion' => 'sometimes|nullable|string|in:christian,catholic,jewish,muslim,hindu,buddhist,agnostic,atheist,spiritual,other',
            'preferences.politics' => 'sometimes|nullable|string|in:liberal,moderate,conservative,apolitical,other',
            'preferences.hobbies' => 'sometimes|nullable|array',
            'preferences.music' => 'sometimes|nullable|array',
            'preferences.sports' => 'sometimes|nullable|array',
            'preferences.communication_style' => 'sometimes|nullable|string|in:direct,diplomatic,humorous,serious,casual,formal',
            'preferences.response_time' => 'sometimes|nullable|string|in:immediate,within-hour,within-day,when-convenient,no-rush',
            'preferences.meeting_preference' => 'sometimes|nullable|string|in:public-places,coffee-dates,dinner-dates,outdoor-activities,virtual-first,flexible',
            'email' => 'sometimes|nullable|email|max:255',
        ];
    }
}
