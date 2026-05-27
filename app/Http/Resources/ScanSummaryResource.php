<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Compact scan row for the history list / progress compare.
 *
 * @mixin \App\Models\Scan
 */
class ScanSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $deltas = $this->deltas();

        return [
            'id' => $this->id,
            'date' => Carbon::parse($this->date)->toDateString(),
            'label' => $this->resolveLabel(),
            'weight' => (float) $this->weight,
            'fatPct' => (float) $this->fat_pct,
            'muscle' => (float) $this->muscle,
            'bmi' => (float) $this->bmi,
            'health' => (float) $this->health,
            'delta' => [
                'weight' => $deltas['weight'],
                'fatPct' => $deltas['fatPct'],
                'health' => $deltas['health'],
            ],
        ];
    }
}
