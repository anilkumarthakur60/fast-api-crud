<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\VarDumper\Caster\ScalarStub;
use Symfony\Component\VarDumper\VarDumper;

if (! function_exists('_dd')) {
    /**
     * Custom debug dump that sends appropriate headers for CORS and API error response.
     *
     * Outputs the dumped content and stops execution, similar to Laravel's `dd()`,
     * but includes headers for debugging in API contexts. Always returns a 500 status.
     *
     * @param  mixed  ...$args  Values to dump.
     * @return never
     */
    function _dd(...$args): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Headers: *');
        http_response_code(500);

        if (! \in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) && ! headers_sent()) {
            header('HTTP/1.1 500 Internal Server Error');
        }

        if (! $args) {
            VarDumper::dump(new ScalarStub('🐛'));
            exit(1);
        }

        if (array_key_exists(0, $args) && count($args) === 1) {
            VarDumper::dump($args[0]);
        } else {
            foreach ($args as $k => $v) {
                // @phpstan-ignore-next-line
                VarDumper::dump($v, is_int($k) ? 1 + $k : $k);
            }
        }

        exit(1);
    }
}

if (! function_exists('getClassShortName')) {
    /**
     * Get the short name of a class by its fully qualified name.
     *
     * If the given class name resolves in the container, returns the reflection short name.
     * Otherwise returns null.
     *
     * @param  string  $param  Fully qualified class name.
     * @return string|null Class short name, or null if the class is not bound in the container.
     *
     * @throws ReflectionException
     */
    function getClassShortName(string $param): ?string
    {
        if (! app($param)) {
            return null;
        }
        $reflection = new ReflectionClass(app($param));

        return $reflection->getShortName();
    }
}

if (! function_exists('parseTimeToSeconds')) {
    /**
     * Parse a time string (e.g., "01:30:00" or "30:45") into total seconds.
     *
     * Supports:
     *  - "H:i:s" format (hours:minutes:seconds)
     *  - "i:s" format (minutes:seconds)
     *  - plain integer or numeric string representing seconds
     *
     * @param  string  $times  Time string to parse.
     * @return int|float Number of seconds corresponding to the input.
     */
    function parseTimeToSeconds(string $times): int|float
    {
        $time = explode(':', $times);
        $seconds = 0;

        if (count($time) >= 3) {
            $carbon = Carbon::createFromFormat('H:i:s', $times);
            $reference = Carbon::createFromFormat('H:i:s', '00:00:00');

            if ($carbon && $reference) {
                $seconds = $carbon->diffInSeconds($reference);
            }
        } elseif (count($time) === 2) {
            $minSec = "00:{$times}";
            $carbon = Carbon::createFromFormat('H:i:s', $minSec);
            $reference = Carbon::createFromFormat('H:i:s', '00:00:00');

            if ($carbon && $reference) {
                $seconds = $carbon->diffInSeconds($reference);
            }
        } else {
            $seconds = (int) $times;
        }

        return $seconds;
    }
}

if (! function_exists('formatDuration')) {
    /**
     * Format a duration in seconds into a custom or default human-readable string.
     *
     * Approximates:
     *   1 year   = 365 days
     *   1 month  = 30 days
     *   1 day    = 24 hours
     *   1 hour   = 60 minutes
     *   1 minute = 60 seconds
     *
     * Placeholders:
     *   %y  = years
     *   %mo = months
     *   %d  = days
     *   %h  = hours
     *   %m  = minutes
     *   %s  = seconds
     *
     * @param  int|float|null  $duration  Negative or float OK; will be floored to abs().
     * @param  string|null  $format  Space-separated list of placeholders (with literals).
     * @param  string  $separator  How to join the parts (default: a single space).
     */
    function formatDuration(int|float|null $duration, ?string $format = '%y %mo %d %h %m %s', string $separator = ' '): string
    {
        if ($duration === null || $duration === 0) {
            return '0s';
        }

        // absolute, floored to integer seconds
        $total = (int) abs($duration);

        // fixed conversion factors
        $secPerMin = 60;
        $secPerHour = 60 * $secPerMin;
        $secPerDay = 24 * $secPerHour;
        $secPerMonth = 30 * $secPerDay;
        $secPerYear = 365 * $secPerDay;

        // break down
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
            '%y' => ['value' => $years,   'label' => 'yr'],
            '%mo' => ['value' => $months,  'label' => 'mo'],
            '%d' => ['value' => $days,    'label' => 'd'],
            '%h' => ['value' => $hours,   'label' => 'h'],
            '%m' => ['value' => $minutes, 'label' => 'm'],
            '%s' => ['value' => $seconds, 'label' => 's'],
        ];

        $parts = [];

        // custom‐format path
        if ($format !== null) {
            foreach (preg_split('/\s+/', $format) as $token) {
                if (str_starts_with($token, '%')) {
                    // known placeholder?
                    if (isset($units[$token]) && $units[$token]['value'] > 0) {
                        $parts[] = $units[$token]['value'].$units[$token]['label'];
                    }
                    // unknown % token → skip
                } else {
                    // literal text
                    $parts[] = $token;
                }
            }

            return $parts
                ? implode($separator, $parts)
                : '0s';
        }

        // default path: list all non‐zero units
        foreach ($units as $u) {
            if ($u['value'] > 0) {
                $parts[] = $u['value'].$u['label'];
            }
        }

        return $parts
            ? implode($separator, $parts)
            : '0s';
    }
}

if (! function_exists('diffForHumans')) {
    /**
     * Get a human-readable difference between the given date and now.
     *
     * Returns null if the input date is null or invalid.
     *
     * @param  string|null  $date  A parsable date string.
     * @return string|null A "diffForHumans" string, or null if input is null.
     */
    function diffForHumans(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->diffForHumans() : null;
    }
}

if (! function_exists('ymdDate')) {
    /**
     * Format a date string into a specified Y-m-d format.
     *
     * Returns null if the input date is null or cannot be parsed.
     *
     * @param  string|null  $date  A parsable date string.
     * @param  string  $format  Desired output format (default: "Y-m-d").
     * @return string|null Formatted date string or null.
     */
    function ymdDate(?string $date, string $format = 'Y-m-d'): ?string
    {
        return $date ? Carbon::parse($date)->format($format) : null;
    }
}

if (! function_exists('dateForReports')) {
    /**
     * Format a date/time string for reporting purposes.
     *
     * Attempts to parse the given date and format it according to the provided pattern.
     * Returns null on parse failure.
     *
     * @param  string|null  $date  A parsable date/time string.
     * @param  string  $format  Desired output format (default: "Y-m-d H:i").
     * @return string|null Formatted date/time string or null if parsing fails.
     */
    function dateForReports(?string $date, string $format = 'Y-m-d H:i'): ?string
    {
        try {
            return Carbon::parse($date)->format($format);
        } catch (\Exception $e) {
            return null;
        }
    }
}

if (! function_exists('getFilterByKey')) {
    /**
     * Get a single filter value by key from the "filters" query parameter.
     *
     * Expects the request to include a "filters" parameter containing a JSON object.
     * Returns the string value matching the given key, or null if not present or invalid.
     *
     * @param  string  $key  The filter key to retrieve (default: "date").
     * @return string|null The filter value as a string, or null if not found/invalid.
     */
    function getFilterByKey(string $key = 'date'): ?string
    {
        $filters = Request::get('filters');
        $jsonData = is_string($filters) ? json_decode($filters, true) : [];

        if (! is_array($jsonData)) {
            return null;
        }

        $value = collect($jsonData)->get($key);

        return is_string($value) ? $value : null;
    }
}

if (! function_exists('getArrayFilterByKey')) {
    /**
     * Convert a JSON string or array into a filtered associative array.
     *
     * Accepts:
     *  - A JSON-encoded string representing an array.
     *  - An actual PHP array.
     *  - Null, which yields an empty array.
     *
     * Filters out any falsy or empty values, returning only keys with truthy entries.
     *
     * @param  string|array<string,mixed>|null  $data  Input data to decode/filter.
     * @return array<string,mixed> Filtered array of data.
     */
    function getArrayFilterByKey(array|string|null $data): array
    {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        }

        /** @var array<string,mixed> */
        return collect($data ?? [])->filter()->all();
    }
}

if (! function_exists('flatData')) {
    /**
     * Flatten a nested array to a given depth.
     *
     * Uses Laravel's Collection flatten method under the hood.
     *
     * @param  array<string,mixed>  $data  The nested array to flatten.
     * @param  int  $depth  Depth level to flatten (default: 0, full flatten).
     * @return array<string,mixed> Flattened array.
     */
    function flatData(array $data, int $depth = 0): array
    {
        return collect($data)->flatten($depth)->toArray();
    }
}

if (! function_exists('defaultOrder')) {
    /**
     * Determine the default sort order based on the "descending" query parameter.
     *
     * If "descending" is set to "true" (string), returns "ASC"; otherwise "DESC".
     *
     * @return string Either "ASC" or "DESC".
     */
    function defaultOrder(): string
    {
        return request()->query('descending') === 'true' ? 'ASC' : 'DESC';
    }
}

if (! function_exists('defaultSort')) {
    /**
     * Get the default sort key(s) from the "sort" request parameter.
     *
     * Accepts:
     *  - A string (single sort column).
     *  - An array of strings (multiple sort columns).
     *  - Anything else yields null.
     *
     * @return array<string>|string|null Sort key(s) or null if none provided.
     */
    function defaultSort(): array|string|null
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

if (! function_exists('getClassMethod')) {
    /**
     * Retrieve all public methods of an object that begin with "scope".
     *
     * Useful for discovering Eloquent "scope" methods dynamically.
     *
     * @param  object  $class  Instance of a class to inspect.
     * @return array<string> List of method names starting with "scope".
     */
    function getClassMethod(object $class): array
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

if (! function_exists('getColumns')) {
    /**
     * Get an ordered list of column names for a given table or Eloquent model.
     *
     * - If provided a model class name, will instantiate it and use its table.
     * - Excludes the "id" column and timestamp columns ("created_at", "updated_at", "deleted_at")
     *   from the middle; then merges them back so the final array is:
     *   ["id", ...other columns (sorted), "created_at", "updated_at", "deleted_at"].
     *
     * @param  string|Model  $table  Table name or fully qualified Model class name.
     * @return array<string> Ordered list of column names.
     */
    function getColumns(string|Model $table = 'users'): array
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

        $columns = array_diff($columns, ['id']);
        $specialColumns = ['created_at', 'updated_at', 'deleted_at'];
        $columns = array_diff($columns, $specialColumns);
        sort($columns);

        /** @var array<string> */
        return array_merge(['id'], $columns, $specialColumns);
    }
}

if (! function_exists('recursiveDatabaseClasses')) {
    /**
     * Recursively find all PHP classes inside the database directory.
     *
     * Scans /database by default or a given subdirectory under /database for ".php" files,
     * extracts namespace and class name via regex, and returns fully qualified class names.
     *
     * @param  string|null  $directory  Relative directory under /database to scan (e.g., "migrations").
     * @param  array<string>  $excluding  Fully qualified class names to exclude from results.
     * @return array<int,string> List of fully qualified class names found.
     */
    function recursiveDatabaseClasses(?string $directory = null, array $excluding = []): array
    {
        // Determine base path; default to /database root if no subdirectory provided
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
                    $classes[] = $namespace.'\\'.$className;
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
     *
     * Returns null if the input is null or cannot be parsed.
     *
     * @param  string|null  $date  A parsable date string.
     * @return string|null Formatted date string or null if input is null.
     */
    function toFormattedDateString(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->toFormattedDateString() : null;
    }
}

if (! function_exists('uuid')) {
    /**
     * Generate a UUID (version 4) using Laravel's Str::uuid().
     *
     * @return \Ramsey\Uuid\UuidInterface A newly generated UUID object.
     */
    function uuid(): \Ramsey\Uuid\UuidInterface
    {
        return Str::uuid();
    }
}

if (! function_exists('implodeFillable')) {
    /**
     * Get a comma-separated string of fillable attributes for a Model or database table.
     *
     * If provided a Model class (subclass of \Illuminate\Database\Eloquent\Model),
     * instantiates it and retrieves its $fillable properties. Otherwise, assumes
     * the string is a table name and retrieves all column names.
     *
     * @param  string  $model  Model class name or table name.
     * @return string Comma-separated list of column names.
     */
    function implodeFillable(string $model): string
    {
        if (is_subclass_of($model, Model::class)) {
            $instance = new $model;
            $columns = $instance->getFillable();
        } else {
            $columns = \Illuminate\Support\Facades\DB::getSchemaBuilder()->getColumnListing($model);
        }

        return implode(',', $columns);
    }
}

if (! function_exists('implodeColumns')) {
    /**
     * Get a comma-separated string of column names for a Model or database table.
     *
     * Uses getColumns() under the hood, which orders columns as:
     * ["id", ...other columns (sorted), "created_at", "updated_at", "deleted_at"].
     *
     * @param  string  $model  Model class name or table name.
     * @param  string  $separator  Separator to use between column names (default: ",").
     * @return string Concatenated column names string.
     */
    function implodeColumns(string $model, string $separator = ','): string
    {
        $columns = getColumns($model);

        return implode($separator, $columns);
    }
}

if (! function_exists('toDateString')) {
    /**
     * Convert a date/time string into a "Y-m-d" date string.
     *
     * Returns null if the input is null or cannot be parsed.
     *
     * @param  string|null  $date  A parsable date/time string.
     * @return string|null Date string in "Y-m-d" format, or null.
     */
    function toDateString(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->toDateString() : null;
    }
}

if (! function_exists('toDateTimeString')) {
    /**
     * Convert a date/time string into a "Y-m-d H:i:s" datetime string.
     *
     * Returns null if the input is null or cannot be parsed.
     *
     * @param  string|null  $date  A parsable date/time string.
     * @return string|null Datetime string in "Y-m-d H:i:s" format, or null.
     */
    function toDateTimeString(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->toDateTimeString() : null;
    }
}

if (! function_exists('toTimeString')) {
    /**
     * Convert a date/time string into an "H:i:s" time string.
     *
     * Returns null if the input is null or cannot be parsed.
     *
     * @param  string|null  $date  A parsable date/time string.
     * @return string|null Time string in "H:i:s" format, or null.
     */
    function toTimeString(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->toTimeString() : null;
    }
}

if (! function_exists('recursiveClasses')) {
    /**
     * Get a list of fully qualified class names in a given directory, with optional exclusions.
     *
     * Scans the "app" directory by default (i.e., app_path('App')), or a subdirectory thereof,
     * for PHP files, and returns class names by converting file paths to namespace\\ClassName.
     *
     * @param  string  $path  Base directory under app/ to scan (default: "App").
     * @param  array<string>  $excluding  Class names to exclude from the result.
     * @return array<int,string> Array of fully qualified class names.
     */
    function recursiveClasses(string $path = 'App', array $excluding = []): array
    {
        $fullPath = app_path($path);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($fullPath));
        $classes = [];

        foreach ($iterator as $file) {
            /** @var RecursiveDirectoryIterator $file */
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace(app_path().'/', '', $file->getPathname());
                $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
                $classes[] = "App\\{$className}";
            }
        }

        if (! empty($excluding)) {
            $classes = array_filter($classes, fn ($class) => ! in_array($class, $excluding));
        }

        return array_values($classes);
    }
}

if (! function_exists('slug')) {
    /**
     * Generate a URL-friendly "slug" from a given string.
     *
     * Uses Laravel's Str::slug() internally. Returns null if the input is null.
     *
     * @param  string|null  $text  Input text to convert to slug.
     * @return string|null Slugified string or null if input is null.
     */
    function slug(?string $text = null): ?string
    {
        return isset($text) ? Str::slug($text) : null;
    }
}

if (! function_exists('pathRelativeToBase')) {
    /**
     * Get the relative path of a given absolute path with respect to the base application path.
     *
     * Strips the base_path() portion from the provided full path.
     *
     * @param  string  $path  Absolute file or directory path.
     * @return string Relative path from the project root.
     */
    function pathRelativeToBase(string $path): string
    {
        $fullPath = $path;
        $basePath = base_path();

        return str_replace($basePath.DIRECTORY_SEPARATOR, '', $fullPath);
    }
}
