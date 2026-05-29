<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Macros;

use Anil\FastApiCrud\Contracts\Sortable;
use Anil\FastApiCrud\Support\Pagination;
use Closure;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Str;

/**
 * Registers Eloquent Builder macros provided by the package.
 */
final class BuilderMacros
{
    public static function register(): void
    {
        self::registerLikeWhere();
        self::registerPaginates();
        self::registerSimplePaginates();
        self::registerCursorPaginates();
        self::registerInitializer();
        self::registerWithAggregates();
        self::registerWithCountWhereHas();
        self::registerOrWithCountWhereHas();
    }

    private static function registerLikeWhere(): void
    {
        /**
         * Add a LIKE search condition across multiple columns or relation columns.
         *
         * Usage:
         *   ->likeWhere(['name', 'email'], $search)
         *   ->likeWhere(['user:name,email'], $search)
         */
        Builder::macro('likeWhere', function (array $attributes, ?string $searchTerm = null): Builder {
            /** @var array<int, string> $attributes */
            /** @var Builder<Model> $this */
            if ($searchTerm === null || $searchTerm === '') {
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
                        },
                    );
                }
            });
        });
    }

    private static function registerPaginates(): void
    {
        /**
         * Paginate using the rowsPerPage request parameter.
         *
         * @param array<int, string> $columns
         *
         * @return Paginator
         */
        Builder::macro('paginates', function (array $columns = ['*'], string $pageName = 'page', ?int $page = null): Paginator {
            /** @var array<int, string> $columns */
            /** @var Builder<Model> $this */
            return $this->paginate(Pagination::resolveEffectivePerPage(fn () => $this->count()), $columns, $pageName, $page);
        });
    }

    private static function registerSimplePaginates(): void
    {
        /**
         * Simple-paginate using the rowsPerPage request parameter.
         *
         * @param array<int, string> $columns
         *
         * @return Paginator
         */
        Builder::macro('simplePaginates', function (array $columns = ['*'], string $pageName = 'page', ?int $page = null): Paginator {
            /** @var array<int, string> $columns */
            /** @var Builder<Model> $this */
            return $this->simplePaginate(Pagination::resolveEffectivePerPage(fn () => $this->count()), $columns, $pageName, $page);
        });
    }

    private static function registerCursorPaginates(): void
    {
        /**
         * Cursor-paginate using the rowsPerPage request parameter.
         *
         * @param array<string>|string $columns
         *
         * @return CursorPaginator
         */
        Builder::macro('cursorPaginates', function (array $columns = ['*'], ?string $cursorName = null, ?Cursor $cursor = null): CursorPaginator {
            /** @var array<int, string> $columns */
            /** @var Builder<Model> $this */
            $requested = Pagination::requestedPerPage(Pagination::defaultPerPage());
            $perPage = $requested <= 0 ? Pagination::defaultPerPage() : min($requested, Pagination::maxPerPage());

            return $this->cursorPaginate($perPage, $columns, $cursorName ?? 'cursor', $cursor);
        });
    }

    private static function registerInitializer(): void
    {
        /**
         * Apply request-based filters, sorting, and scopes to the query.
         *
         * - Reads ?filters={"scope": "value"} JSON and calls matching model scopes.
         * - Reads ?sortBy=column&descending=true for ordering.
         * - Models implementing Sortable get default sort when no request param is provided.
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

            foreach ($filters as $filter => $value) {
                if (! isset($value)) {
                    continue;
                }

                $model = $this->getModel();

                if ($model->hasNamedScope($filter)) {
                    $this->{$filter}($value);
                } elseif (method_exists($model, $studly = Str::studly((string) $filter))) {
                    $model->{$studly}($value);
                }
            }

            if ($orderBy) {
                $sortBy = $request->query('sortBy');
                $desc = $request->boolean('descending', true);

                if ($sortBy === null && $this->getModel() instanceof Sortable) {
                    $defaults = $this->getModel()->sortByDefaults();
                    $sortBy = $defaults['sortBy'];
                    $desc = $defaults['sortByDesc'];
                }

                $sortBy = is_string($sortBy) && $sortBy !== '' ? $sortBy : 'id';

                $desc ? $this->latest($sortBy) : $this->oldest($sortBy);
            }

            return $this;
        });
    }

    private static function registerWithAggregates(): void
    {
        /**
         * Apply multiple aggregate functions to the query.
         *
         * @param array<string, array<string>|string> $aggregates
         *
         * @return Builder<Model>
         */
        Builder::macro('withAggregates', function (array $aggregates): Builder {
            /** @var Builder<Model> $this */
            if ($aggregates === []) {
                return $this;
            }

            foreach ($aggregates as $relation => $value) {
                if (is_array($value)) {
                    $column = is_string($value[0]) ? $value[0] : '';
                    $function = isset($value[1]) && is_string($value[1]) ? $value[1] : null;
                } elseif (is_string($value)) {
                    $column = $value;
                    $function = null;
                } else {
                    continue;
                }
                $this->withAggregate($relation, $column, $function);
            }

            return $this;
        });
    }

    private static function registerWithCountWhereHas(): void
    {
        /**
         * Add a conditional withCount that also filters results using whereHas.
         *
         * @return Builder<Model>
         */
        Builder::macro('withCountWhereHas', function (string $relation, ?Closure $callback = null, string $operator = '>=', int $count = 1): Builder {
            /** @var Builder<Model> $this */
            $this->whereHas(Str::before($relation, ':'), $callback, $operator, $count)
                ->withCount(relations: $callback ? [$relation => fn ($query) => $callback($query)] : $relation);

            return $this;
        });
    }

    private static function registerOrWithCountWhereHas(): void
    {
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
}
