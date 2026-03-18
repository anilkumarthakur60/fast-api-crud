<?php

namespace Anil\FastApiCrud\Providers;

use Anil\FastApiCrud\Commands\MakeAllCommand;
use Closure;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ApiCrudServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/fast-api.php' => config_path('fast-api.php'),
        ], 'config');

        $this->registerBuilderMacros();
        $this->registerCollectionMacros();

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeAllCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/fast-api.php', 'fast-api');
    }

    private function registerBuilderMacros(): void
    {
        /**
         * Add a LIKE search condition across multiple columns or relation columns.
         *
         * Usage:
         *   ->likeWhere(['name', 'email'], $search)
         *   ->likeWhere(['user:name,email'], $search)  // search in relation columns
         */
        Builder::macro('likeWhere', function (array $attributes, ?string $searchTerm = null): Builder {
            /** @var Builder<Model> $this */
            if (empty($searchTerm)) {
                return $this;
            }

            return $this->where(function (Builder $query) use ($attributes, $searchTerm): void {
                foreach ($attributes as $attribute) {
                    $query->when(
                        Str::contains($attribute, ':'),
                        function (Builder $query) use ($attribute, $searchTerm): void {
                            [$relationName, $relationAttributes] = explode(':', $attribute);
                            $relationAttributes = explode(',', $relationAttributes);
                            $query->whereHas($relationName, function (Builder $builder) use ($relationAttributes, $searchTerm): void {
                                $builder->orWhereAny($relationAttributes, 'LIKE', "%{$searchTerm}%");
                            });
                        },
                        function (Builder $query) use ($attribute, $searchTerm): void {
                            $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                        }
                    );
                }
            });
        });

        /**
         * Paginate using the rowsPerPage request parameter.
         *
         * Respects fast-api.pagination.max_per_page and fast-api.pagination.allow_all config.
         * Pass rowsPerPage=0 to return all records (if allow_all is enabled).
         *
         * @param  array<string>|string  $columns
         * @return Paginator
         *
         * @throws InvalidArgumentException
         */
        Builder::macro('paginates', function ($columns = ['*'], string $pageName = 'page', ?int $page = null): Paginator {
            /** @var Builder<Model> $this */
            $defaultPerPage = ApiCrudServiceProvider::configInt('fast-api.pagination.default_per_page', 15);
            $maxPerPage = ApiCrudServiceProvider::configInt('fast-api.pagination.max_per_page', 100);
            $allowAll = ApiCrudServiceProvider::configBool('fast-api.pagination.allow_all', true);
            $requested = ApiCrudServiceProvider::requestedPerPage($defaultPerPage);

            if ($allowAll && $requested === 0) {
                $count = $this->count();
                $perPage = $count > 0 ? $count : $defaultPerPage;
            } elseif ($requested <= 0) {
                $perPage = $defaultPerPage;
            } else {
                $perPage = min($requested, $maxPerPage);
            }

            return $this->paginate($perPage, $columns, $pageName, $page);
        });

        /**
         * Simple-paginate using the rowsPerPage request parameter.
         *
         * @param  array<string>|string  $columns
         * @return Paginator
         */
        Builder::macro('simplePaginates', function ($columns = ['*'], string $pageName = 'page', ?int $page = null): Paginator {
            /** @var Builder<Model> $this */
            $defaultPerPage = ApiCrudServiceProvider::configInt('fast-api.pagination.default_per_page', 15);
            $maxPerPage = ApiCrudServiceProvider::configInt('fast-api.pagination.max_per_page', 100);
            $allowAll = ApiCrudServiceProvider::configBool('fast-api.pagination.allow_all', true);
            $requested = ApiCrudServiceProvider::requestedPerPage($defaultPerPage);

            if ($allowAll && $requested === 0) {
                $count = $this->count();
                $perPage = $count > 0 ? $count : $defaultPerPage;
            } elseif ($requested <= 0) {
                $perPage = $defaultPerPage;
            } else {
                $perPage = min($requested, $maxPerPage);
            }

            return $this->simplePaginate($perPage, $columns, $pageName, $page);
        });

        /**
         * Apply request-based filters, sorting, and scopes to the query.
         *
         * - Reads ?filters={"scope": "value"} JSON and calls matching model scopes
         * - Reads ?sortBy=column&descending=true for ordering
         * - Models can define sortByDefaults(): array{sortBy: string, sortByDesc: bool}
         *   to set default sort when no request param is provided
         *
         * @return Builder<Model>
         */
        Builder::macro('initializer', function (bool $orderBy = true): Builder {
            /** @var Builder<Model> $this */
            $request = request();
            $filters = [];

            if ($request->filled('filters')) {
                $filtersInput = $request->query('filters', '{}');

                if (is_string($filtersInput)) {
                    $decodedFilters = json_decode($filtersInput, true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($decodedFilters)) {
                        $filters = $decodedFilters;
                    }
                }
            }

            if (! empty($filters)) {
                foreach (collect($filters) as $filter => $value) {
                    if (! isset($value)) {
                        continue;
                    }
                    $studlyFilter = Str::studly((string) $filter);
                    if (method_exists($this->getModel(), 'scope'.$studlyFilter)) {
                        $this->{$filter}($value);
                    } elseif (method_exists($this->getModel(), $studlyFilter)) {
                        $this->getModel()->{$studlyFilter}($value);
                    }
                }
            }

            if ($orderBy) {
                $sortBy = $request->query('sortBy', 'id');
                $desc = $request->boolean('descending', true);

                // Allow model to provide default sort configuration
                if (method_exists($this->getModel(), 'sortByDefaults')) {
                    $sortByDefaults = $this->getModel()->sortByDefaults();
                    if (
                        isset($sortByDefaults['sortBy']) && is_string($sortByDefaults['sortBy']) &&
                        isset($sortByDefaults['sortByDesc']) && is_bool($sortByDefaults['sortByDesc'])
                    ) {
                        $sortBy = $sortByDefaults['sortBy'];
                        $desc = $sortByDefaults['sortByDesc'];
                    }
                }

                if (is_string($sortBy) && $sortBy !== '') {
                    $desc ? $this->latest($sortBy) : $this->oldest($sortBy);
                }
            }

            return $this;
        });

        /**
         * Apply multiple aggregate functions to the query.
         *
         * @param  array<string, array<string>|string>  $aggregates  ['relation' => 'column'] or ['relation' => ['column', 'function']]
         * @return Builder<Model>
         */
        Builder::macro('withAggregates', function (array $aggregates): Builder {
            /** @var Builder<Model> $this */
            if (empty($aggregates)) {
                return $this;
            }

            foreach ($aggregates as $relation => $value) {
                if (is_array($value)) {
                    $column = $value[0];
                    $function = isset($value[1]) && is_string($value[1]) ? $value[1] : null;
                } else {
                    $column = $value;
                    $function = null;
                }
                $this->withAggregate($relation, $column, $function);
            }

            return $this;
        });

        /**
         * Add a conditional withCount that also filters results using whereHas.
         *
         * @param  string  $relation  Relation name, optionally with alias using ":" separator.
         * @return Builder<Model>
         */
        Builder::macro('withCountWhereHas', function (string $relation, ?Closure $callback = null, string $operator = '>=', int $count = 1): Builder {
            /** @var Builder<Model> $this */
            $this->whereHas(Str::before($relation, ':'), $callback, $operator, $count)
                ->withCount(relations: $callback ? [$relation => fn ($query) => $callback($query)] : $relation);

            return $this;
        });

        /**
         * Add an OR conditional withCount that also filters results using orWhereHas.
         *
         * @return Builder<Model>
         */
        Builder::macro('orWithCountWhereHas', function (string $relation, ?Closure $callback = null, string $operator = '>=', int $count = 1): Builder {
            /** @var Builder<Model> $this */
            $this->orWhereHas(Str::before($relation, ':'), $callback, $operator, $count)
                ->withCount(relations: $callback ? [$relation => fn ($query) => $callback($query)] : $relation);

            return $this;
        });
    }

    /**
     * Read a config value and coerce it to int.
     */
    public static function configInt(string $key, int $default): int
    {
        $value = config($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * Read a config value and coerce it to bool.
     */
    public static function configBool(string $key, bool $default): bool
    {
        $value = config($key, $default);

        return is_bool($value) ? $value : (bool) $value;
    }

    /**
     * Get the rowsPerPage request parameter as an integer.
     */
    public static function requestedPerPage(int $default): int
    {
        $raw = request()->query('rowsPerPage', $default);

        return is_numeric($raw) ? (int) $raw : $default;
    }

    private function registerCollectionMacros(): void
    {
        /**
         * Paginate an in-memory collection.
         *
         * @return Paginator
         */
        Collection::macro('paginate', function (int $perPage, ?int $total = null, ?int $page = null, string $pageName = 'page'): Paginator {
            /** @var Collection<array-key, mixed> $this */
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);

            return new LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });
    }
}
