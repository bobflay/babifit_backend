<?php

namespace App\Services;

use App\Models\Photo;

/**
 * Stand-in for the food-vision + nutrition-database pipeline behind
 * POST /meals/recognize. Returns a deterministic detection chosen from a small
 * catalog (keyed off the uploaded file) so the "snap a dish" flow is testable
 * without a live model.
 */
class FoodRecognitionService
{
    private const CATALOG = [
        [
            'name' => 'Grilled salmon bowl',
            'kcal' => 610,
            'macros' => ['protein' => 46, 'carbs' => 58, 'fat' => 22],
            'items' => [
                ['label' => 'Salmon', 'confidence' => 0.94, 'box' => [0.20, 0.30, 0.45, 0.55]],
                ['label' => 'Rice', 'confidence' => 0.88, 'box' => [0.55, 0.55, 0.78, 0.78]],
            ],
            'confidence' => 0.92,
        ],
        [
            'name' => 'Chicken Caesar salad',
            'kcal' => 430,
            'macros' => ['protein' => 38, 'carbs' => 18, 'fat' => 24],
            'items' => [
                ['label' => 'Grilled chicken', 'confidence' => 0.91, 'box' => [0.25, 0.28, 0.52, 0.6]],
                ['label' => 'Romaine', 'confidence' => 0.83, 'box' => [0.5, 0.45, 0.82, 0.8]],
            ],
            'confidence' => 0.89,
        ],
        [
            'name' => 'Greek yogurt bowl',
            'kcal' => 340,
            'macros' => ['protein' => 28, 'carbs' => 32, 'fat' => 10],
            'items' => [
                ['label' => 'Greek yogurt', 'confidence' => 0.93, 'box' => [0.22, 0.25, 0.6, 0.7]],
                ['label' => 'Berries', 'confidence' => 0.86, 'box' => [0.55, 0.3, 0.8, 0.55]],
            ],
            'confidence' => 0.9,
        ],
    ];

    /** @param  string|null  $fingerprint  e.g. the file hash, for deterministic variety. */
    public function recognize(?string $fingerprint = null): array
    {
        $index = $fingerprint
            ? hexdec(substr(md5($fingerprint), 0, 8)) % count(self::CATALOG)
            : 0;

        return self::CATALOG[$index];
    }
}
