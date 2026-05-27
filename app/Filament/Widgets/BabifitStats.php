<?php

namespace App\Filament\Widgets;

use App\Models\Activity;
use App\Models\Meal;
use App\Models\Scan;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class BabifitStats extends StatsOverviewWidget
{
    protected ?string $heading = 'Overview';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Members', User::count())
                ->description('Registered accounts')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Meals logged', Meal::count())
                ->description($this->todayCount(Meal::class).' logged today')
                ->descriptionIcon('heroicon-m-cake')
                ->chart($this->last7Days(Meal::class))
                ->color('success'),

            Stat::make('Activities', Activity::count())
                ->description($this->todayCount(Activity::class).' logged today')
                ->descriptionIcon('heroicon-m-bolt')
                ->chart($this->last7Days(Activity::class))
                ->color('warning'),

            Stat::make('Body scans', Scan::count())
                ->description('InBody reports parsed')
                ->descriptionIcon('heroicon-m-scale')
                ->color('info'),
        ];
    }

    /** Rows logged today for a model with a `date` column. */
    protected function todayCount(string $model): int
    {
        return $model::whereDate('date', Carbon::today())->count();
    }

    /** Daily row counts for the trailing 7 days, oldest → newest (for sparklines). */
    protected function last7Days(string $model): array
    {
        $start = Carbon::today()->subDays(6);

        $counts = $model::query()
            ->where('date', '>=', $start->toDateString())
            ->get(['date'])
            ->groupBy(fn ($row) => Carbon::parse($row->date)->toDateString())
            ->map->count();

        $series = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $start->copy()->addDays($i)->toDateString();
            $series[] = (int) ($counts[$day] ?? 0);
        }

        return $series;
    }
}
