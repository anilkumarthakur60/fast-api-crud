<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Concerns;

use Anil\FastApiCrud\Contracts\HasPermissionSlug;
use Anil\FastApiCrud\Contracts\Searchable;
use Anil\FastApiCrud\Enums\CrudAction;
use Anil\FastApiCrud\Enums\PaginationType;
use Closure;
use Exception;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Shared CRUD query building, data operations, and lifecycle hooks.
 *
 * Used by both BaseController (API) and BaseWebController (Blade/web).
 */
trait HasCrudOperations
{
    /**
     * Pagination strategy for index results.
     */
    protected PaginationType $paginationType = PaginationType::LengthAware;

    /**
     * Scopes applied to the index query.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>|Closure>
     */
    protected array $scopes = [];

    /**
     * Scopes applied to the show (single resource) query.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>|Closure>
     */
    protected array $loadScopes = [];

    /**
     * Eager load relationships in index.
     *
     * @var array<array-key, string|array<string, string|int|float|bool>|(\Closure(\Illuminate\Database\Eloquent\Relations\Relation<*,*,*>): mixed)|array<array-key, string|(\Closure(\Illuminate\Database\Eloquent\Relations\Relation<*,*,*>): mixed)>>
     */
    protected array $with = [];

    /**
     * Relationships to count in index.
     *
     * @var array<string>
     */
    protected array $withCount = [];

    /**
     * Aggregate functions to apply in index.
     *
     * @var array<string, string>
     */
    protected array $withAggregate = [];

    /**
     * Eager load relationships in show.
     *
     * @var array<array-key, string|array<string, string|int|float|bool>|(\Closure(\Illuminate\Database\Eloquent\Relations\Relation<*,*,*>): mixed)|array<array-key, string|(\Closure(\Illuminate\Database\Eloquent\Relations\Relation<*,*,*>): mixed)>>
     */
    protected array $load = [];

    /**
     * Relationships to count in show.
     *
     * @var array<string>
     */
    protected array $loadCount = [];

    /**
     * Aggregate functions to apply in show.
     *
     * @var array<string, string>
     */
    protected array $loadAggregate = [];

    /**
     * Force permanent deletion instead of soft delete.
     */
    protected bool $forceDelete = false;

    /**
     * Scopes applied when finding a record for deletion.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>|Closure>
     */
    protected array $deleteScopes = [];

    /**
     * Scopes applied when finding a record for status change or column update.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>|Closure>
     */
    protected array $columnScopes = [];

    /**
     * Scopes applied when finding a trashed record for restore.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>|Closure>
     */
    protected array $restoreScopes = [];

    /**
     * Scopes applied when finding a record for update.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>|Closure>
     */
    protected array $updateScopes = [];

    protected readonly Model $model;

    /** @var class-string<FormRequest> */
    protected readonly string $storeRequest;

    /** @var class-string<FormRequest> */
    protected readonly string $updateRequest;

    // -------------------------------------------------------------------------
    // Query builders
    // -------------------------------------------------------------------------

    /**
     * Build the index query with eager loads, counts, aggregates, scopes, and search.
     *
     * @return Builder<Model>
     */
    protected function buildIndexQuery(): Builder
    {
        /** @var Builder<Model> $query */
        $query = $this->model::query()->initializer();

        if ($this->with !== []) {
            $query->with($this->with);
        }

        if ($this->withCount !== []) {
            $query->withCount($this->withCount);
        }

        if ($this->withAggregate !== []) {
            $this->applyAggregates($query, $this->withAggregate);
        }

        if ($this->scopes !== []) {
            $this->applyScopes($query, $this->scopes);
        }

        $this->applySearch($query);

        return $query;
    }

    /**
     * Build the show query with eager loads, counts, aggregates, and scopes.
     *
     * @return Builder<Model>
     */
    protected function buildShowQuery(): Builder
    {
        /** @var Builder<Model> $query */
        $query = $this->model::query()->initializer();

        if ($this->load !== []) {
            $query->with($this->load);
        }

        if ($this->loadCount !== []) {
            $query->withCount($this->loadCount);
        }

        if ($this->loadAggregate !== []) {
            $this->applyAggregates($query, $this->loadAggregate);
        }

        if ($this->loadScopes !== []) {
            $this->applyScopes($query, $this->loadScopes);
        }

        return $query;
    }

    // -------------------------------------------------------------------------
    // Perform methods — execute operations, throw on failure
    // -------------------------------------------------------------------------

    /**
     * Execute the store operation.
     *
     * @throws Throwable
     */
    protected function performStore(): Model
    {
        $data = $this->resolveValidatedData($this->storeRequest);

        $model = $this->model->newInstance();
        $model->fill($data);

        try {
            DB::beginTransaction();
            $this->beforeCreate($model);
            $model->save();
            $this->afterCreate($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $model;
    }

    /**
     * Execute the update operation.
     *
     * @throws Throwable
     */
    protected function performUpdate(int|string $id): Model
    {
        $data = $this->resolveValidatedData($this->updateRequest);
        $model = $this->findModel($id, $this->updateScopes);

        try {
            DB::beginTransaction();
            $this->beforeUpdate($model);
            $model->update($data);
            $this->afterUpdate($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $model;
    }

    /**
     * Execute the destroy operation on a single model.
     *
     * @throws Throwable
     */
    protected function performDestroy(int|string $id): void
    {
        $model = $this->findModel($id, $this->deleteScopes);

        try {
            DB::beginTransaction();
            $this->beforeDelete($model);
            $this->forceDelete ? $model->forceDelete() : $model->delete();
            $this->afterDelete($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Execute bulk delete.
     *
     * @throws Throwable
     */
    protected function performBulkDelete(): void
    {
        $keyName = $this->model->getKeyName();

        request()->validate([
            'delete_rows'   => ['required', 'array'],
            'delete_rows.*' => ['required', "exists:{$this->model->getTable()},{$keyName}"],
        ]);

        try {
            DB::beginTransaction();
            foreach ((array) request()->input('delete_rows') as $rawId) {
                if (! is_int($rawId) && ! is_string($rawId)) {
                    continue;
                }
                $model = $this->findModel($rawId, $this->deleteScopes);
                $this->beforeDelete($model);
                $this->forceDelete ? $model->forceDelete() : $model->delete();
                $this->afterDelete($model);
            }
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Execute change status, return the updated model.
     *
     * @throws Throwable
     */
    protected function performChangeStatus(int|string $id, string $column = 'status'): Model
    {
        $model = $this->findModel($id, $this->columnScopes);
        $this->assertFillableColumn($model, $column);

        try {
            DB::beginTransaction();
            $this->beforeStatusChange($model);
            $currentValue = $model->getAttribute($column);
            $model->update([$column => $currentValue === 1 ? 0 : 1]);
            $this->afterStatusChange($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $model;
    }

    /**
     * Execute column update, return the updated model.
     *
     * @throws Throwable
     */
    protected function performUpdateColumn(int|string $id, string $column = 'status'): Model
    {
        $model = $this->findModel($id, $this->columnScopes);
        $this->assertFillableColumn($model, $column);

        try {
            DB::beginTransaction();
            $this->beforeColumnUpdate($model);
            $model->update([$column => request()->input($column)]);
            $this->afterColumnUpdate($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $model;
    }

    /**
     * Execute restore on a single trashed model.
     *
     * @throws Throwable
     */
    protected function performRestore(int|string $id): Model
    {
        $query = $this->model::query();
        $query->initializer();
        $query->onlyTrashed();

        if ($this->restoreScopes !== []) {
            $this->applyScopes($query, $this->restoreScopes);
        }

        $model = $query->findOrFail($id);

        try {
            DB::beginTransaction();
            $this->beforeRestore($model);
            if (method_exists($model, 'restore')) {
                $model->restore();
            }
            $this->afterRestore($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $model;
    }

    /**
     * Execute restore all trashed models.
     *
     * @throws Throwable
     */
    protected function performRestoreAll(): void
    {
        try {
            DB::beginTransaction();
            $query = $this->model::query();
            $query->initializer();
            $query->onlyTrashed();
            $query->restore();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Execute permanent delete on a trashed model.
     *
     * @throws Throwable
     */
    protected function performPermanentDelete(int|string $id): void
    {
        $query = $this->model::query();
        $query->initializer();
        $query->onlyTrashed();
        $model = $query->findOrFail($id);

        try {
            DB::beginTransaction();
            $this->beforeForceDelete($model);
            $model->forceDelete();
            $this->afterForceDelete($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Lifecycle hooks — override in child controller or define on the model
    // -------------------------------------------------------------------------

    protected function beforeCreate(Model $model): void
    {
        if (method_exists($model, 'beforeCreate')) {
            $model->beforeCreate();
        }
    }

    protected function afterCreate(Model $model): void
    {
        if (method_exists($model, 'afterCreate')) {
            $model->afterCreate();
        }
    }

    protected function beforeUpdate(Model $model): void
    {
        if (method_exists($model, 'beforeUpdate')) {
            $model->beforeUpdate();
        }
    }

    protected function afterUpdate(Model $model): void
    {
        if (method_exists($model, 'afterUpdate')) {
            $model->afterUpdate();
        }
    }

    protected function beforeDelete(Model $model): void
    {
        if (method_exists($model, 'beforeDelete')) {
            $model->beforeDelete();
        }
    }

    protected function afterDelete(Model $model): void
    {
        if (method_exists($model, 'afterDelete')) {
            $model->afterDelete();
        }
    }

    protected function beforeStatusChange(Model $model): void
    {
        if (method_exists($model, 'beforeStatusChange')) {
            $model->beforeStatusChange();
        }
    }

    protected function afterStatusChange(Model $model): void
    {
        if (method_exists($model, 'afterStatusChange')) {
            $model->afterStatusChange();
        }
    }

    protected function beforeColumnUpdate(Model $model): void
    {
        if (method_exists($model, 'beforeColumnUpdate')) {
            $model->beforeColumnUpdate();
        }
    }

    protected function afterColumnUpdate(Model $model): void
    {
        if (method_exists($model, 'afterColumnUpdate')) {
            $model->afterColumnUpdate();
        }
    }

    protected function beforeRestore(Model $model): void
    {
        if (method_exists($model, 'beforeRestore')) {
            $model->beforeRestore();
        }
    }

    protected function afterRestore(Model $model): void
    {
        if (method_exists($model, 'afterRestore')) {
            $model->afterRestore();
        }
    }

    protected function beforeForceDelete(Model $model): void
    {
        if (method_exists($model, 'beforeForceDelete')) {
            $model->beforeForceDelete();
        }
    }

    protected function afterForceDelete(Model $model): void
    {
        if (method_exists($model, 'afterForceDelete')) {
            $model->afterForceDelete();
        }
    }

    // -------------------------------------------------------------------------
    // Permission middleware helper
    // -------------------------------------------------------------------------

    /**
     * Generate Spatie permission middleware definitions for a given slug.
     *
     * @return array<int, Middleware>
     */
    protected static function permissionMiddleware(string $permissionSlug): array
    {
        return [
            new Middleware('permission:' . CrudAction::View->value . "-{$permissionSlug}", only: ['index', 'show']),
            new Middleware('permission:' . CrudAction::Store->value . "-{$permissionSlug}", only: ['store']),
            new Middleware('permission:' . CrudAction::Update->value . "-{$permissionSlug}", only: ['update', 'updateColumn']),
            new Middleware('permission:' . CrudAction::Delete->value . "-{$permissionSlug}", only: ['destroy', 'delete', 'permanentDelete']),
            new Middleware('permission:' . CrudAction::ChangeStatus->value . "-{$permissionSlug}", only: ['changeStatus']),
            new Middleware('permission:' . CrudAction::Restore->value . "-{$permissionSlug}", only: ['restore', 'restoreAll']),
        ];
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * @param class-string<Model> $modelClass
     *
     * @throws Exception
     */
    protected function resolveModel(string $modelClass): Model
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new Exception("[{$modelClass}] must extend " . Model::class);
        }

        return new $modelClass;
    }

    /**
     * Resolve a FormRequest and return only fillable data.
     *
     * @param class-string<FormRequest> $requestClass
     *
     * @return array<string, mixed>
     */
    protected function resolveValidatedData(string $requestClass): array
    {
        $resolved = resolve($requestClass);
        if (! $resolved instanceof FormRequest) {
            return [];
        }

        $data = $resolved->safe()->only($this->model->getFillable());

        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param class-string<FormRequest> $requestClass
     *
     * @throws Exception
     *
     * @return class-string<FormRequest>
     */
    protected function resolveFormRequest(string $requestClass, string $paramName): string
    {
        if (! is_subclass_of($requestClass, FormRequest::class)) {
            throw new Exception("[{$requestClass}] ({$paramName}) must extend " . FormRequest::class);
        }

        return $requestClass;
    }

    /**
     * @param class-string<JsonResource> $resourceClass
     *
     * @throws Exception
     *
     * @return class-string<JsonResource>
     */
    protected function resolveResource(string $resourceClass): string
    {
        if (! is_subclass_of($resourceClass, JsonResource::class)) {
            throw new Exception("[{$resourceClass}] must extend " . JsonResource::class);
        }

        return $resourceClass;
    }

    protected function registerPermissionMiddleware(): void
    {
        if (! config('fast-api.permissions.enabled', true)) {
            return;
        }

        if (! $this->model instanceof HasPermissionSlug) {
            return;
        }

        $slug = $this->model->getPermissionSlug();

        if ($slug === '') {
            return;
        }

        $this->middleware('permission:' . CrudAction::View->value . "-{$slug}")->only(['index', 'show']);
        $this->middleware('permission:' . CrudAction::Store->value . "-{$slug}")->only(['store']);
        $this->middleware('permission:' . CrudAction::Update->value . "-{$slug}")->only(['update', 'updateColumn']);
        $this->middleware('permission:' . CrudAction::Delete->value . "-{$slug}")->only(['destroy', 'delete', 'permanentDelete']);
        $this->middleware('permission:' . CrudAction::ChangeStatus->value . "-{$slug}")->only(['changeStatus']);
        $this->middleware('permission:' . CrudAction::Restore->value . "-{$slug}")->only(['restore', 'restoreAll']);
    }

    /**
     * @param array<int, string>|array<string, scalar|array<scalar>|Closure> $scopes
     */
    protected function findModel(int|string $id, array $scopes = []): Model
    {
        $query = $this->model::query();

        if ($scopes !== []) {
            $this->applyScopes($query, $scopes);
        }

        return $query->findOrFail($id);
    }

    /**
     * @param Builder<Model> $query
     * @param array<int, string>|array<string, scalar|array<scalar>|Closure> $scopes
     *
     * @return Builder<Model>
     */
    protected function applyScopes(Builder $query, array $scopes): Builder
    {
        foreach ($scopes as $key => $value) {
            if (is_int($key)) {
                $scope = $value;
                $args = [];
            } else {
                $scope = $key;
                $args = is_array($value) ? $value : [$value];
            }

            if (! is_string($scope)) {
                continue;
            }

            if ($query->getModel()->hasNamedScope($scope)) {
                $query->{$scope}(...$args);
            }
        }

        return $query;
    }

    /**
     * @param Builder<Model> $query
     * @param array<string, string> $aggregates
     *
     * @return Builder<Model>
     */
    protected function applyAggregates(Builder $query, array $aggregates): Builder
    {
        foreach ($aggregates as $relation => $column) {
            $query->withAggregate($relation, $column);
        }

        return $query;
    }

    /**
     * @param Builder<Model> $query
     */
    protected function applySearch(Builder $query): void
    {
        if (! $this->model instanceof Searchable) {
            return;
        }

        $searchTerm = request()->query('search');

        if (! is_string($searchTerm) || $searchTerm === '') {
            return;
        }

        $query->likeWhere($this->model->searchableColumns(), $searchTerm);
    }

    /**
     * @param Builder<Model> $query
     *
     * @return Paginator<int, Model>|CursorPaginator<int, Model>|Collection<int, Model>
     */
    protected function paginateQuery(Builder $query): Paginator|CursorPaginator|Collection
    {
        return match ($this->paginationType) {
            PaginationType::LengthAware => $query->paginates(),
            PaginationType::Simple      => $query->simplePaginates(),
            PaginationType::Cursor      => $query->cursorPaginates(),
            PaginationType::None        => $query->get(),
        };
    }

    /**
     * @throws Exception
     */
    protected function assertFillableColumn(Model $model, string $column): void
    {
        if (! Schema::hasColumn($model->getTable(), $column)) {
            throw new Exception("Column [{$column}] does not exist on table [{$model->getTable()}].");
        }

        if (! in_array($column, $model->getFillable(), true)) {
            throw new Exception("Column [{$column}] is not fillable on model [" . get_class($model) . '].');
        }
    }
}
