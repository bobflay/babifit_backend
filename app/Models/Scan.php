<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Scan extends Model
{
    use HasPrefixedId;

    protected $fillable = [
        'id', 'user_id', 'photo_id', 'date', 'label',
        'weight', 'muscle', 'fat', 'fat_pct', 'bmi', 'health',
        'bmr', 'water', 'visceral', 'waist_hip', 'protein', 'salt',
        'lean_mass', 'intra_water', 'extra_water', 'abdominal_fat',
        'subcut_fat', 'burn_rec', 'ranges', 'segments', 'nutrition',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'weight' => 'float',
            'muscle' => 'float',
            'fat' => 'float',
            'fat_pct' => 'float',
            'bmi' => 'float',
            'health' => 'float',
            'bmr' => 'float',
            'water' => 'float',
            'visceral' => 'integer',
            'waist_hip' => 'float',
            'protein' => 'float',
            'salt' => 'float',
            'lean_mass' => 'float',
            'intra_water' => 'float',
            'extra_water' => 'float',
            'abdominal_fat' => 'integer',
            'subcut_fat' => 'integer',
            'burn_rec' => 'integer',
            'ranges' => 'array',
            'segments' => 'array',
            'nutrition' => 'array',
        ];
    }

    public function idPrefix(): string
    {
        return 'scan_';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** The original uploaded InBody report this scan was parsed from. */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }

    /**
     * Per-metric percentage change vs the previous scan (rise/fall indicator).
     * Each entry: { abs, pct, dir } — null map when there is no prior scan.
     */
    public function changes(?Scan $previous = null): array
    {
        $previous ??= $this->previous();
        if (! $previous) {
            return [];
        }

        $fields = ['weight', 'muscle', 'fat', 'fat_pct', 'bmi', 'health', 'visceral', 'bmr', 'water', 'protein'];
        $out = [];
        foreach ($fields as $field) {
            $curr = $this->{$field};
            $prev = $previous->{$field};
            if ($curr === null || $prev === null) {
                continue;
            }
            $abs = round((float) $curr - (float) $prev, 1);
            $pct = (float) $prev != 0.0 ? round((((float) $curr - (float) $prev) / abs((float) $prev)) * 100, 1) : 0.0;
            $out[$this->camel($field)] = [
                'abs' => $abs,
                'pct' => $pct,
                'dir' => $abs > 0 ? 'up' : ($abs < 0 ? 'down' : 'flat'),
            ];
        }

        return $out;
    }

    private function camel(string $field): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $field))));
    }

    /** The chronologically previous scan for the same user. */
    public function previous(): ?Scan
    {
        return static::query()
            ->where('user_id', $this->user_id)
            ->where('date', '<', $this->date)
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->first();
    }

    /** Diffs vs the previous scan; null fields when there is no prior scan. */
    public function deltas(?Scan $previous = null): array
    {
        $previous ??= $this->previous();

        $diff = fn (string $field, int $round = 1) => $previous
            ? round((float) $this->{$field} - (float) $previous->{$field}, $round)
            : null;

        return [
            'weight' => $diff('weight'),
            'fatPct' => $diff('fat_pct'),
            'muscle' => $diff('muscle'),
            'health' => $diff('health'),
            'visceral' => $previous ? (int) $this->visceral - (int) $previous->visceral : null,
            'bmr' => $previous ? (int) round((float) $this->bmr - (float) $previous->bmr) : null,
        ];
    }

    /** "26 May"-style label, derived from the date when not stored. */
    public function resolveLabel(): string
    {
        if ($this->label) {
            return $this->label;
        }

        return Carbon::parse($this->date)->format('j M');
    }
}
