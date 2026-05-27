<?php

namespace App\Services;

/**
 * MET-based energy estimates. kcal = MET × weight(kg) × hours.
 * Used by GET /activities/estimate and to size activity recommendations.
 */
class CalorieEstimator
{
    /** Approximate MET values keyed by activity type (case-insensitive). */
    private const MET = [
        'walk' => 4.3,
        'walking' => 4.3,
        'jog' => 8.5,
        'jogging' => 8.5,
        'run' => 9.8,
        'running' => 9.8,
        'cycling' => 7.0,
        'bike' => 7.0,
        'biking' => 7.0,
        'swim' => 7.0,
        'swimming' => 7.0,
        'swim / bike' => 7.0,
        'rowing' => 7.0,
        'hiit' => 8.0,
        'strength' => 5.0,
        'yoga' => 3.0,
        'elliptical' => 5.0,
    ];

    private const DEFAULT_MET = 5.0;

    private const DEFAULT_WEIGHT_KG = 80.0;

    public function metFor(string $type): float
    {
        $key = mb_strtolower(trim($type));

        if (isset(self::MET[$key])) {
            return self::MET[$key];
        }

        // Match on the first recognised word ("Cycling (road)" -> cycling).
        foreach (self::MET as $name => $met) {
            if (str_contains($key, $name)) {
                return $met;
            }
        }

        return self::DEFAULT_MET;
    }

    /** Estimated kcal burned for a session. */
    public function estimate(string $type, int $mins, ?float $weightKg = null): int
    {
        $weightKg ??= self::DEFAULT_WEIGHT_KG;

        return (int) round($this->metFor($type) * $weightKg * ($mins / 60));
    }

    /** Minutes needed to burn a target number of kcal. */
    public function minutesForKcal(string $type, int $targetKcal, ?float $weightKg = null): int
    {
        $weightKg ??= self::DEFAULT_WEIGHT_KG;
        $perMinute = $this->metFor($type) * $weightKg / 60;

        if ($perMinute <= 0) {
            return 0;
        }

        return (int) round($targetKcal / $perMinute);
    }
}
