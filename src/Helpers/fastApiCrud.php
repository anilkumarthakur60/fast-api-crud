<?php

use Carbon\Carbon;
use Carbon\CarbonInterval;

if (! function_exists('_dd')) {
    function _dd(...$args)
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Headers: *');
        http_response_code(500);

        foreach ($args as $x) {
            (new Symfony\Component\VarDumper\VarDumper)->dump($x);
        }

        exit(1);
    }
}

if (! function_exists('shortName')) {

    function shortName($param)
    {
        if (! app($param)) {
            return null;
        }
        $reflection = new ReflectionClass(app($param));

        return $reflection->getShortName();
    }
}

if (! function_exists('totalSeconds')) {
    function totalSeconds($times): mixed
    {
        $time = explode(':', $times);

        if (count($time) >= 3) {
            $carbon = new Carbon($times);
            $seconds = $carbon->diffInSeconds(Carbon::createFromFormat('H:i:s', '00:00:00'));
        } elseif (count($time) == 2) {
            $minSec = '00:'.$times;
            $carbon = new Carbon($minSec);
            $seconds = $carbon->diffInSeconds(Carbon::createFromFormat('H:i:s', '00:00:00'));
        } else {
            $seconds = $times;
        }

        return $seconds;
    }
}
if (! function_exists('duration')) {
    function duration($duration): string
    {
        $interval = CarbonInterval::seconds($duration)->cascade();

        return sprintf('%dh %dm', $interval->totalHours, $interval->toArray()['minutes']);

        // return CarbonInterval::second($duration)->cascade()->forHumans();
    }
}

if (! function_exists('dateForHumans')) {
    function dateForHumans($date): ?string
    {
        if ($date) {
            return Carbon::parse($date)->diffForHumans();
        }

        return null;
    }
}

if (! function_exists('ymdDate')) {
    function ymdDate($date, $format = 'Y-m-d'): ?string
    {
        if ($date) {
            return Carbon::parse($date)->format($format);
        }

        return null;
    }
}

if (! function_exists('dateForReports')) {
    function dateForReports($date, $format = 'Y-m-d H:i'): ?string
    {
        if ($date) {
            return Carbon::parse($date)->format($format);
        }

        return null;
    }
}
if (! function_exists('getFilterByKey')) {
    function getFilterByKey($key = 'date'): ?string
    {
        $jsonData = json_decode(request()->query('filters'));
        $value = collect($jsonData)->get($key);

        return $value ?? null;
    }
}
if (! function_exists('getArrayFilterByKey')) {
    function getArrayFilterByKey($key = 'date'): ?array
    {
        $jsonData = json_decode(request()->query('filters'));
        $value = collect($jsonData)->get($key);

        return flatData($value) ?? [];
    }
}

if (! function_exists('flatData')) {
    function flatData($data, $depth = 0): array
    {
        return collect($data)->flatten($depth)->toArray();
    }
}

if (! function_exists('defaultOrder')) {
    function defaultOrder(): string
    {
        return (bool) request()->query('descending') === true ? 'ASC' : 'DESC';
    }
}
if (! function_exists('defaultSort')) {
    function defaultSort($key = 'id'): string
    {
        return request()->query('sortBy', $key);
    }
}

if (! function_exists('getClassMethod')) {
    function getClassMethod($class)
    {

        $class = new ReflectionClass($class);
        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);
        $scopeMethods = [];
        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), 'scope')) {
                $scopeMethods[] = $method->getName();
            }
        }

        return $scopeMethods;
    }
}

if (! function_exists('getColumns')) {
    function getColumns($table = 'users'): array
    {
        if (is_subclass_of($table, 'Illuminate\Database\Eloquent\Model')) {
            $model = new $table();

            $columns = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());
        } else {
            $columns = \Illuminate\Support\Facades\DB::getSchemaBuilder()->getColumnListing($table);
        }

        $columns = array_diff($columns, ['id']);
        $specialColumns = ['created_at', 'updated_at', 'deleted_at'];
        $columns = array_diff($columns, $specialColumns);
        sort($columns);
        $sortedColumns = array_merge(['id'], $columns, $specialColumns);

        return $sortedColumns;
    }
}
