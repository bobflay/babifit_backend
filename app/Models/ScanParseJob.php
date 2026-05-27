<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanParseJob extends Model
{
    use HasPrefixedId;

    protected $fillable = [
        'id', 'user_id', 'photo_id', 'status', 'confidence', 'draft', 'insight',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'float',
            'draft' => 'array',
        ];
    }

    public function idPrefix(): string
    {
        return 'job_';
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
