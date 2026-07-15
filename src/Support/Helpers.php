<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\VarDumper\Caster\ScalarStub;
use Symfony\Component\VarDumper\VarDumper;

if (! function_exists('classShortName')) {
    /**
     * Get the short name of a class by its fully qualified name.
     *
     * @throws ReflectionException
     */
    function classShortName(string $param): ?string
    {
        $instance = app($param);
        if (! $instance) {
            return null;
        }
        if (! is_object($instance)) {
            return null;
        }
        $reflection = new ReflectionClass($instance);

        return $reflection->getShortName();
    }
}

if (! function_exists('parseTimeToSeconds')) {
    /**
     * Parse a time string (e.g., "01:30:00" or "30:45") into total seconds.
     *
     * Supports "H:i:s", "i:s", or plain integer seconds.
     */
    function parseTimeToSeconds(string $timeString): int
    {
        // Compute arithmetically rather than via Carbon::diffInSeconds — the latter
        // is signed and directional in Carbon 3 (Laravel 11+) and returned a
        // NEGATIVE value here, contradicting the "total seconds" contract.
        $parts = explode(':', trim($timeString));
        $count = count($parts);

        if ($count >= 3) {
            return (int) $parts[0] * 3600 + (int) $parts[1] * 60 + (int) $parts[2];
        }

        if ($count === 2) {
            // "MM:SS"
            return (int) $parts[0] * 60 + (int) $parts[1];
        }

        return (int) $timeString;
    }
}

if (! function_exists('formatDuration')) {
    /**
     * Format a duration in seconds into a human-readable string.
     *
     * Placeholders: %y=years, %mo=months, %d=days, %h=hours, %m=minutes, %s=seconds
     */
    function formatDuration(int|float|null $duration, ?string $format = '%y %mo %d %h %m %s', string $separator = ' '): string
    {
        if ($duration === null || $duration === 0) {
            return '0s';
        }

        $total = (int) abs($duration);

        $secPerMin = 60;
        $secPerHour = 60 * $secPerMin;
        $secPerDay = 24 * $secPerHour;
        $secPerMonth = 30 * $secPerDay;
        $secPerYear = 365 * $secPerDay;

        $years = intdiv($total, $secPerYear);
        $total %= $secPerYear;
        $months = intdiv($total, $secPerMonth);
        $total %= $secPerMonth;
        $days = intdiv($total, $secPerDay);
        $total %= $secPerDay;
        $hours = intdiv($total, $secPerHour);
        $total %= $secPerHour;
        $minutes = intdiv($total, $secPerMin);
        $seconds = $total % $secPerMin;

        $units = [
            '%y'  => ['value' => $years, 'label' => 'yr'],
            '%mo' => ['value' => $months, 'label' => 'mo'],
            '%d'  => ['value' => $days, 'label' => 'd'],
            '%h'  => ['value' => $hours, 'label' => 'h'],
            '%m'  => ['value' => $minutes, 'label' => 'm'],
            '%s'  => ['value' => $seconds, 'label' => 's'],
        ];

        $parts = [];

        if ($format !== null) {
            foreach (preg_split('/\s+/', $format) ?: [] as $token) {
                if (str_starts_with($token, '%')) {
                    if (isset($units[$token]) && $units[$token]['value'] > 0) {
                        $parts[] = $units[$token]['value'] . $units[$token]['label'];
                    }
                } else {
                    $parts[] = $token;
                }
            }

            return $parts !== []
                ? implode($separator, $parts)
                : '0s';
        }

        foreach ($units as $u) {
            if ($u['value'] > 0) {
                $parts[] = $u['value'] . $u['label'];
            }
        }

        return $parts !== []
            ? implode($separator, $parts)
            : '0s';
    }
}

if (! function_exists('diffForHumans')) {
    /**
     * Get a human-readable difference between the given date and now.
     */
    function diffForHumans(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->diffForHumans() : null;
    }
}

if (! function_exists('ymdDate')) {
    /**
     * Format a date string into a specified format (default: "Y-m-d").
     */
    function ymdDate(?string $date, string $format = 'Y-m-d'): ?string
    {
        return $date ? Carbon::parse($date)->format($format) : null;
    }
}

if (! function_exists('dateForReports')) {
    /**
     * Format a date/time string for reporting purposes (default: "Y-m-d H:i").
     */
    function dateForReports(?string $date, string $format = 'Y-m-d H:i'): ?string
    {
        try {
            return Carbon::parse($date)->format($format);
        } catch (Exception) {
            return null;
        }
    }
}

if (! function_exists('filterValue')) {
    /**
     * Get a single filter value by key from the "filters" query parameter.
     */
    function filterValue(string $key = 'date'): ?string
    {
        $filters = Request::get('filters');
        $jsonData = is_string($filters) ? json_decode($filters, true) : [];

        if (! is_array($jsonData)) {
            return null;
        }

        $value = $jsonData[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}

if (! function_exists('arrayFilters')) {
    /**
     * Convert a JSON string or array into a filtered associative array (removes falsy values).
     *
     * @param string|array<string,mixed>|null $data
     *
     * @return array<string,mixed>
     */
    function arrayFilters(array|string|null $data): array
    {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        }

        /** @var array<string,mixed> */
        return collect($data ?? [])->filter()->all();
    }
}

if (! function_exists('flattenArray')) {
    /**
     * Flatten a nested array to a given depth.
     *
     * @param array<string,mixed> $data
     *
     * @return array<int|string, mixed>
     */
    function flattenArray(array $data, int $depth = 0): array
    {
        return collect($data)->flatten($depth)->toArray();
    }
}

if (! function_exists('sortDirection')) {
    /**
     * Determine the sort direction from the "descending" query parameter.
     */
    function sortDirection(): string
    {
        return request()->query('descending') === 'true' ? 'ASC' : 'DESC';
    }
}

if (! function_exists('sortBy')) {
    /**
     * Get the sort key(s) from the "sort" request parameter.
     *
     * @return array<string>|string|null
     */
    function sortBy(): array|string|null
    {
        $sort = request('sort');

        if (is_string($sort)) {
            return $sort;
        }

        if (is_array($sort)) {
            /** @var array<string> */
            return array_filter($sort);
        }

        return null;
    }
}

if (! function_exists('scopeMethods')) {
    /**
     * Retrieve all public methods of an object that begin with "scope".
     *
     * @return array<string>
     */
    function scopeMethods(object $class): array
    {
        $reflection = new ReflectionClass($class);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        $scopeMethods = [];

        foreach ($methods as $method) {
            if (str_starts_with($method->getName(), 'scope')) {
                $scopeMethods[] = $method->getName();
            }
        }

        return $scopeMethods;
    }
}

if (! function_exists('tableColumns')) {
    /**
     * Get an ordered list of column names for a given table or Eloquent model.
     *
     * Returns: ["id", ...other columns (sorted), "created_at", "updated_at", "deleted_at"].
     *
     * @return list<string>
     */
    function tableColumns(string|Model $table = 'users'): array
    {
        if (is_string($table)) {
            if (is_subclass_of($table, Model::class)) {
                /** @var Model */
                $model = new $table;
                $columns = Schema::getColumnListing($model->getTable());
            } else {
                $columns = Schema::getColumnListing($table);
            }
        } else {
            $columns = Schema::getColumnListing($table->getTable());
        }

        /** @var array<string> $stringColumns */
        $stringColumns = array_filter($columns, 'is_string');
        $stringColumns = array_diff($stringColumns, ['id']);
        $specialColumns = ['created_at', 'updated_at', 'deleted_at'];
        $stringColumns = array_diff($stringColumns, $specialColumns);
        sort($stringColumns);

        return array_merge(['id'], $stringColumns, $specialColumns);
    }
}

if (! function_exists('databaseClasses')) {
    /**
     * Recursively find all PHP classes inside the database directory.
     *
     * @param array<string> $excluding
     *
     * @return array<int,string>
     */
    function databaseClasses(?string $directory = null, array $excluding = []): array
    {
        $basePath = $directory ? database_path($directory) : database_path();
        $classes = [];

        if (! is_dir($basePath)) {
            return $classes;
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basePath));
        foreach ($files as $file) {
            /** @var RecursiveDirectoryIterator $file */
            if ($file->isFile() && $file->getExtension() === 'php') {
                $fileContent = file_get_contents($file->getPathname());
                preg_match('/namespace\s+(.+?);/', (string) $fileContent, $namespaceMatch);
                preg_match('/class\s+([A-Za-z0-9_]+)/', (string) $fileContent, $classMatch);

                $namespace = $namespaceMatch[1] ?? null;
                $className = $classMatch[1] ?? null;
                if ($namespace && $className) {
                    $classes[] = $namespace . '\\' . $className;
                }
            }
        }

        $classes = array_filter($classes, fn ($model) => ! in_array($model, $excluding));

        return array_values($classes);
    }
}

if (! function_exists('toFormattedDateString')) {
    /**
     * Convert a date string into a formatted date (e.g., "January 1, 2025").
     */
    function toFormattedDateString(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->toFormattedDateString() : null;
    }
}

if (! function_exists('uuid')) {
    /**
     * Generate a UUID v4 string.
     */
    function uuid(): string
    {
        return (string) Str::uuid();
    }
}

if (! function_exists('fillableCsv')) {
    /**
     * Get a comma-separated string of fillable attributes for a Model or table columns.
     */
    function fillableCsv(string $model): string
    {
        if (is_subclass_of($model, Model::class)) {
            $instance = new $model;
            $columns = $instance->getFillable();
        } else {
            $columns = DB::getSchemaBuilder()->getColumnListing($model);
        }

        return implode(',', $columns);
    }
}

if (! function_exists('columnsCsv')) {
    /**
     * Get a separated string of column names for a Model or table.
     */
    function columnsCsv(string $model, string $separator = ','): string
    {
        return implode($separator, tableColumns($model));
    }
}

if (! function_exists('toDateString')) {
    /**
     * Convert a date/time string into a "Y-m-d" date string.
     */
    function toDateString(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->toDateString() : null;
    }
}

if (! function_exists('toDateTimeString')) {
    /**
     * Convert a date/time string into a "Y-m-d H:i:s" datetime string.
     */
    function toDateTimeString(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->toDateTimeString() : null;
    }
}

if (! function_exists('toTimeString')) {
    /**
     * Convert a date/time string into an "H:i:s" time string.
     */
    function toTimeString(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->toTimeString() : null;
    }
}

if (! function_exists('appClasses')) {
    /**
     * Get a list of fully qualified class names in a given app directory.
     *
     * @param array<string> $excluding
     *
     * @return array<int,string>
     */
    function appClasses(string $path = 'App', array $excluding = []): array
    {
        $fullPath = app_path($path);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullPath));
        $classes = [];

        foreach ($iterator as $file) {
            /** @var RecursiveDirectoryIterator $file */
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace(app_path() . '/', '', $file->getPathname());
                $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
                $classes[] = "App\\{$className}";
            }
        }

        if ($excluding !== []) {
            $classes = array_filter($classes, fn ($class) => ! in_array($class, $excluding));
        }

        return array_values($classes);
    }
}

if (! function_exists('slug')) {
    /**
     * Generate a URL-friendly slug from a given string.
     */
    function slug(?string $text = null): ?string
    {
        return isset($text) ? Str::slug($text) : null;
    }
}

if (! function_exists('relativePath')) {
    /**
     * Get the relative path of a given absolute path from the project root.
     */
    function relativePath(string $path): string
    {
        return str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path);
    }
}

if (! function_exists('_dd')) {
    /**
     * Enhanced debug dump with CORS headers support for API contexts.
     *
     * Outputs dumped values and halts execution. Includes CORS headers
     * to prevent browser/API clients from swallowing the error response.
     * Always returns HTTP 500.
     *
     * @param mixed ...$vars Values to dump.
     */
    function _dd(mixed ...$vars): never
    {
        if (! headers_sent()) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: *');
            header('Access-Control-Allow-Headers: *');
            header('HTTP/1.1 500 Internal Server Error');
        }

        if (! $vars) {
            VarDumper::dump(new ScalarStub('🐛'));
            exit(1);
        }

        if (array_key_exists(0, $vars) && count($vars) === 1) {
            VarDumper::dump($vars[0]);
        } else {
            foreach ($vars as $k => $v) {
                // @phpstan-ignore-next-line
                VarDumper::dump($v, is_int($k) ? 1 + $k : $k);
            }
        }

        exit(1);
    }
}
