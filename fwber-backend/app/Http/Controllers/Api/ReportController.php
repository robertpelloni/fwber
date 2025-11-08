<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'accused_id' => 'required|integer|exists:users,id',
            'message_id' => 'nullable|integer|exists:messages,id',
            'reason' => 'required|string|max:100',
            'details' => 'nullable|string|max:2000'
        ]);
        if ($data['accused_id'] == Auth::id()) {
            return response()->json(['error' => 'Cannot report yourself'], 422);
        }
        $report = Report::create([
            'reporter_id' => Auth::id(),
            'accused_id' => $data['accused_id'],
            'message_id' => $data['message_id'] ?? null,
            'reason' => $data['reason'],
            'details' => $data['details'] ?? null,
            'status' => 'open'
        ]);
        return response()->json(['data' => $report], 201);
    }

    // List reports (moderator/admin only). For now simple role check on user->is_moderator flag.
    public function index()
    {
        $user = Auth::user();
        if (!$user || !$user->is_moderator) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $reports = Report::orderBy('created_at', 'desc')->paginate(50);
        return response()->json(['data' => $reports]);
    }

    // Update report status & optional resolution notes (moderator only)
    public function update(Request $request, int $reportId)
    {
        $user = Auth::user();
        if (!$user || !$user->is_moderator) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $data = $request->validate([
            'status' => 'required|string|in:open,reviewing,resolved,dismissed',
            'resolution_notes' => 'nullable|string|max:5000'
        ]);
        $report = Report::findOrFail($reportId);
        $report->status = $data['status'];
        $report->resolution_notes = $data['resolution_notes'] ?? $report->resolution_notes;
        $report->moderator_id = $user->id;
        $report->save();
        return response()->json(['data' => $report]);
    }
}
