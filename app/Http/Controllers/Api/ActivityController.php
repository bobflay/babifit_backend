<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Services\ActivityRecommendationService;
use App\Services\CalorieEstimator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class ActivityController extends Controller
{
    public function __construct(private readonly CalorieEstimator $estimator) {}

    /** GET /activities?week=YYYY-Www  OR  ?days=N (rolling N-day window) */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'week' => ['sometimes', 'regex:/^\d{4}-W\d{2}$/'],
            'days' => ['sometimes', 'integer', 'min:1', 'max:366'],
        ]);

        $user = $request->user();

        // A `days` param takes precedence: a rolling N-day window ending today,
        // compared against the immediately preceding N-day period. Otherwise
        // fall back to the (ISO or rolling 7-day) week window.
        if ($request->filled('days')) {
            $days = (int) $request->integer('days');
            $end = Carbon::today();
            $start = $end->copy()->subDays($days - 1);
            $prevEnd = $start->copy()->subDay();
            $prevStart = $prevEnd->copy()->subDays($days - 1);
        } else {
            [$start, $end] = $this->weekBounds($request->query('week'));
            $prevStart = $start->copy()->subWeek();
            $prevEnd = $end->copy()->subWeek();
        }

        $activities = $user->activities()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get();

        $kcal = (int) $activities->sum('kcal');
        $prevKcal = (int) $user->activities()
            ->whereBetween('date', [$prevStart->toDateString(), $prevEnd->toDateString()])
            ->sum('kcal');

        return response()->json([
            'weekTotals' => [
                'kcal' => $kcal,
                'mins' => (int) $activities->sum('mins'),
                'sessions' => $activities->count(),
                'vsLastWeekPct' => $this->pctChange($kcal, $prevKcal),
            ],
            'burnTarget' => (int) ($user->target?->burn ?? 0),
            'days' => ActivityResource::collection($activities)->resolve(),
        ]);
    }

    /** GET /activities/recent?limit=4 */
    public function recent(Request $request): JsonResponse
    {
        $limit = max(1, min((int) $request->integer('limit', 4), 50));

        $activities = $request->user()->activities()
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => ActivityResource::collection($activities)->resolve(),
        ]);
    }

    /** GET /activities/recommendations */
    public function recommendations(Request $request, ActivityRecommendationService $service): JsonResponse
    {
        return response()->json($service->for($request->user()->load('target')));
    }

    /** GET /activities/estimate?type=&mins= */
    public function estimate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string'],
            'mins' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);

        return response()->json([
            'kcal' => $this->estimator->estimate($data['type'], $data['mins'], $this->weight($request)),
        ]);
    }

    /** POST /activities — log an activity. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'type' => ['required', 'string', 'max:120'],
            'mins' => ['required', 'integer', 'min:1', 'max:1440'],
            'kcal' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'distance' => ['sometimes', 'nullable', 'string', 'max:60'],
        ]);

        $activity = $request->user()->activities()->create([
            'date' => $data['date'],
            'type' => $data['type'],
            'mins' => $data['mins'],
            'kcal' => $data['kcal'] ?? $this->estimator->estimate($data['type'], $data['mins'], $this->weight($request)),
            'distance' => $data['distance'] ?? null,
        ]);

        return response()->json(['id' => $activity->id], Response::HTTP_CREATED);
    }

    /** DELETE /activities/{id} */
    public function destroy(Request $request, string $activity): Response
    {
        $request->user()->activities()->findOrFail($activity)->delete();

        return response()->noContent();
    }

    /** Latest scan weight for energy estimates (null lets the estimator default). */
    private function weight(Request $request): ?float
    {
        $weight = $request->user()->scans()->orderByDesc('date')->value('weight');

        return $weight !== null ? (float) $weight : null;
    }

    /**
     * Week bounds for the chart. An explicit ISO week (YYYY-Www) maps to
     * Monday→Sunday; with no param we return the rolling 7-day window ending
     * today, which is what the week chart shows by default.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function weekBounds(?string $week): array
    {
        if ($week && preg_match('/^(\d{4})-W(\d{2})$/', $week, $m)) {
            $start = Carbon::now()->setISODate((int) $m[1], (int) $m[2])->startOfWeek(Carbon::MONDAY);

            return [$start, $start->copy()->endOfWeek(Carbon::SUNDAY)];
        }

        $end = Carbon::today();

        return [$end->copy()->subDays(6), $end];
    }

    private function pctChange(int $current, int $previous): int
    {
        if ($previous > 0) {
            return (int) round(($current - $previous) / $previous * 100);
        }

        return $current > 0 ? 100 : 0;
    }
}
