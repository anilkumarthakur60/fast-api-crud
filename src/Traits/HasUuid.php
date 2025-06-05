<?php

namespace Anil\FastApiCrud\Traits;

use Illuminate\Support\Str;

/**
 * Trait HasUuid.
 */
trait HasUuid
{
    /**
     * Boot function from laravel.
     */
    protected static function bootHasUuid(): void
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Get the data type of the primary key.
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}
