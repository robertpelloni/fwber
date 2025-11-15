<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * @OA\Post(
     *   path="/reports",
     *   tags={"Safety"},
     *   summary="Report a user",
     *   security={{"bearerAuth":{}}},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"accused_id", "reason"},
     *       @OA\Property(property="accused_id", type="integer"),
     *       @OA\Property(property="message_id", type="integer"),
     *       @OA\Property(property="reason", type="string", maxLength=100),
     *       @OA\Property(property="details", type="string", maxLength=2000)
     *     )
     *   ),
     *   @OA\Response(response=201, description="Report created"),
     *   @OA\Response(response=422, ref="#/components/schemas/ValidationError")
     * )
     */
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

    /**
     * @OA\Get(
     *   path="/reports",
     *   tags={"Safety"},
     *   summary="List reports (moderator only)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Response(response=200, description="Reports list (paginated)"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user || !$user->is_moderator) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $reports = Report::orderBy('created_at', 'desc')->paginate(50);
        return response()->json(['data' => $reports]);
    }

    /**
     * @OA\Put(
     *   path="/reports/{reportId}",
     *   tags={"Safety"},
     *   summary="Update report status (moderator only)",
     *   security={{"bearerAuth":{}}},
     *   @OA\Parameter(name="reportId", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"status"},
     *       @OA\Property(property="status", type="string", enum={"open", "reviewing", "resolved", "dismissed"}),
     *       @OA\Property(property="resolution_notes", type="string", maxLength=5000)
     *     )
     *   ),
    *   @OA\Response(response=200, description="Report updated"),
    *   @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
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
