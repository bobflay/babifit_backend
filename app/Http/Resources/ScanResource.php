<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Full scan detail — GET /scans/{id} and the (unsaved) draft returned by the
 * parse flow.
 *
 * @mixin \App\Models\Scan
 */
class ScanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => Carbon::parse($this->date)->toDateString(),
            'label' => $this->resolveLabel(),
            'weight' => (float) $this->weight,
            'muscle' => (float) $this->muscle,
            'fat' => (float) $this->fat,
            'fatPct' => (float) $this->fat_pct,
            'bmi' => (float) $this->bmi,
            'health' => (float) $this->health,
            'bmr' => $this->bmr !== null ? (float) $this->bmr : null,
            'water' => $this->water !== null ? (float) $this->water : null,
            'visceral' => $this->visceral !== null ? (int) $this->visceral : null,
            'waistHip' => $this->waist_hip !== null ? (float) $this->waist_hip : null,
            'protein' => $this->protein !== null ? (float) $this->protein : null,
            'salt' => $this->salt !== null ? (float) $this->salt : null,
            'leanMass' => $this->lean_mass !== null ? (float) $this->lean_mass : null,
            'intraWater' => $this->intra_water !== null ? (float) $this->intra_water : null,
            'extraWater' => $this->extra_water !== null ? (float) $this->extra_water : null,
            'abdominalFat' => $this->abdominal_fat !== null ? (int) $this->abdominal_fat : null,
            'subcutFat' => $this->subcut_fat !== null ? (int) $this->subcut_fat : null,
            'burnRec' => $this->burn_rec !== null ? (int) $this->burn_rec : null,
            'ranges' => $this->ranges,
            'segments' => $this->segments,
            'nutrition' => $this->nutrition,
            'previousScanId' => $this->previous()?->id,
            // Original uploaded InBody report (photo/PDF); null for seeded/manual scans.
            'reportUrl' => $this->relationLoaded('photo')
                ? $this->photo?->url()
                : ($this->photo_id ? $this->photo?->url() : null),
            // Per-metric % change vs the previous scan (rise/fall indicators).
            'changes' => (object) $this->changes(),
        ];
    }
}
