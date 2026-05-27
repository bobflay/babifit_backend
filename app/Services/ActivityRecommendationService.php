<?php

namespace App\Services;

use App\Models\Scan;
use App\Models\User;

/**
 * Builds the "to hit your burn target" suggestions surfaced on Today and the
 * Activity screen, derived from the user's latest scan.
 */
class ActivityRecommendationService
{
    /** Fixed suggestion menu (type => icon), mirroring the app design. */
    private const MENU = [
        ['type' => 'Walk', 'icon' => 'walk'],
        ['type' => 'Swim / Bike', 'icon' => 'bike'],
        ['type' => 'Jog', 'icon' => 'run'],
    ];

    private const DEFAULT_BURN = 384;

    private const DEFAULT_PER_WEEK = 3;

    public function __construct(private readonly CalorieEstimator $estimator) {}

    public function for(User $user): array
    {
        $latest = $user->scans()->orderByDesc('date')->first();

        $burnKcal = $this->burnTarget($user, $latest);
        $weight = $latest?->weight ?? $user->target?->weight ?? null;

        $items = array_map(function (array $entry) use ($burnKcal, $weight) {
            $mins = max(1, $this->estimator->minutesForKcal($entry['type'], $burnKcal, $weight));

            return [
                'type' => $entry['type'],
                'mins' => $mins,
                'icon' => $entry['icon'],
                'estKcal' => $this->estimator->estimate($entry['type'], $mins, $weight),
            ];
        }, self::MENU);

        return [
            'burnKcal' => $burnKcal,
            'perWeek' => self::DEFAULT_PER_WEEK,
            'items' => $items,
        ];
    }

    private function burnTarget(User $user, ?Scan $latest): int
    {
        return (int) ($latest?->burn_rec ?? $user->target?->burn ?? self::DEFAULT_BURN);
    }
}
