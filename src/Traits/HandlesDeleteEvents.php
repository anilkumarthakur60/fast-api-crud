<?php

namespace Anil\FastApiCrud\Traits;

use Illuminate\Support\Facades\DB;

trait HandlesDeleteEvents
{
    public static function bootDeleteEvent(): void
    {
        static::deleting(function ($model) {
            $table = $model->getTable();
            $columns = DB::select("SHOW INDEXES FROM `{$table}` WHERE NOT Non_unique AND Key_Name <> 'PRIMARY'");
            foreach ($columns as $column) {
                $model->{$column->Column_name} = $model->{$column->Column_name}.'_'.time();
                $model->save();
            }
        });
    }
}
