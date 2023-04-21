<?php

namespace Anil\FastApiCrud\Providers;

use Anil\FastApiCrud\Commands\MakeAction;
use Anil\FastApiCrud\Commands\MakeService;
use Anil\FastApiCrud\Commands\MakeTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ApiCrudServiceProvider extends ServiceProvider
{
    public function boot()
    {

        $this->publishes([
            __DIR__.'/../config/fastApiCrud.php' => config_path('fastApiCrud.php'),
        ], 'config');

        Builder::macro('likeWhere', function (array $attributes, string $searchTerm = null) {
            $this->where(function (Builder $query) use ($attributes, $searchTerm) {
                foreach ($attributes as $attribute) {
                    $query->when(
                        str_contains($attribute, '.'),
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

            return $this;
        });

        Builder::macro('likeEqual', function (array $attributes, string $searchTerm = null) {
            $this->where(function (Builder $query) use ($attributes, $searchTerm) {
                foreach ($attributes as $attribute) {
                    $query->when(
                        str_contains($attribute, '.'),
                        function (Builder $query) use ($attribute, $searchTerm) {
                            [$relationName, $relationAttribute] = explode('.', $attribute);
                            $query->whereHas($relationName, function (Builder $query) use ($relationAttribute, $searchTerm) {
                                $query->where($relationAttribute, $searchTerm);
                            });
                        },
                        function (Builder $query) use ($attribute, $searchTerm) {
                            $query->where($attribute, $searchTerm);
                        }
                    );
                }
            });

            return $this;
        });

        Builder::macro('paginates', function (int $perPage = null, $columns = ['*'], $pageName = 'page', int $page = null) {
            request()->validate(['rowsPerPage' => 'nullable|numeric|gte:0|lte:1000000000000000000']);

            $page = $page ?: Paginator::resolveCurrentPage($pageName);

            $total = $this->toBase()->getCountForPagination();

            if ($perPage === null) {
                $rows = (int) request()->query('rowsPerPage', 20);
                if ($rows === 0) {
                    $perPage = $total;
                } else {
                    $perPage = $rows;
                }
            }
            $results = $total
                ? $this->forPage($page, $perPage)->get($columns)
                : $this->model->newCollection();

            return $this->paginator($results, $total, $perPage, $page, [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]);
        });
        Builder::macro('simplePaginates', function (int $perPage = null, $columns = ['*'], $pageName = 'page', $page = null) {
            request()->validate(['rowsPerPage' => 'nullable|numeric|gte:0|lte:1000000000000000000']);
            $page = $page ?: Paginator::resolveCurrentPage($pageName);

            if ($perPage === null) {
                $rows = (int) request()->query('rowsPerPage', 20);
                if ($rows === 0) {
                    $perPage = $this->count();
                } else {
                    $perPage = $rows;
                }
            }

            $this->offset(($page - 1) * $perPage)->limit($perPage + 1);

            return $this->simplePaginator($this->get($columns), $perPage, $page, [
                'path' => Paginator::resolveCurrentPath(),
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
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });
    }

    public function register()
    {

        $this->mergeConfigFrom(__DIR__.'/../config/fastApiCrud.php', 'fastApiCrud');
        $this->commands([MakeAction::class, MakeService::class, MakeTrait::class]);

    }
}
