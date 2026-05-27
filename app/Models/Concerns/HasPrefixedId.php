<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * String primary keys shaped like "scan_01H..." so ids stay opaque strings
 * for the client while remaining sortable. Seeders may set explicit ids
 * (e.g. "scan-1") — those are preserved.
 */
trait HasPrefixedId
{
    public static function bootHasPrefixedId(): void
    {
        static::creating(function ($model) {
            $key = $model->getKeyName();

            if (empty($model->{$key})) {
                $model->{$key} = $model->idPrefix().(string) Str::ulid();
            }
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    abstract public function idPrefix(): string;
}
