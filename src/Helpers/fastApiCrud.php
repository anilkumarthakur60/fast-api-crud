<?php

use Carbon\Carbon;
use Carbon\CarbonInterval;

if (!function_exists('_dd')) {
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
            return null;
        }
        $reflection = new ReflectionClass(app($param));

        return $reflection->getShortName();
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
    function duration($duration): string
    {
        $interval = CarbonInterval::seconds($duration)->cascade();

        return sprintf('%dh %dm', $interval->totalHours, $interval->toArray()['minutes']);

        // return CarbonInterval::second($duration)->cascade()->forHumans();
    }
}

if (!function_exists('dateForHumans')) {
    function dateForHumans($date): string|null
    {
        if ($date) {
            return Carbon::parse($date)->diffForHumans();
        }

        return null;
    }
}

if (!function_exists('ymdDate')) {
    function ymdDate($date, $format = 'Y-m-d'):string|null
    {
        if ($date) {
            return Carbon::parse($date)->format($format);
        }

        return null;
    }
}

if (!function_exists('dateForReports')) {
    function dateForReports($date, $format = 'Y-m-d H:i'): string|null
    {
        if ($date) {
            return Carbon::parse($date)->format($format);
        }

        return null;
    }
}
if (!function_exists('getFilterByKey')) {
    function getFilterByKey($key = 'date'): string|null
    {
        $jsonData = json_decode(request()->query('filters'));
        $value = collect($jsonData)->get($key);

        return $value ?? null;
    }
}
if (!function_exists('getArrayFilterByKey')) {
    function getArrayFilterByKey($key = 'date'): array|null
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
    function defaultOrder(): string
    {
        return (bool)request()->query('descending') === true ? 'ASC' : 'DESC';
    }
}
if (!function_exists('defaultSort')) {
    function defaultSort($key = 'id'): string
    {
        return request()->query('sortBy', $key);
    }
}
