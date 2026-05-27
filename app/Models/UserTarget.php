<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserTarget extends Model
{
    protected $fillable = [
        'user_id',
        'calories',
        'protein',
        'carbs',
        'fat',
        'burn',
        'weight',
        'fat_pct',
    ];

    protected function casts(): array
    {
        return [
            'calories' => 'integer',
            'protein' => 'integer',
            'carbs' => 'integer',
            'fat' => 'integer',
            'burn' => 'integer',
            'weight' => 'float',
            'fat_pct' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
