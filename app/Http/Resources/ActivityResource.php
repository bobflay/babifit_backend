<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin \App\Models\Activity */
class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $date = Carbon::parse($this->date);

        return [
            'id' => $this->id,
            'day' => $date->isToday() ? 'Tod' : $date->format('D'),
            'date' => $date->format('j'),
            'type' => $this->type,
            'kcal' => (int) $this->kcal,
            'mins' => (int) $this->mins,
            'distance' => $this->distance,
        ];
    }
}
