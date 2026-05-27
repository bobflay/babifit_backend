<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use App\Models\Meal;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CaloriesChart extends ChartWidget
{
    protected ?string $heading = 'Calories — last 14 days';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = Carbon::today()->subDays(13);

        $consumedByDay = Meal::query()
            ->where('date', '>=', $start->toDateString())
            ->get(['date', 'kcal'])
            ->groupBy(fn ($m) => Carbon::parse($m->date)->toDateString())
            ->map(fn ($group) => (int) $group->sum('kcal'));

        $burnedByDay = Activity::query()
            ->where('date', '>=', $start->toDateString())
            ->get(['date', 'kcal'])
            ->groupBy(fn ($a) => Carbon::parse($a->date)->toDateString())
            ->map(fn ($group) => (int) $group->sum('kcal'));

        $labels = [];
        $consumed = [];
        $burned = [];

        for ($i = 0; $i < 14; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();
            $labels[] = $day->format('M j');
            $consumed[] = $consumedByDay[$key] ?? 0;
            $burned[] = $burnedByDay[$key] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Consumed (kcal)',
                    'data' => $consumed,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Burned (kcal)',
                    'data' => $burned,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.12)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
