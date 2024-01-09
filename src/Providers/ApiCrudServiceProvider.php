<?php

namespace Anil\FastApiCrud\Providers;

use Anil\FastApiCrud\Commands\MakeAction;
use Anil\FastApiCrud\Commands\MakeService;
use Anil\FastApiCrud\Commands\MakeTrait;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ApiCrudServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/fastApiCrud.php' => config_path('fastApiCrud.php'),
        ], 'config');

        Builder::macro('likeWhere', function (array $attributes, string $searchTerm = null) {
            return  $this->where(function (Builder $query) use ($attributes, $searchTerm) {
                foreach ($attributes as $attribute) {
                    $query->when(
                        Str::contains($attribute, '.'),
                        function (Builder $query) use ($attribute, $searchTerm) {
                            [$relationName, $relationAttribute] = explode('.', $attribute);
                            $query->orWhereHas($relationName, function (Builder $query) use ($relationAttribute, $searchTerm) {
                                $query->where($relationAttribute, 'LIKE', "%{$searchTerm}%");
                            });
                        },
                        function (Builder $query) use ($attribute, $searchTerm) {
                            $query->orWhere($attribute, 'LIKE', "%{$searchTerm}%");
                        }
                    );
                }
            });
        });

        Builder::macro('equalWhere', function (array $attributes, mixed $searchTerm = null) {
            return  $this->where(function (Builder $query) use ($attributes, $searchTerm) {
                foreach ($attributes as $attribute) {
                    $query->when(
                        Str::contains($attribute, '.'),
                        function (Builder $query) use ($attribute, $searchTerm) {
                            $relationName = Str::beforeLast($attribute, '.');
                            $relationAttribute = Str::afterLast($attribute, '.');

                            $query->whereHas($relationName, function (Builder $query) use ($relationAttribute, $searchTerm) {
                                if (is_array($searchTerm)) {
                                    $query->whereIn($relationAttribute, $searchTerm);
                                } else {
                                    $query->where($relationAttribute, $searchTerm);
                                }
                            });
                        },
                        function (Builder $query) use ($attribute, $searchTerm) {
                            if (is_array($searchTerm)) {
                                $query->whereIn($attribute, $searchTerm);
                            } else {
                                $query->where($attribute, $searchTerm);
                            }
                        }
                    );
                }
            });
        });

        Builder::macro('paginates', function ($perPage = null, $columns = ['*'], $pageName = 'page', int $page = null) {
            request()->validate(['rowsPerPage' => 'nullable|numeric|gte:0|lte:100000']);

            $page = $page ?: Paginator::resolveCurrentPage($pageName);

            $total = func_num_args() === 5 ? value(func_get_arg(4)) : $this->toBase()->getCountForPagination();

            $perPage = (
                $perPage instanceof Closure
                ? $perPage($total)
                : $perPage
            ) ?: $this->model->getPerPage();

            if (request()->filled('rowsPerPage') && !($perPage instanceof  Closure)) {
                if ((int) request('rowsPerPage') === 0) {
                    $perPage = $total === 0 ? 15 : $total;
                } else {
                    $perPage = (int) request('rowsPerPage');
                }
            }

            $results = $total
                ? $this->forPage($page, $perPage)->get($columns)
                : $this->model->newCollection();

            return $this->paginator($results, $total, $perPage, $page, [
                'path'     => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]);
        });
        Builder::macro('simplePaginates', function (int $perPage = null, $columns = ['*'], $pageName = 'page', $page = null) {
            request()->validate(['rowsPerPage' => 'nullable|numeric|gte:0|lte:100000']);
            if (request()->filled('rowsPerPage')) {
                if ((int) request('rowsPerPage') === 0) {
                    $perPage = $this->count();
                } else {
                    $perPage = (int) request('rowsPerPage');
                }
            }
            $page = $page ?: Paginator::resolveCurrentPage($pageName);

            $this->offset(($page - 1) * $perPage)->limit($perPage + 1);

            return $this->simplePaginator($this->get($columns), $perPage, $page, [
                'path'     => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]);
        });

        Builder::macro('toRawSql', function (): string {
            $bindings = [];
            foreach ($this->getBindings() as $value) {
                if (is_string($value)) {
                    $bindings[] = "'{$value}'";
                } else {
                    $bindings[] = $value;
                }
            }

            return Str::replaceArray('?', $bindings, $this->toSql());
        });

        Builder::macro('getSqlQuery', function () {
            $query = str_replace(['?'], ['\'%s\''], $this->toSql());

            return vsprintf($query, $this->getBindings());
        });

        Collection::macro('paginates', function ($perPage = 15, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);

            return new LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path'     => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });

        Builder::macro('initializer', function (bool $orderBy = true) {
            $request = request();
            $filters = json_decode($request->query('filters'), true);
            if (method_exists($this->model, 'initializeModel')) {
                $model = $this->model->initializeModel();
            } else {
                $model = $this->newQuery();
            }
            foreach (collect($filters) as $filter => $value) {
                if (isset($value) && method_exists($this->model, 'scope'.ucfirst($filter))) {
                    $model->$filter($value);
                }
            }
            $sortBy = (string) $request->query('sortBy', 'id');
            $desc = $request->boolean('descending', true);
            if ($orderBy) {
                if ($sortBy && method_exists($this->model, 'sortByDefaults')) {
                    $sortByDefaults = $this->model->sortByDefaults();
                    $sortBy = $sortByDefaults['sortBy'];
                    $desc = $sortByDefaults['sortByDesc'];
                }
                $desc === true ? $model->latest($sortBy) : $model->oldest($sortBy);
            }

            return $model;
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/fastApiCrud.php', 'fastApiCrud');
        $this->commands([MakeAction::class, MakeService::class, MakeTrait::class]);
    }
}
