<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\GymLog */
class GymLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'time' => $this->time,
            'machine' => $this->machine,
            'muscles' => $this->muscles ?? [],
            'sets' => (int) $this->sets,
            'reps' => (int) $this->reps,
            'kcal' => (int) $this->kcal,
            'photoUrl' => $this->whenLoaded('photo', fn () => $this->photo?->url()),
        ];
    }
}
