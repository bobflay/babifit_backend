<?php

namespace App\Models;

use App\Models\Concerns\HasPrefixedId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Photo extends Model
{
    use HasPrefixedId;

    protected $fillable = [
        'id', 'user_id', 'kind', 'disk', 'path', 'mime', 'size',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function idPrefix(): string
    {
        return 'ph_';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Public URL for the stored file. */
    public function url(): ?string
    {
        if (! $this->path) {
            return null;
        }

        return Storage::disk($this->disk ?? 'public')->url($this->path);
    }
}
