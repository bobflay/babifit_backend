<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Meal */
class MealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'time' => $this->time,
            'name' => $this->name,
            'kcal' => (int) $this->kcal,
            'protein' => (int) $this->protein,
            'carbs' => (int) $this->carbs,
            'fat' => (int) $this->fat,
            'photoUrl' => $this->photo?->url(),
        ];
    }
}
