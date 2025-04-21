<?php

declare(strict_types=1);

use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;
use Pest\PendingCalls\DescribeCall;
use Pest\Support\Backtrace;
use Pest\TestSuite;
use Symfony\Component\VarDumper\Caster\ScalarStub;
use Symfony\Component\VarDumper\VarDumper;

if (! function_exists('_dd')) {
    /**
     * Dump.
     *
     * @param  mixed  ...$args
     */
    function _dd(...$args): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Headers: *');
        http_response_code(500);
        // dd($args);

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
                VarDumper::dump($v, is_int($k) ? 1 + $k : $k);
            }
        }

        exit(1);
    }
}

if (! function_exists('shortName')) {
    /**
     * Get the short name of the class.
     *
     * @throws ReflectionException
     */
    function shortName(string $param): ?string
    {
        if (! app($param)) {
            return null;
        }
        $reflection = new ReflectionClass(app($param));

        return $reflection->getShortName();
    }
}
function totalSeconds(string $times): int
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
        $minSec = '00:'.$times;
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

if (! function_exists('duration')) {
    /**
     * Format duration in hours and minutes.
     */
    function duration(int $duration): string
    {
        $interval = CarbonInterval::seconds($duration)->cascade();

        return sprintf('%dh %dm', $interval->totalHours, $interval->minutes);
    }
}

if (! function_exists('dateForHumans')) {
    /**
     * Get human-readable date difference.
     */
    function dateForHumans(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->diffForHumans() : null;
    }
}

if (! function_exists('ymdDate')) {
    /**
     * Format date to specified format.
     */
    function ymdDate(?string $date, string $format = 'Y-m-d'): ?string
    {
        return $date ? Carbon::parse($date)->format($format) : null;
    }
}

if (! function_exists('dateForReports')) {
    /**
     * Format date for reports.
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
     * Get filter value by key.
     */
    function getFilterByKey(string $key = 'date'): ?string
    {
        $filters = Request::get('filters');
        $jsonData = is_string($filters) ? json_decode($filters, true) : [];

        // Ensure $jsonData is an array before using collect
        if (! is_array($jsonData)) {
            return null;
        }

        $value = collect($jsonData)->get($key);

        return is_string($value) ? $value : null;
    }
}

if (! function_exists('getArrayFilterByKey')) {
    /**
     * Get array filter by key.
     *
     * @param  array<string, mixed>|string|null  $data
     * @return array<string, mixed>
     */
    function getArrayFilterByKey($data): array
    {
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            $data = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        }

        /** @var array<string, mixed> */
        return collect($data ?? [])->filter()->all();
    }
}

if (! function_exists('flatData')) {
    /**
     * Flatten data.
     *
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    function flatData(array $data, int $depth = 0): array
    {
        return collect($data)->flatten($depth)->toArray();
    }
}

if (! function_exists('defaultOrder')) {
    /**
     * Get default order direction.
     */
    function defaultOrder(): string
    {
        return request()->query('descending') === 'true' ? 'ASC' : 'DESC';
    }
}

if (! function_exists('defaultSort')) {
    /**
     * Get default sort.
     *
     * @return array<string>|string|null
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
     * Get class methods.
     *
     * @return array<string>
     */
    function getClassMethod(object $class): array
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
    /**
     * Get columns from a table.
     *
     * @return array<string>
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

if (! function_exists('describe') && version_compare(app()->version(), '10.0.0', '<')) {
    /**
     * Adds the given closure as a group of tests. The first argument
     * is the group description; the second argument is a closure
     * that contains the group tests.
     */
    function describe(string $description, Closure $tests): DescribeCall
    {
        $filename = Backtrace::testFile();

        return new DescribeCall(TestSuite::getInstance(), $filename, $description, $tests);
    }
}

if (! function_exists('recursiveDatabaseClasses')) {
    /**
     * Recursively scan a directory for PHP files and extract the fully qualified class names.
     *
     * @param  array<string>  $excluding  An array of class names to exclude.
     * @return array<int,string> An array of fully qualified class names.
     *
     * */
    function recursiveDatabaseClasses(?string $directory = null, array $excluding = []): array
    {
        // Determine base path, default to the database path if no directory is provided
        $basePath = $directory ? database_path($directory) : database_path();

        // Initialize an empty array for classes
        $classes = [];

        // Ensure the directory exists before proceeding
        if (! is_dir($basePath)) {
            return $classes;
        }
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basePath));

        foreach ($files as $file) {
            /**
             * @var RecursiveDirectoryIterator $file
             */
            // Check if the current item is a PHP file
            if ($file->isFile() && $file->getExtension() === 'php') {
                // Get the content of the PHP file
                $fileContent = file_get_contents($file->getPathname());

                // Extract the namespace and class name using regular expressions
                preg_match('/namespace\s+(.+?);/', (string) $fileContent, $namespaceMatch);
                preg_match('/class\s+([a-zA-Z0-9_]+)/', (string) $fileContent, $classMatch);

                // Assign the namespace and class name, if found
                $namespace = $namespaceMatch[1] ?? null;
                $className = $classMatch[1] ?? null;

                // If both namespace and class name are present, add to the classes array
                if ($namespace && $className) {
                    $classes[] = $namespace.'\\'.$className;
                }
            }
        }

        $classes = array_filter($classes, function ($model) use ($excluding) {
            return ! in_array($model, $excluding);
        });

        return array_values($classes);
    }
}

if (! function_exists('toFormattedDateString')) {
    function toFormattedDateString(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->toFormattedDateString() : null;
    }
}

if (! function_exists('uuid')) {
    function uuid(): Ramsey\Uuid\UuidInterface
    {
        return Str::uuid();
    }
}

if (! function_exists('implodeFillable')) {
    function implodeFillable(string $model): string
    {
        if (is_subclass_of($model, 'Illuminate\Database\Eloquent\Model')) {
            $model = new $model;
            $columns = $model->getFillable();
        } else {
            $columns = \Illuminate\Support\Facades\DB::getSchemaBuilder()->getColumnListing($model);
        }

        return implode(',', $columns);
    }
}

if (! function_exists('toDateString')) {
    function toDateString(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->toDateString() : null;
    }
}

if (! function_exists('toDateTimeString')) {
    function toDateTimeString(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->toDateTimeString() : null;
    }
}

if (! function_exists('toTimeString')) {
    function toTimeString(?string $date): ?string
    {
        return $date ? Carbon::parse($date)->toTimeString() : null;
    }
}

if (! function_exists('recursiveClasses')) {
    /**
     * Get list of classes in a directory path, with optional inclusion/exclusion filters
     *
     * @param  string  $path  Base path to scan for classes
     * @param  array<string>  $excluding  Classes to exclude from results
     * @param  array<string>  $including  Classes to include in results
     * @return array<int,string> Array of class names
     */
    function recursiveClasses(string $path = 'App', array $excluding = [], array $including = []): array
    {
        $path = app_path($path);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        $classes = [];
        foreach ($iterator as $file) {
            /**
             * @var RecursiveDirectoryIterator $file
             */
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace(app_path().'/', '', $file->getPathname());
                $className = str_replace(['/', '.php'], ['\\', ''], $relativePath);
                $classes[] = 'App\\'.$className;
            }
        }

        if (! empty($excluding)) {
            $classes = array_filter($classes, function ($class) use ($excluding) {
                return ! in_array($class, $excluding);
            });
        }

        if (! empty($including)) {
            $classes = array_filter($classes, function ($class) use ($including) {
                return in_array($class, $including);
            });
        }

        return array_values($classes);
    }
}

if (! function_exists('slug')) {
    function slug(?string $text = null): ?string
    {
        return isset($text) ? Str::slug($text) : null;
    }
}

if (! function_exists('anyRoute')) {
    function anyRoute(mixed $params, string $action, string $method = 'get'): \Illuminate\Routing\Route
    {
        return Route::$method($params, $action);
    }
}
