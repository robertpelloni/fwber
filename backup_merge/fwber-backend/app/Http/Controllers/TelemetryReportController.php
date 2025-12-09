<?php

namespace App\Http\Controllers;

use App\Models\TelemetryEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TelemetryReportController extends Controller
{
    private const SUPPORTED_RANGES = ['1d', '7d', '30d', '90d'];

    public function previewSummary(Request $request): JsonResponse
    {
        $range = $request->input('range', '7d');
        if (!in_array($range, self::SUPPORTED_RANGES, true)) {
            $range = '7d';
        }

        $window = $this->resolveWindow($range);
        $cacheKey = sprintf('telemetry:preview-summary:%s:%s', $range, now()->format('YmdHi'));

        $summary = Cache::remember($cacheKey, 60, function () use ($window) {
            return $this->buildSummary($window['start'], $window['end']);
        });

        $summary['window'] = [
            'start' => $window['start']->toIso8601String(),
            'end' => $window['end']->toIso8601String(),
            'range' => $range,
        ];

        return response()->json($summary);
    }

    private function buildSummary(Carbon $start, Carbon $end): array
    {
        $readyEvents = $this->fetchEvents('face_blur_preview_ready', $start, $end);
        $toggleEvents = $this->fetchEvents('face_blur_preview_toggled', $start, $end);
        $discardedEvents = $this->fetchEvents('face_blur_preview_discarded', $start, $end);
        $appliedUploads = $this->fetchEvents('face_blur_applied', $start, $end);
        $skippedUploads = $this->fetchEvents('face_blur_skipped_reason', $start, $end);

        $totalReady = $readyEvents->count();
        $blurAppliedFromPreview = $readyEvents->filter(fn ($event) => (bool) data_get($event->payload, 'blur_applied'))->count();
        $avgProcessing = $this->averageMetric($readyEvents->pluck('payload.processing_ms')->all());
        $warningBreakdown = $this->formatBreakdown($readyEvents, 'payload.warning');
        $uniqueUsers = $readyEvents->pluck('payload.user_id')->filter()->unique()->count();

        $toggleCount = $toggleEvents->count();
        $toggleUniquePreviews = $toggleEvents->pluck('payload.preview_id')->filter()->unique()->count();

        $discardCount = $discardedEvents->count();
        $discardReasons = $this->formatBreakdown($discardedEvents, 'payload.discard_reason');

        $skipReasons = $this->formatBreakdown($skippedUploads, 'payload.reason');

        return [
            'preview_ready' => [
                'total' => $totalReady,
                'blur_applied' => [
                    'count' => $blurAppliedFromPreview,
                    'rate' => $this->percent($blurAppliedFromPreview, $totalReady),
                ],
                'avg_processing_ms' => $avgProcessing,
                'warnings' => $warningBreakdown,
            ],
            'toggles' => [
                'events' => $toggleCount,
                'unique_previews' => $toggleUniquePreviews,
                'engagement_rate' => $this->percent($toggleUniquePreviews, $totalReady),
            ],
            'discarded' => [
                'events' => $discardCount,
                'rate' => $this->percent($discardCount, $totalReady),
                'reasons' => $discardReasons,
            ],
            'uploads' => [
                'applied' => $appliedUploads->count(),
                'skipped' => $skippedUploads->count(),
                'conversion_rate' => $this->percent($appliedUploads->count(), $totalReady),
                'skip_reasons' => $skipReasons,
            ],
            'totals' => [
                'unique_users' => $uniqueUsers,
            ],
        ];
    }

    private function fetchEvents(string $event, Carbon $start, Carbon $end): Collection
    {
        return TelemetryEvent::query()
            ->where('event', $event)
            ->whereBetween('recorded_at', [$start, $end])
            ->get();
    }

    private function resolveWindow(string $range): array
    {
        $end = now();
        switch ($range) {
            case '1d':
                $start = $end->copy()->subDay();
                break;
            case '30d':
                $start = $end->copy()->subDays(30);
                break;
            case '90d':
                $start = $end->copy()->subDays(90);
                break;
            case '7d':
            default:
                $start = $end->copy()->subDays(7);
                break;
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    private function percent(int $part, int $whole): ?float
    {
        if ($whole <= 0) {
            return null;
        }

        return round(($part / $whole) * 100, 1);
    }

    private function averageMetric(array $values): ?int
    {
        $numeric = collect($values)
            ->filter(fn ($value) => is_numeric($value))
            ->map(fn ($value) => (int) $value);

        if ($numeric->isEmpty()) {
            return null;
        }

        return (int) round($numeric->avg());
    }

    private function formatBreakdown(Collection $events, string $path, int $take = 5): array
    {
        return $events
            ->map(fn ($event) => data_get($event->payload, str_replace('payload.', '', $path)))
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (string) $value)
            ->countBy()
            ->sortDesc()
            ->take($take)
            ->map(function ($count, $value) {
                return [
                    'value' => $value,
                    'count' => $count,
                ];
            })
            ->values()
            ->all();
    }
}
