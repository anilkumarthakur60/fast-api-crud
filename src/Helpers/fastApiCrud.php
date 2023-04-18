<?php

use Carbon\Carbon;
use Carbon\CarbonInterval;

if (!function_exists('_dd')) {
    /*
     * Dump the passed variables and end the script.
     *
     * @param  mixed  $args
     * @return void
     */
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

if (!function_exists('shortName')) {
    function shortName($param)
    {
        if (!app($param)) {
            return NULL;
        }
        $reflection = new ReflectionClass(app($param));

        return $reflection->getShortName();
    }
}

if (!function_exists('videoDuration')) {
    function videoDuration($file): mixed
    {
        $getID3 = new getID3;
        $fileData = $getID3->analyze($file);

        return $fileData['playtime_seconds'];
    }
}

if (!function_exists('totalSeconds')) {
    function totalSeconds($times): mixed
    {
        $time = explode(':', $times);

        if (count($time) >= 3) {
            $carbon = new Carbon($times);
            $seconds = $carbon->diffInSeconds(Carbon::createFromFormat('H:i:s', '00:00:00'));
        } elseif (count($time) == 2) {
            $minSec = '00:' . $times;
            $carbon = new Carbon($minSec);
            $seconds = $carbon->diffInSeconds(Carbon::createFromFormat('H:i:s', '00:00:00'));
        } else {
            $seconds = $times;
        }

        return $seconds;
    }
}
if (!function_exists('duration')) {
    function duration($duration): mixed
    {
        $interval = CarbonInterval::seconds($duration)->cascade();

        return $output = sprintf('%dh %dm', $interval->totalHours, $interval->toArray()['minutes']);

        return CarbonInterval::second($duration)->cascade()->forHumans();
    }
}

if (!function_exists('dateForHumans')) {
    function dateForHumans($date): mixed
    {
        if ($date) {
            return Carbon::parse($date)->diffForHumans();
        }
        return NULL;
    }
}

if (!function_exists('ymdDate')) {
    function ymdDate($date): mixed
    {
        if ($date) {
            return Carbon::parse($date)->format('Y-m-d');
        }
        return NULL;
    }
}

if (!function_exists('dateForReports')) {
    function dateForReports($date): string|null
    {
        if ($date) {
            return Carbon::parse($date)->format('Y-m-d H:i');
        }

        return NULL;
    }
}
if (!function_exists('getSqlQuery')) {
    function getSqlQuery($sql)
    {
        $query = str_replace(['?'], ['\'%s\''], $sql->toSql());

        return vsprintf($query, $sql->getBindings());
    }
}
if (!function_exists('getFilterByKey')) {
    function getFilterByKey($key = 'date')
    {
        $jsonData = json_decode(request()->query('filters'));
        $value = collect($jsonData)->get($key);

        return $value ?? FALSE;
    }
}
if (!function_exists('getArrayFilterByKey')) {
    function getArrayFilterByKey($key = 'date')
    {
        $jsonData = json_decode(request()->query('filters'));
        $value = collect($jsonData)->get($key);

        return flatData($value) ?? [];
    }
}

if (!function_exists('flatData')) {
    function flatData($data, $depth = 0): array
    {
        return collect($data)->flatten($depth)->toArray();
    }
}

if (!function_exists('defaultOrder')) {
    function defaultOrder($value = NULL): string
    {
        if ($value === TRUE) {
            return 'DESC';
        }

        return json_decode(request()->query('descending')) ? 'DESC' : 'ASC';
    }
}
if (!function_exists('defaultSort')) {
    function defaultSort($key = 'id'): string
    {
        return request()->query('sortBy', $key);
    }
}
