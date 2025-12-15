<?php

namespace App\Http\Controllers;

use App\Services\AchievementService;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    protected $achievementService;

    public function __construct(AchievementService $achievementService)
    {
        $this->achievementService = $achievementService;
    }

    public function index(Request $request)
    {
        return response()->json([
            'achievements' => $this->achievementService->getProgress($request->user())
        ]);
    }
}
