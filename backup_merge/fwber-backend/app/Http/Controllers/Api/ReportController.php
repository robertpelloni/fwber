<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReportRequest;
use App\Http\Requests\Api\UpdateReportRequest;
use App\Models\Report;
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
    public function store(StoreReportRequest $request)
    {
        $data = $request->validated();
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
    public function update(UpdateReportRequest $request, int $reportId)
    {
        $user = Auth::user();
        if (!$user || !$user->is_moderator) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $data = $request->validated();
        $report = Report::findOrFail($reportId);
        $report->status = $data['status'];
        $report->resolution_notes = $data['resolution_notes'] ?? $report->resolution_notes;
        $report->moderator_id = $user->id;
        $report->save();
        return response()->json(['data' => $report]);
    }
}
