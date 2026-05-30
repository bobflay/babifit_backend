<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GymLog extends Model
{
    use HasPrefixedId;

    protected $fillable = [
        'id', 'user_id', 'date', 'time', 'machine', 'muscles', 'sets', 'reps', 'kcal', 'photo_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'muscles' => 'array',
            'sets' => 'integer',
            'reps' => 'integer',
            'kcal' => 'integer',
        ];
    }

    public function idPrefix(): string
    {
        return 'gym_';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }
}
