<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Concerns;

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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
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
     * Relationships clients may eager load on demand via the "include" query
     * parameter (e.g. ?include=author,tags). Acts as an allowlist — anything
     * not listed here is ignored. Empty disables client-driven includes.
     *
     * @var array<int, string>
     */
    protected array $allowedIncludes = [];

    /**
     * Allow clients to include soft-deleted records in the index via the
     * "trashed" query parameter (?trashed=with or ?trashed=only). Opt-in, since
     * soft-deleted rows are usually hidden on purpose.
     */
    protected bool $allowTrashedFilter = false;

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

        $this->applyRequestedIncludes($query);
        $this->applyTrashedFilter($query);
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

        $this->applyRequestedIncludes($query);

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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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

        $maxRows = config('fast-api.bulk.max_rows', 1000);
        $maxRows = is_int($maxRows) ? $maxRows : 1000;

        $rows = ['required', 'array'];
        if ($maxRows > 0) {
            $rows[] = "max:{$maxRows}";
        }

        request()->validate([
            'delete_rows'   => $rows,
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
        } catch (Throwable $e) {
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
            // Toggle truthy → 0, falsy → 1. Works for int, bool, and "1"/"0"
            // string casts alike without casting a mixed value.
            $model->update([$column => $model->getAttribute($column) ? 0 : 1]);
            $this->afterStatusChange($model);
            DB::commit();
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
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
        $query = $this->model::query()->initializer()->onlyTrashed();

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
        } catch (Throwable $e) {
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
            $this->model::query()->initializer()->onlyTrashed()->restore();
            DB::commit();
        } catch (Throwable $e) {
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
        $model = $this->model::query()->initializer()->onlyTrashed()->findOrFail($id);

        try {
            DB::beginTransaction();
            $this->beforeForceDelete($model);
            $model->forceDelete();
            $this->afterForceDelete($model);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Lifecycle hooks — override in child controller or define on the model
    // -------------------------------------------------------------------------

    protected function beforeCreate(Model $model): void
    {
        $this->fireModelHook($model, __FUNCTION__);
    }

    protected function afterCreate(Model $model): void
    {
        $this->fireModelHook($model, __FUNCTION__);
    }

    protected function beforeUpdate(Model $model): void
    {
        $this->fireModelHook($model, __FUNCTION__);
    }

    protected function afterUpdate(Model $model): void
    {
        $this->fireModelHook($model, __FUNCTION__);
    }

    protected function beforeDelete(Model $model): void
    {
        $this->fireModelHook($model, __FUNCTION__);
    }

    protected function afterDelete(Model $model): void
    {
        $this->fireModelHook($model, __FUNCTION__);
    }

    protected function beforeStatusChange(Model $model): void
    {
        $this->fireModelHook($model, __FUNCTION__);
    }

    protected function afterStatusChange(Model $model): void
    {
        $this->fireModelHook($model, __FUNCTION__);
    }

    protected function beforeColumnUpdate(Model $model): void
    {
        $this->fireModelHook($model, __FUNCTION__);
    }

    protected function afterColumnUpdate(Model $model): void
    {
        $this->fireModelHook($model, __FUNCTION__);
    }

    protected function beforeRestore(Model $model): void
    {
        $this->fireModelHook($model, __FUNCTION__);
    }

    protected function afterRestore(Model $model): void
    {
        $this->fireModelHook($model, __FUNCTION__);
    }

    protected function beforeForceDelete(Model $model): void
    {
        $this->fireModelHook($model, __FUNCTION__);
    }

    protected function afterForceDelete(Model $model): void
    {
        $this->fireModelHook($model, __FUNCTION__);
    }

    // -------------------------------------------------------------------------
    // Permission middleware helper
    // -------------------------------------------------------------------------

    /**
     * Generate Spatie permission middleware definitions for a given slug.
     *
     * Call this from a controller's static middleware() method:
     *   public static function middleware(): array
     *   {
     *       return static::permissionMiddleware('posts');
     *   }
     *
     * Returns an empty array when permissions are disabled via config.
     * Throws a RuntimeException if spatie/laravel-permission is not installed.
     *
     * @return array<int, Middleware>
     */
    protected static function permissionMiddleware(string $permissionSlug): array
    {
        if (! config('fast-api.permissions.enabled', true)) {
            return [];
        }

        if (! class_exists(\Spatie\Permission\PermissionServiceProvider::class)) {
            throw new RuntimeException(
                'spatie/laravel-permission is required to use permissionMiddleware(). Install it with: composer require spatie/laravel-permission',
            );
        }

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
     * Resolve a FormRequest and return the validated data the model may persist.
     *
     * Models declaring $fillable are restricted to that list. Models using
     * $guarded (including the common `$guarded = []`) are restricted to the
     * actual table columns, so request-only keys — nested payloads, *_ids used
     * by lifecycle hooks, etc. — are never passed to the insert/update while
     * Eloquent's own mass-assignment guard still applies on save.
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

        $validated = $resolved->validated();

        $fillable = $this->model->getFillable();
        $allowed = $fillable !== []
            ? $fillable
            : Schema::getColumnListing($this->model->getTable());

        $data = [];
        foreach ($allowed as $column) {
            if (is_string($column) && array_key_exists($column, $validated)) {
                $data[$column] = $validated[$column];
            }
        }

        return $data;
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
     * Eager load relationships requested via the "include" query parameter,
     * restricted to the $allowedIncludes allowlist.
     *
     * @param Builder<Model> $query
     */
    protected function applyRequestedIncludes(Builder $query): void
    {
        if ($this->allowedIncludes === []) {
            return;
        }

        $key = config('fast-api.query.include', 'include');
        $requested = request()->query(is_string($key) ? $key : 'include');

        if (! is_string($requested) || $requested === '') {
            return;
        }

        $includes = array_values(array_intersect(
            array_filter(array_map('trim', explode(',', $requested))),
            $this->allowedIncludes,
        ));

        if ($includes !== []) {
            $query->with($includes);
        }
    }

    /**
     * Include soft-deleted records when the client asks via the "trashed" query
     * parameter. Only honoured when $allowTrashedFilter is enabled and the model
     * is soft-deletable.
     *
     * @param Builder<Model> $query
     */
    protected function applyTrashedFilter(Builder $query): void
    {
        if (! $this->allowTrashedFilter || ! $this->modelUsesSoftDeletes()) {
            return;
        }

        $key = config('fast-api.query.trashed', 'trashed');
        $trashed = request()->query(is_string($key) ? $key : 'trashed');

        if ($trashed !== 'with' && $trashed !== 'only') {
            return;
        }

        // Drop the soft-delete global scope so trashed rows become visible
        // (equivalent to withTrashed()), then narrow to only-trashed if asked.
        $query->withoutGlobalScope(SoftDeletingScope::class);

        if ($trashed === 'only') {
            $column = method_exists($this->model, 'getDeletedAtColumn')
                ? $this->model->getDeletedAtColumn()
                : 'deleted_at';

            $query->whereNotNull(is_string($column) ? $column : 'deleted_at');
        }
    }

    /**
     * Determine whether the model uses the SoftDeletes trait.
     */
    protected function modelUsesSoftDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($this->model), true);
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

    private function fireModelHook(Model $model, string $hook): void
    {
        if (method_exists($model, $hook)) {
            $model->{$hook}();
        }
    }
}
