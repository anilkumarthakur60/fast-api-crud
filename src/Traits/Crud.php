<?php

namespace Anil\FastApiCrud\Traits;

trait Crud
{
    public static function initializer(bool $orderBy = true)
    {
        $request = request();
        $filters = json_decode($request->query('filters'), true);
        if (method_exists(static::class, 'initializeModel')) {
            $model = static::initializeModel();
        } else {
            $model = static::where((new static())->getTable() . '.id', '>', 0);
        }
        foreach (collect($filters) as $filter => $value) {
            if (isset($value) && method_exists(static::class, 'scope' . ucfirst($filter))) {
                $model->$filter($value);
            }
        }
        $sortBy = (string) $request->query('sortBy', 'id');
        $desc = $request->boolean('descending', true);
        if ($orderBy) {
            if ($sortBy && method_exists(static::class, 'sortByDefaults')) {
                $sortByDefaults = static::sortByDefaults();
                $sortBy = $sortByDefaults['sortBy'];
                $desc = $sortByDefaults['sortByDesc'];
            }
            $desc === true ? $model->latest($sortBy) : $model->oldest($sortBy);
        }

        return $model;
    }
}
