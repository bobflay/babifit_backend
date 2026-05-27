<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'initials' => $this->resolveInitials(),
            'age' => $this->age,
            'height' => $this->height,
            'gender' => $this->gender,
            'streakDays' => (int) $this->streak_days,
            'target' => $this->when(
                $this->target !== null,
                fn () => [
                    'calories' => (int) $this->target->calories,
                    'protein' => (int) $this->target->protein,
                    'carbs' => (int) $this->target->carbs,
                    'fat' => (int) $this->target->fat,
                    'burn' => (int) $this->target->burn,
                    'weight' => $this->target->weight !== null ? (float) $this->target->weight : null,
                    'fatPct' => $this->target->fat_pct !== null ? (float) $this->target->fat_pct : null,
                ],
            ),
        ];
    }
}
