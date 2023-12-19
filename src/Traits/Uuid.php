<?php

namespace Anil\FastApiCrud\Traits;

use Illuminate\Support\Str;

trait Uuid
{
    protected static function bootUuid()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
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
