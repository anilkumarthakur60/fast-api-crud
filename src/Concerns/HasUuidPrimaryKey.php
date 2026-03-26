<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Concerns;

use Illuminate\Support\Str;

/**
 * Automatically assign a UUID v4 as the primary key on model creation.
 *
 * Include this trait in any Eloquent model that should use UUID primary keys
 * instead of auto-incrementing integers.
 */
trait HasUuidPrimaryKey
{
    protected static function bootHasUuidPrimaryKey(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
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
}
