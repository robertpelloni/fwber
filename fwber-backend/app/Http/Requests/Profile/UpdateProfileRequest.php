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
            'date_of_birth' => 'sometimes|date|before_or_equal:' . now()->subYears(18)->toDateString() . '|after:1900-01-01',
            'gender' => 'sometimes|string|in:male,female,non-binary,mtf,ftm,other,prefer-not-to-say',
            'pronouns' => 'sometimes|string|in:he/him,she/her,they/them,he/they,she/they,other,prefer-not-to-say',
            'sexual_orientation' => 'sometimes|string|in:straight,gay,lesbian,bisexual,pansexual,asexual,demisexual,queer,questioning,other,prefer-not-to-say',
            'relationship_style' => 'sometimes|string|in:monogamous,non-monogamous,polyamorous,open,swinger,other,prefer-not-to-say',
            'looking_for' => 'sometimes|array',
            'looking_for.*' => 'string|in:friendship,dating,relationship,casual,marriage,networking',
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
