<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function getSiteInfo()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'site_name' => 'FWBer',
                'site_tagline' => 'A completely new kind of Adult Match site',
                'site_description' => 'Finally, a better replacement for Craigslist and AdultFriendFinder!',
                'features' => [
                    'no_fees' => 'Totally free forever',
                    'no_bots' => 'No bots, spam, ads, or popups',
                    'inclusive' => 'Supports all types of lifestyles and preferences',
                    'matching' => 'Match by dozens of specific sexual interests and fetishes',
                    'avatars' => 'We make avatars for you - no public pictures needed',
                    'automatic' => 'No searching - automatic matches based on interests',
                    'privacy' => 'Privacy first - only show profiles to matches',
                    'open_source' => 'Open source website code'
                ],
                'supported_genders' => [
                    'male', 'female', 'mtf', 'ftm', 'cdmtf', 'cdftm', 'mf', 'mm', 'ff', 'group'
                ],
                'supported_orientations' => [
                    'straight', 'gay', 'bisexual', 'pansexual', 'asexual'
                ]
            ]
        ]);
    }
}
