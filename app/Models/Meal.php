<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meal extends Model
{
    use HasPrefixedId;

    protected $fillable = [
        'id', 'user_id', 'date', 'time', 'name',
        'kcal', 'protein', 'carbs', 'fat', 'photo_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'kcal' => 'integer',
            'protein' => 'integer',
            'carbs' => 'integer',
            'fat' => 'integer',
        ];
    }

    public function idPrefix(): string
    {
        return 'meal_';
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
