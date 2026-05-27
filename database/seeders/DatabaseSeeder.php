<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\Meal;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Mirrors the sample data in the babifit API spec so every documented endpoint
 * returns its example payload. Dates are anchored to the current day (the spec
 * uses 2026-05-26 as "today") so the demo stays populated whenever it is run:
 * the latest scan is today, the previous scan is 8 days earlier, etc.
 *
 * Note: no WithoutModelEvents — the prefixed-id generation on create relies on
 * the model "creating" event firing.
 */
class DatabaseSeeder extends Seeder
{
    private Carbon $today;

    public function run(): void
    {
        $this->today = Carbon::today();

        $user = User::updateOrCreate(
            ['email' => 'demo@babifit.app'],
            [
                'name' => 'Ibrahim',
                'password' => Hash::make('password'),
                'initials' => 'IF',
                'age' => 38,
                'height' => 184,
                'gender' => 'M',
                'streak_days' => 6,
            ],
        );

        $user->target()->updateOrCreate(['user_id' => $user->id], [
            'calories' => 2100,
            'protein' => 165,
            'carbs' => 210,
            'fat' => 70,
            'burn' => 384,
            'weight' => 88,
            'fat_pct' => 18,
        ]);

        $this->seedScans($user);
        $this->seedMeals($user);
        $this->seedActivities($user);

        // Auguste Nehme + his six transcribed InBody scans (storage/app/auguste_data).
        $this->call(AugusteSeeder::class);
    }

    private function seedScans(User $user): void
    {
        $populationRanges = [
            'weight' => [20, 62.6, 80.9, 162],
            'muscle' => [12, 38.2, 45.1, 90],
            'fat' => [3, 11, 21, 42],
        ];

        $prevDate = $this->today->copy()->subDays(8);
        $latestDate = $this->today->copy();

        // Earlier scan — 8 days before "today".
        Scan::updateOrCreate(['id' => 'scan-1'], [
            'user_id' => $user->id,
            'date' => $prevDate->toDateString(),
            'label' => $prevDate->format('j M'),
            'weight' => 101.7, 'muscle' => 38.7, 'fat' => 33.3, 'fat_pct' => 32.7,
            'bmi' => 29.8, 'health' => 63.8, 'bmr' => 1845.8, 'water' => 49.5,
            'visceral' => 15, 'waist_hip' => 0.99, 'protein' => 12.7, 'salt' => 5.5,
            'lean_mass' => 68.4, 'intra_water' => 30.4, 'extra_water' => 19.1,
            'abdominal_fat' => 148, 'subcut_fat' => 292, 'burn_rec' => 390,
            'ranges' => $populationRanges,
            'segments' => [
                'armR' => ['muscle' => 4.1, 'fat' => 2.4],
                'armL' => ['muscle' => 4.1, 'fat' => 2.4],
                'torso' => ['muscle' => 31.5, 'fat' => 17.9],
                'legR' => ['muscle' => 11.3, 'fat' => 4.4],
                'legL' => ['muscle' => 11.2, 'fat' => 4.4],
            ],
            'nutrition' => ['protein' => 'low', 'fat' => 'high', 'salt' => 'high'],
        ]);

        // Latest scan — "today". Matches GET /scans/{id} in the spec verbatim.
        Scan::updateOrCreate(['id' => 'scan-2'], [
            'user_id' => $user->id,
            'date' => $latestDate->toDateString(),
            'label' => $latestDate->format('j M'),
            'weight' => 100.0, 'muscle' => 38.6, 'fat' => 31.9, 'fat_pct' => 31.9,
            'bmi' => 29.6, 'health' => 65.1, 'bmr' => 1840.8, 'water' => 49.8,
            'visceral' => 14, 'waist_hip' => 0.98, 'protein' => 12.9, 'salt' => 5.4,
            'lean_mass' => 68.1, 'intra_water' => 30.6, 'extra_water' => 19.2,
            'abdominal_fat' => 140, 'subcut_fat' => 280, 'burn_rec' => 384,
            'ranges' => $populationRanges,
            'segments' => [
                'armR' => ['muscle' => 4.1, 'fat' => 2.3],
                'armL' => ['muscle' => 4.1, 'fat' => 2.3],
                'torso' => ['muscle' => 31.4, 'fat' => 17.3],
                'legR' => ['muscle' => 11.3, 'fat' => 4.2],
                'legL' => ['muscle' => 11.2, 'fat' => 4.2],
            ],
            'nutrition' => ['protein' => 'low', 'fat' => 'high', 'salt' => 'high'],
        ]);
    }

    private function seedMeals(User $user): void
    {
        $user->meals()->delete();

        $today = $this->today->toDateString();
        $yesterday = $this->today->copy()->subDay()->toDateString();

        // Today: totals 1070 kcal / 82P / 78C / 46F.
        $todayMeals = [
            ['m1', '08:14', 'Greek yogurt bowl', 340, 28, 32, 10],
            ['m2', '12:30', 'Chicken & rice', 430, 38, 40, 14],
            ['m3', '16:05', 'Protein shake', 300, 16, 6, 22],
        ];
        foreach ($todayMeals as [$id, $time, $name, $kcal, $p, $c, $f]) {
            Meal::create([
                'id' => $id, 'user_id' => $user->id, 'date' => $today,
                'time' => $time, 'name' => $name, 'kcal' => $kcal,
                'protein' => $p, 'carbs' => $c, 'fat' => $f,
            ]);
        }

        // Yesterday: 5 meals, 2040 kcal, 142g protein (summary card).
        $yesterdayMeals = [
            ['07:30', 'Oats & eggs', 420, 30, 45, 12],
            ['12:00', 'Chicken bowl', 560, 45, 55, 18],
            ['15:30', 'Greek yogurt', 180, 18, 14, 5],
            ['19:00', 'Salmon & veg', 600, 42, 30, 28],
            ['21:15', 'Casein shake', 280, 7, 18, 4],
        ];
        foreach ($yesterdayMeals as [$time, $name, $kcal, $p, $c, $f]) {
            Meal::create([
                'user_id' => $user->id, 'date' => $yesterday,
                'time' => $time, 'name' => $name, 'kcal' => $kcal,
                'protein' => $p, 'carbs' => $c, 'fat' => $f,
            ]);
        }
    }

    private function seedActivities(User $user): void
    {
        $user->activities()->delete();

        $day = fn (int $ago) => $this->today->copy()->subDays($ago)->toDateString();

        // Trailing week ending today: 6 sessions, 1980 kcal, 212 mins.
        // [id, days-ago, type, kcal, mins, distance]
        $thisWeek = [
            ['a1', 6, 'Walk', 312, 48, '4.1 km'],
            ['a2', 5, 'Jog', 280, 26, '3.0 km'],
            ['a3', 4, 'Strength', 220, 35, null],
            ['a4', 2, 'Cycling', 360, 35, '12.0 km'],
            ['a5', 1, 'Swim', 382, 27, '1.2 km'],
            ['a7', 0, 'Cycling', 426, 41, '14.2 km'],
        ];
        foreach ($thisWeek as [$id, $ago, $type, $kcal, $mins, $distance]) {
            Activity::create([
                'id' => $id, 'user_id' => $user->id, 'date' => $day($ago),
                'type' => $type, 'kcal' => $kcal, 'mins' => $mins, 'distance' => $distance,
            ]);
        }

        // Prior week: 1678 kcal -> vsLastWeekPct = +18.
        $lastWeek = [
            [13, 'Strength', 210, 30, null],
            [12, 'Walk', 300, 45, '3.9 km'],
            [11, 'Swim', 260, 22, '0.9 km'],
            [10, 'Cycling', 400, 38, '13.0 km'],
            [9, 'Walk', 218, 33, '2.8 km'],
            [8, 'Jog', 290, 27, '3.1 km'],
        ];
        foreach ($lastWeek as [$ago, $type, $kcal, $mins, $distance]) {
            Activity::create([
                'user_id' => $user->id, 'date' => $day($ago),
                'type' => $type, 'kcal' => $kcal, 'mins' => $mins, 'distance' => $distance,
            ]);
        }
    }
}
