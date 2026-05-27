<?php

namespace Database\Seeders;

use App\Models\Photo;
use App\Models\Scan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

/**
 * Auguste Nehme — six "Sunka / InBody" body-composition scans (10 Apr → 15 May
 * 2026), transcribed from the printouts in storage/app/auguste_data.
 *
 * Field mapping from the French sheet:
 *   muscle      = Muscle squelettique (skeletal muscle, ~37 kg) — NOT the
 *                 "Masse musculaire" line (~60 kg); this matches how the rest
 *                 of the app uses `muscle` vs `lean_mass`.
 *   lean_mass   = Masse maigre
 *   fat         = Graisse (kg)            fat_pct  = Pourcentage graisse
 *   water       = Quantité d'eau          intra/extra = Eau intra/extra-cellulaire
 *   visceral    = Degré de graisse abdominale
 *   waist_hip   = Rapport taille-hanche   bmr = taux métabolique basal
 *   abdominal_fat / subcut_fat = the two "Zone de graisse" areas (cm²)
 *   burn_rec    = Sport recommandé (kcal)
 *
 * Headline composition metrics are read straight off each sheet (high
 * confidence). The per-segment muscle/fat split is the small print at the
 * bottom of the report (best-effort OCR). Population `ranges` are the fixed
 * reference bands used elsewhere (App\Services\ScanParserService::RANGES).
 *
 * Idempotent: stable ids + updateOrCreate, and each source report image is
 * copied into the public disk so the scan's reportUrl resolves.
 *
 * Run standalone with:  php artisan db:seed --class=AugusteSeeder
 */
class AugusteSeeder extends Seeder
{
    /** Login credentials reported after seeding (auth is by email). */
    public const EMAIL = 'auguste@babifit.app';

    public const PASSWORD = 'Auguste#Fit2026';

    /** Population reference bands — not read from the sheet. */
    private const RANGES = [
        'weight' => [20, 62.6, 80.9, 162],
        'muscle' => [12, 38.2, 45.1, 90],
        'fat' => [3, 11, 21, 42],
    ];

    /** Nutritional verdict — identical on all six sheets. */
    private const NUTRITION = ['protein' => 'low', 'fat' => 'high', 'salt' => 'high'];

    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'Auguste Nehme',
                'password' => Hash::make(self::PASSWORD),
                'initials' => 'AN',
                'age' => 34,
                'height' => 178, // sheets vary 177.5–178.6 cm
                'gender' => 'M',
                'streak_days' => 6,
            ],
        );

        // Goal targets (not from the sheet) so the /me header + target math work.
        $user->target()->updateOrCreate(['user_id' => $user->id], [
            'calories' => 2000,
            'protein' => 180,
            'carbs' => 180,
            'fat' => 60,
            'burn' => 408,
            'weight' => 85,
            'fat_pct' => 22,
        ]);

        foreach ($this->scans() as $seq => $s) {
            $photo = $this->importReport($user, $seq + 1, $s['file']);
            $date = Carbon::parse($s['date']);

            Scan::updateOrCreate(['id' => 'scan_auguste_'.($seq + 1)], [
                'user_id' => $user->id,
                'photo_id' => $photo?->id,
                'date' => $date->toDateString(),
                'label' => $date->format('j M'),
                'weight' => $s['weight'],
                'muscle' => $s['muscle'],
                'fat' => $s['fat'],
                'fat_pct' => $s['fat_pct'],
                'bmi' => $s['bmi'],
                'health' => $s['health'],
                'bmr' => $s['bmr'],
                'water' => $s['water'],
                'visceral' => $s['visceral'],
                'waist_hip' => $s['waist_hip'],
                'protein' => $s['protein'],
                'salt' => $s['salt'],
                'lean_mass' => $s['lean_mass'],
                'intra_water' => $s['intra_water'],
                'extra_water' => $s['extra_water'],
                'abdominal_fat' => $s['abdominal_fat'],
                'subcut_fat' => $s['subcut_fat'],
                'burn_rec' => $s['burn_rec'],
                'ranges' => self::RANGES,
                'segments' => $s['segments'],
                'nutrition' => self::NUTRITION,
            ]);
        }
    }

    /**
     * Six scans, oldest first. Segment keys: armR, armL, torso, legR, legL —
     * each ['muscle' => kg, 'fat' => kg] ("Masse musculaire/graisseuse" per limb).
     *
     * @return list<array<string, mixed>>
     */
    private function scans(): array
    {
        return [
            [
                'file' => '2f6e5b65-c6a0-4f53-892e-e954d0b713d7.JPG',
                'date' => '2026-04-10',
                'weight' => 101.0, 'muscle' => 37.3, 'fat' => 34.9, 'fat_pct' => 34.6,
                'bmi' => 32.0, 'health' => 63.6, 'bmr' => 1795.7, 'water' => 48.4,
                'visceral' => 17, 'waist_hip' => 1.09, 'protein' => 12.2, 'salt' => 5.5,
                'lean_mass' => 66.1, 'intra_water' => 29.8, 'extra_water' => 18.6,
                'abdominal_fat' => 170, 'subcut_fat' => 340, 'burn_rec' => 408,
                'segments' => [
                    'armR' => ['muscle' => 4.2, 'fat' => 2.7],
                    'armL' => ['muscle' => 4.2, 'fat' => 2.7],
                    'torso' => ['muscle' => 31.7, 'fat' => 19.0],
                    'legR' => ['muscle' => 10.6, 'fat' => 4.4],
                    'legL' => ['muscle' => 10.4, 'fat' => 4.4],
                ],
            ],
            [
                'file' => '836d61ea-b852-4f0e-b1e9-411d6e2c7a81.JPG',
                'date' => '2026-04-17',
                'weight' => 100.1, 'muscle' => 37.1, 'fat' => 34.3, 'fat_pct' => 34.3,
                'bmi' => 31.8, 'health' => 63.9, 'bmr' => 1789.0, 'water' => 48.2,
                'visceral' => 16, 'waist_hip' => 1.01, 'protein' => 12.1, 'salt' => 5.5,
                'lean_mass' => 65.8, 'intra_water' => 29.6, 'extra_water' => 18.6,
                'abdominal_fat' => 160, 'subcut_fat' => 320, 'burn_rec' => 409,
                'segments' => [
                    'armR' => ['muscle' => 4.2, 'fat' => 2.6],
                    'armL' => ['muscle' => 4.2, 'fat' => 2.6],
                    'torso' => ['muscle' => 31.9, 'fat' => 18.9], // sheet digit ambiguous; set per trend
                    'legR' => ['muscle' => 10.5, 'fat' => 4.4],
                    'legL' => ['muscle' => 10.3, 'fat' => 4.4],
                ],
            ],
            [
                'file' => '22eb6abc-3e7b-49cd-b5bd-72f9caac77b8.JPG',
                'date' => '2026-04-24',
                'weight' => 101.4, 'muscle' => 37.7, 'fat' => 34.6, 'fat_pct' => 34.1,
                'bmi' => 31.8, 'health' => 64.3, 'bmr' => 1811.3, 'water' => 49.0,
                'visceral' => 16, 'waist_hip' => 1.09, 'protein' => 12.2, 'salt' => 5.6,
                'lean_mass' => 66.8, 'intra_water' => 30.1, 'extra_water' => 18.9,
                'abdominal_fat' => 160, 'subcut_fat' => 320, 'burn_rec' => 408,
                'segments' => [
                    'armR' => ['muscle' => 4.3, 'fat' => 2.7],
                    'armL' => ['muscle' => 4.3, 'fat' => 2.7],
                    'torso' => ['muscle' => 32.0, 'fat' => 19.0],
                    'legR' => ['muscle' => 10.7, 'fat' => 4.4],
                    'legL' => ['muscle' => 10.5, 'fat' => 4.4],
                ],
            ],
            [
                'file' => 'fa49d680-a016-4824-9ff8-6ca4d962865d.JPG',
                'date' => '2026-04-30',
                'weight' => 101.1, 'muscle' => 37.8, 'fat' => 34.3, 'fat_pct' => 33.9,
                'bmi' => 31.7, 'health' => 64.6, 'bmr' => 1812.6, 'water' => 49.0,
                'visceral' => 16, 'waist_hip' => 1.07, 'protein' => 12.2, 'salt' => 5.6,
                'lean_mass' => 66.8, 'intra_water' => 30.1, 'extra_water' => 18.9,
                'abdominal_fat' => 160, 'subcut_fat' => 320, 'burn_rec' => 408,
                'segments' => [
                    'armR' => ['muscle' => 4.3, 'fat' => 2.6],
                    'armL' => ['muscle' => 4.2, 'fat' => 2.6],
                    'torso' => ['muscle' => 32.0, 'fat' => 18.9],
                    'legR' => ['muscle' => 10.7, 'fat' => 4.4],
                    'legL' => ['muscle' => 10.5, 'fat' => 4.4],
                ],
            ],
            [
                'file' => '792bb690-0918-45e8-9fb3-10ece41acf58.JPG',
                'date' => '2026-05-08',
                'weight' => 100.3, 'muscle' => 37.2, 'fat' => 34.4, 'fat_pct' => 34.3,
                'bmi' => 31.8, 'health' => 64.0, 'bmr' => 1793.3, 'water' => 48.4,
                'visceral' => 16, 'waist_hip' => 1.04, 'protein' => 12.0, 'salt' => 5.5,
                'lean_mass' => 65.9, 'intra_water' => 29.8, 'extra_water' => 18.6,
                'abdominal_fat' => 160, 'subcut_fat' => 320, 'burn_rec' => 408,
                'segments' => [
                    'armR' => ['muscle' => 4.2, 'fat' => 2.6],
                    'armL' => ['muscle' => 4.2, 'fat' => 2.6],
                    'torso' => ['muscle' => 31.7, 'fat' => 18.9],
                    'legR' => ['muscle' => 10.6, 'fat' => 4.4],
                    'legL' => ['muscle' => 10.4, 'fat' => 4.4],
                ],
            ],
            [
                'file' => '4efa9f89-8ef7-4e17-b2a8-ed6b89cc30c1.JPG',
                'date' => '2026-05-15',
                'weight' => 100.8, 'muscle' => 37.3, 'fat' => 34.8, 'fat_pct' => 34.5,
                'bmi' => 31.9, 'health' => 63.7, 'bmr' => 1795.0, 'water' => 48.4,
                'visceral' => 16, 'waist_hip' => 1.09, 'protein' => 12.1, 'salt' => 5.5,
                'lean_mass' => 66.0, 'intra_water' => 29.8, 'extra_water' => 18.6,
                'abdominal_fat' => 160, 'subcut_fat' => 320, 'burn_rec' => 408,
                'segments' => [
                    'armR' => ['muscle' => 4.2, 'fat' => 2.7],
                    'armL' => ['muscle' => 4.2, 'fat' => 2.7],
                    'torso' => ['muscle' => 31.7, 'fat' => 19.1],
                    'legR' => ['muscle' => 10.6, 'fat' => 4.4],
                    'legL' => ['muscle' => 10.4, 'fat' => 4.4],
                ],
            ],
        ];
    }

    /** Copy an auguste_data report into the public disk and record a Photo. */
    private function importReport(User $user, int $seq, string $file): ?Photo
    {
        $source = storage_path('app/auguste_data/'.$file);
        if (! File::exists($source)) {
            return null;
        }

        $relPath = 'scans/auguste/'.$file;
        $dest = storage_path('app/public/'.$relPath);
        File::ensureDirectoryExists(dirname($dest));
        if (! File::exists($dest)) {
            File::copy($source, $dest);
        }

        return Photo::updateOrCreate(['id' => 'ph_auguste_'.$seq], [
            'user_id' => $user->id,
            'kind' => 'scan',
            'disk' => 'public',
            'path' => $relPath,
            'mime' => 'image/jpeg',
            'size' => File::size($source),
        ]);
    }
}
