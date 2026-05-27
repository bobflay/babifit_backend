<?php

namespace App\Services;

use App\Models\Scan;
use App\Models\User;
use Illuminate\Support\Carbon;

class ProgressService
{
    /** metric => [scan column, unit]. */
    private const METRICS = [
        'weight' => ['weight', 'kg'],
        'fatPct' => ['fat_pct', '%'],
        'muscle' => ['muscle', 'kg'],
        'health' => ['health', ''],
    ];

    public function metrics(): array
    {
        return array_keys(self::METRICS);
    }

    public function series(User $user, string $metric, Carbon $from, Carbon $to): array
    {
        [$column, $unit] = self::METRICS[$metric];

        $scans = $user->scans()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('date')
            ->get();

        $points = $scans->map(fn (Scan $s) => [
            'date' => Carbon::parse($s->date)->toDateString(),
            'value' => (float) $s->{$column},
        ])->values()->all();

        $delta = count($points) >= 2
            ? round(end($points)['value'] - $points[0]['value'], 1)
            : null;

        return [
            'metric' => $metric,
            'unit' => $unit,
            'target' => $this->targetFor($user, $metric),
            'points' => $points,
            'delta' => $delta,
        ];
    }

    public function compare(Scan $from, Scan $to): array
    {
        return [
            'from' => $this->summary($from),
            'to' => $this->summary($to),
            'deltas' => [
                'weight' => round((float) $to->weight - (float) $from->weight, 1),
                'fatPct' => round((float) $to->fat_pct - (float) $from->fat_pct, 1),
                'muscle' => round((float) $to->muscle - (float) $from->muscle, 1),
                'health' => round((float) $to->health - (float) $from->health, 1),
                'visceral' => (int) $to->visceral - (int) $from->visceral,
                'bmr' => (int) round((float) $to->bmr - (float) $from->bmr),
            ],
        ];
    }

    public function insights(User $user): ?array
    {
        $scans = $user->scans()->orderByDesc('date')->limit(2)->get();

        if ($scans->count() < 2) {
            return null;
        }

        [$to, $from] = [$scans[0], $scans[1]];

        $fatMass = round((float) $to->fat - (float) $from->fat, 1);
        $muscle = round((float) $to->muscle - (float) $from->muscle, 1);
        $proteinLow = ($to->nutrition['protein'] ?? null) === 'low';

        $headline = sprintf(
            'You %s %.1f kg of fat while keeping muscle within %.1f kg.',
            $fatMass <= 0 ? 'lost' : 'gained',
            abs($fatMass),
            abs($muscle),
        );

        $body = ($fatMass <= 0 && abs($muscle) <= 0.3)
            ? 'Clean recomposition.'
            : 'Body composition is shifting.';

        if ($proteinLow) {
            $body .= ' Protein intake is still flagged low — adding 30g/day could accelerate this trend.';
        }

        return [
            'title' => 'Pattern detected',
            'headline' => $headline,
            'body' => $body,
        ];
    }

    private function summary(Scan $scan): array
    {
        return [
            'id' => $scan->id,
            'date' => Carbon::parse($scan->date)->toDateString(),
            'weight' => (float) $scan->weight,
            'fatPct' => (float) $scan->fat_pct,
            'health' => (float) $scan->health,
        ];
    }

    private function targetFor(User $user, string $metric): ?float
    {
        return match ($metric) {
            'weight' => $user->target?->weight !== null ? (float) $user->target->weight : null,
            'fatPct' => $user->target?->fat_pct !== null ? (float) $user->target->fat_pct : null,
            default => null,
        };
    }
}
