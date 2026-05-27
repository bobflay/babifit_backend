<?php

namespace App\Services;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Assembles the Today dashboard in one shot so the client doesn't fan out to
 * /me, /scans, /meals and /activities separately.
 */
class TodayService
{
    public function __construct(
        private readonly ActivityRecommendationService $recommendations,
    ) {}

    public function build(User $user, Carbon $date): array
    {
        $latest = $user->scans()->orderByDesc('date')->orderByDesc('created_at')->first();
        $previous = $latest?->previous();
        $deltas = $latest?->deltas($previous);

        $eaten = (int) $user->meals()->whereDate('date', $date)->sum('kcal');
        $burned = (int) $user->activities()->whereDate('date', $date)->sum('kcal');
        $calorieTarget = (int) ($user->target?->calories ?? 0);
        $burnTarget = (int) ($user->target?->burn ?? 0);

        $recommendations = $this->recommendations->for($user);

        return [
            'date' => $date->toDateString(),
            'greeting' => 'Hi, '.$user->name,
            'name' => $user->name,
            'initials' => $user->resolveInitials(),
            'streakDays' => (int) $user->streak_days,
            'health' => $this->health($latest, $previous, $deltas),
            'body' => $this->body($latest, $deltas),
            'calories' => [
                'eaten' => $eaten,
                'target' => $calorieTarget,
                'burned' => $burned,
                'burnTarget' => $burnTarget,
                'toEatToday' => $calorieTarget - $eaten + $burned,
            ],
            'latestScanId' => $latest?->id,
            'recommendations' => [
                'burnKcal' => $recommendations['burnKcal'],
                'perWeek' => $recommendations['perWeek'],
                'items' => array_map(fn ($i) => [
                    'type' => $i['type'],
                    'mins' => $i['mins'],
                    'icon' => $i['icon'],
                ], $recommendations['items']),
            ],
        ];
    }

    private function health(?Scan $latest, ?Scan $previous, ?array $deltas): ?array
    {
        if (! $latest) {
            return null;
        }

        $deltaDays = $previous
            ? Carbon::parse($previous->date)->diffInDays(Carbon::parse($latest->date))
            : null;

        return [
            'score' => (float) $latest->health,
            'deltaDays' => $deltaDays !== null ? (int) $deltaDays : null,
            'delta' => $deltas['health'] ?? null,
            'visceralDelta' => $deltas['visceral'] ?? null,
            'note' => self::recompNote($deltas),
        ];
    }

    private function body(?Scan $latest, ?array $deltas): ?array
    {
        if (! $latest) {
            return null;
        }

        return [
            'weight' => ['value' => (float) $latest->weight, 'unit' => 'kg', 'delta' => $deltas['weight'] ?? null],
            'fatPct' => ['value' => (float) $latest->fat_pct, 'unit' => '%', 'delta' => $deltas['fatPct'] ?? null],
            'muscle' => ['value' => (float) $latest->muscle, 'unit' => 'kg', 'delta' => $deltas['muscle'] ?? null],
        ];
    }

    /** Short qualitative label for the trend between two scans. */
    public static function recompNote(?array $deltas): ?string
    {
        if (! $deltas || $deltas['weight'] === null) {
            return null;
        }

        $fat = $deltas['fatPct'] ?? 0;
        $muscle = $deltas['muscle'] ?? 0;

        return match (true) {
            $fat < 0 && $muscle >= -0.2 => 'Clean recomp',
            $fat < 0 && $muscle < -0.2 => 'Cutting',
            $fat > 0 && $muscle > 0.2 => 'Bulking',
            default => 'Maintaining',
        };
    }
}
