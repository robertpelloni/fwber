<?php

namespace App\Http\Controllers;

use App\Models\ViralContent;
use Illuminate\Http\Request;

class ViralContentController extends Controller
{
    /**
     * Retrieve viral content by its UUID.
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $content = ViralContent::where('id', $id)->firstOrFail();

        return response()->json([
            'type' => $content->type,
            'content' => $content->content,
            'created_at' => $content->created_at,
            // We might want to include some user info, but be careful about privacy.
            // For now, let's just return the content.
        ]);
    }
}
