<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Concerns;

use Illuminate\Support\Facades\Schema;

/**
 * Anonymizes unique column values on soft delete to prevent unique constraint
 * violations when a new record is created with the same value.
 *
 * Each unique column gets _{timestamp} appended on delete.
 * Requires the model to use SoftDeletes.
 *
 * Configurable via fast-api.soft_delete.anonymize_unique_columns (default: true).
 */
trait HandlesDeleteEvents
{
    public static function bootHandlesDeleteEvents(): void
    {
        static::deleting(function (self $model): void {
            if (! config('fast-api.soft_delete.anonymize_unique_columns', true)) {
                return;
            }

            $table = $model->getTable();
            $indexes = Schema::getIndexes($table);
            $timestamp = time();
            $changed = false;

            foreach ($indexes as $index) {
                if (! $index['unique'] || $index['primary']) {
                    continue;
                }

                foreach ($index['columns'] as $column) {
                    if (isset($model->{$column})) {
                        $model->{$column} = $model->{$column}.'_'.$timestamp;
                        $changed = true;
                    }
                }
            }

            if ($changed) {
                $model->saveQuietly();
            }
        });
    }
}
