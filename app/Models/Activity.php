<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasPrefixedId;

    protected $fillable = [
        'id', 'user_id', 'date', 'type', 'kcal', 'mins', 'distance',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'kcal' => 'integer',
            'mins' => 'integer',
        ];
    }

    public function idPrefix(): string
    {
        return 'act_';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
