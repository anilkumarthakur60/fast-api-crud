<?php

namespace Anil\FastApiCrud\Providers;

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

        Builder::macro('likeWhere', function (array $attributes, ?string $searchTerm = null) {
            /** @var Builder<Model> $this */
            if (empty($searchTerm)) {
                return $this;
            }

            return $this->where(function (Builder $query) use ($attributes, $searchTerm) {
                foreach ($attributes as $attribute) {
                    $query->when(
                        Str::contains($attribute, ':'),
                        function (Builder $query) use ($attribute, $searchTerm) {
                            [$relationName, $relationAttributes] = explode(':', $attribute);
                            $relationAttributes = explode(',', $relationAttributes);
                            $query->whereHas($relationName, function (Builder $builder) use ($relationAttributes, $searchTerm) {
                                $builder->orWhereAny($relationAttributes, 'LIKE', "%{$searchTerm}%");
                            });
                        },
                        function (Builder $query) use ($attribute, $searchTerm) {
                            $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                        }
                    );
                }
            });
        });

        /**
         * Paginate the given query.
         *
         * @param  int|null|Closure  $perPage
         * @param  array|string  $columns
         * @param  string  $pageName
         * @param  int|null  $page
         * @param  Closure|int|null  $total
         * @return Paginator
         *
         * @throws InvalidArgumentException
         */
        Builder::macro('paginates', function ($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null): Paginator {

            /** @var Builder<Model> $this */
            $validated = request()->all();
            $rowsPerPage = $validated['rowsPerPage'] ?? 15;
            $perPage = $rowsPerPage === 0 ? $this->count() : $rowsPerPage;
            $perPage = (int) $perPage;

            return $this->paginate($perPage, $columns, $pageName, $page, $total);
        });

        /**
         * Paginate the given query into a simple paginator.
         *
         * @param  int|null  $perPage
         * @param  array|string  $columns
         * @param  string  $pageName
         * @param  int|null  $page
         * @return Paginator
         */
        Builder::macro('simplePaginates', function ($perPage = null, $columns = ['*'], $pageName = 'page', $page = null): Paginator {
            /** @var Builder<Model> $this */
            $validated = request()->all();
            $rowsPerPage = $validated['rowsPerPage'] ?? 15;
            $perPage = $rowsPerPage === 0 ? $this->count() : $rowsPerPage;
            $perPage = (int) $perPage;

            return $this->simplePaginate($perPage, $columns, $pageName, $page);
        });

        /**
         * Macro to initialize the query builder with filters and sorting.
         *
         * @param  bool  $orderBy  Whether to apply ordering based on request parameters.
         * @return Builder<Model> The initialized query builder.
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
                    if (isset($value) && method_exists($this->getModel(), 'scope'.Str::studly($filter))) {
                        $this->{$filter}($value);
                    }
                }
            }

            $sortBy = $request->query('sortBy', 'id');
            $desc = $request->boolean('descending', true);

            if ($orderBy) {
                if ($sortBy && method_exists($this->getModel(), 'sortByDefaults')) {
                    // @phpstan-ignore-next-line
                    $sortByDefaults = $this->sortByDefaults();
                    if (
                        isset($sortByDefaults['sortBy']) && is_string($sortByDefaults['sortBy']) &&
                        isset($sortByDefaults['sortByDesc']) && is_bool($sortByDefaults['sortByDesc'])
                    ) {
                        $sortBy = $sortByDefaults['sortBy'];
                        $desc = $sortByDefaults['sortByDesc'];
                    }
                }
                if (is_string($sortBy)) {
                    $desc ? $this->latest($sortBy) : $this->oldest($sortBy);
                }
            }

            return $this;
        });
        /**
         * Macro to add aggregates to the query.
         *
         * @param  array<string, array<string>|string>  $aggregates
         * @return Builder<Model>
         */
        Builder::macro('withAggregates', function (array $aggregates) {
            /** @var Builder<Model> $this */
            if (! count($aggregates)) {
                return $this;
            }
            foreach ($aggregates as $relation => $value) {
                // Check if $value is an array (for multiple parameters)
                if (is_array($value)) {
                    $column = $value[0]; // First element is the column
                    $function = isset($value[1]) && is_string($value[1]) ? $value[1] : null; // Second element is the optional function
                } else {
                    $column = $value; // Single string column
                    $function = null; // No function provided
                }
                $this->withAggregate($relation, $column, $function);
            }

            return $this;
        });

        /**
         * Macro to add a conditional withCount based on a relationship.
         *
         * @param  string  $relation
         * @param  Closure|null  $callback
         * @param  string  $operator
         * @param  int  $count
         * @return Builder<Model>
         */
        Builder::macro('withCountWhereHas', function ($relation, ?Closure $callback = null, $operator = '>=', $count = 1): Builder {
            /** @var Builder<Model> $this */
            $this->whereHas(Str::before($relation, ':'), $callback, $operator, $count)
                ->withCount(relations: $callback ? [$relation => fn ($query) => $callback($query)] : $relation);

            return $this;
        });

        /**
         * Macro to add an OR conditional withCount based on a relationship.
         *
         * @param  string  $relation
         * @param  Closure|null  $callback
         * @param  string  $operator
         * @param  int  $count
         * @return Builder<Model>
         */
        Builder::macro('orWithCountWhereHas', function ($relation, ?Closure $callback = null, $operator = '>=', $count = 1) {
            /** @var Builder<Model> $this */
            $this->orWhereHas(Str::before($relation, ':'), $callback, $operator, $count)
                ->withCount(relations: $callback ? [$relation => fn ($query) => $callback($query)] : $relation);

            return $this;
        });

        /**
         * Paginate collection
         *
         * @param  int  $perPage
         * @param  int  $total
         * @param  int  $page
         * @param  string  $pageName
         * @return Paginator
         */
        Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page'): Paginator {
            /** @var Collection $this */
            // @phpstan-ignore-next-line
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

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/fast-api.php', 'fast-api');
    }
}

//        Builder::macro('equalWhere', function (array $attributes, mixed $searchTerm = null) {
//            if (is_array($searchTerm) && count($searchTerm) === 0) {
//                return $this;
//            }
//            if (! is_array($searchTerm) && ! isset($searchTerm)) {
//                return $this;
//            }
//            return $this->where(function (Builder $query) use ($attributes, $searchTerm) {
//                foreach ($attributes as $attribute) {
//                    $query->when(
//                        Str::contains($attribute, '.'),
//                        function (Builder $query) use ($attribute, $searchTerm) {
//                            $relationName = Str::beforeLast($attribute, '.');
//                            $relationAttribute = Str::afterLast($attribute, '.');
//                            $relation = $this->getRelationWithoutConstraints($relationName);
//                            $table = $relation->getModel()->getTable();
//                            $query->whereHas($relationName, function (Builder $query) use ($relationAttribute, $searchTerm, $table) {
//                                if (is_array($searchTerm)) {
//                                    $query->whereIn($table.'.'.$relationAttribute, $searchTerm);
//                                } else {
//                                    $query->where($table.'.'.$relationAttribute, $searchTerm);
//                                }
//                            });
//                        },
//                        function (Builder $query) use ($attribute, $searchTerm) {
//                            if (is_array($searchTerm)) {
//                                $query->whereIn($attribute, $searchTerm);
//                            } else {
//                                $query->where($attribute, $searchTerm);
//                            }
//                        }
//                    );
//                }
//            });
//        });
