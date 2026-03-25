<?php

declare(strict_types=1);

namespace Anil\FastApiCrud\Http\Controllers;

use Anil\FastApiCrud\Concerns\ApiResponder;
use Anil\FastApiCrud\Contracts\HasPermissionSlug;
use Anil\FastApiCrud\Contracts\Searchable;
use Anil\FastApiCrud\Enums\CrudAction;
use Anil\FastApiCrud\Enums\PaginationType;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Abstract base controller providing standard CRUD operations for Eloquent models.
 *
 * Supports: index, show, store, update, destroy, bulk delete, changeStatus,
 * updateColumn, restore, restoreAll, permanentDelete.
 *
 * Each operation supports lifecycle hooks (override in child controller or define on model).
 */
abstract class BaseController extends Controller
{
    use ApiResponder;
    use AuthorizesRequests;

    /**
     * Pagination strategy for index results.
     */
    protected PaginationType $paginationType = PaginationType::LengthAware;

    /**
     * Scopes applied to the index query.
     *
     * Supports simple names ['active'] and parameterized ['status' => 1].
     *
     * @var array<int, string>|array<string, scalar|array<scalar>|\Closure>
     */
    protected array $scopes = [];

    /**
     * Scopes applied to the show (single resource) query.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>|\Closure>
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
     * Format: ['relation' => 'column'] or ['relation' => ['column', 'function']].
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
     * @var array<int, string>|array<string, scalar|array<scalar>|\Closure>
     */
    protected array $deleteScopes = [];

    /**
     * Scopes applied when finding a record for status change or column update.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>|\Closure>
     */
    protected array $columnScopes = [];

    /**
     * Scopes applied when finding a trashed record for restore.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>|\Closure>
     */
    protected array $restoreScopes = [];

    /**
     * Scopes applied when finding a record for update.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>|\Closure>
     */
    protected array $updateScopes = [];

    protected readonly Model $model;

    /** @var class-string<FormRequest> */
    protected readonly string $storeRequest;

    /** @var class-string<FormRequest> */
    protected readonly string $updateRequest;

    /** @var class-string<JsonResource> */
    protected readonly string $resource;

    /**
     * @param  class-string<Model>  $model
     * @param  class-string<FormRequest>  $storeRequest
     * @param  class-string<FormRequest>  $updateRequest
     * @param  class-string<JsonResource>  $resource
     *
     * @throws Exception
     */
    public function __construct(
        string $model,
        string $storeRequest,
        string $updateRequest,
        string $resource,
    ) {
        $this->model = $this->resolveModel($model);
        $this->storeRequest = $this->resolveFormRequest($storeRequest, 'storeRequest');
        $this->updateRequest = $this->resolveFormRequest($updateRequest, 'updateRequest');
        $this->resource = $this->resolveResource($resource);
        $this->registerPermissionMiddleware();
    }

    // -------------------------------------------------------------------------
    // Permission middleware helper
    // -------------------------------------------------------------------------

    /**
     * Generate Spatie permission middleware definitions for a given slug.
     *
     * Use this in child controllers implementing HasMiddleware:
     *
     *     public static function middleware(): array
     *     {
     *         return self::permissionMiddleware('posts');
     *     }
     *
     * @return array<int, Middleware>
     */
    protected static function permissionMiddleware(string $permissionSlug): array
    {
        return [
            new Middleware('permission:'.CrudAction::View->value."-{$permissionSlug}", only: ['index', 'show']),
            new Middleware('permission:'.CrudAction::Store->value."-{$permissionSlug}", only: ['store']),
            new Middleware('permission:'.CrudAction::Update->value."-{$permissionSlug}", only: ['update', 'updateColumn']),
            new Middleware('permission:'.CrudAction::Delete->value."-{$permissionSlug}", only: ['destroy', 'delete', 'permanentDelete']),
            new Middleware('permission:'.CrudAction::ChangeStatus->value."-{$permissionSlug}", only: ['changeStatus']),
            new Middleware('permission:'.CrudAction::Restore->value."-{$permissionSlug}", only: ['restore', 'restoreAll']),
        ];
    }

    // -------------------------------------------------------------------------
    // CRUD operations
    // -------------------------------------------------------------------------

    /**
     * List all records, applying scopes, eager loads, counts, aggregates, search and pagination.
     */
    public function index(): AnonymousResourceCollection
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

        return $this->resource::collection($this->paginateQuery($query));
    }

    /**
     * Display the specified resource.
     */
    public function show(int|string $id): JsonResource
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

        return new $this->resource($query->findOrFail($id));
    }

    /**
     * Store a newly created resource. Returns 201 Created on success.
     *
     * @throws Throwable
     */
    public function store(): JsonResponse
    {
        $data = $this->resolveValidatedData($this->storeRequest);

        try {
            DB::beginTransaction();
            $model = $this->model->newInstance();
            $model->fill($data);
            $this->beforeCreate($model);
            $model->save();
            $this->afterCreate($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return (new $this->resource($model))
            ->toResponse(request())
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Update the specified resource.
     *
     * @throws Throwable
     */
    public function update(int|string $id): JsonResource|JsonResponse
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

            return $this->error($e->getMessage());
        }

        return new $this->resource($model);
    }

    /**
     * Remove the specified resource (soft delete or force delete).
     *
     * @throws Throwable
     */
    public function destroy(int|string $id): JsonResponse
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

            return $this->error($e->getMessage());
        }

        return $this->success(code: Response::HTTP_NO_CONTENT);
    }

    /**
     * Bulk delete records by passing an array of IDs in 'delete_rows'.
     *
     * @throws Throwable
     */
    public function delete(): JsonResponse
    {
        $keyName = $this->model->getKeyName();

        request()->validate([
            'delete_rows' => ['required', 'array'],
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

            return $this->error($e->getMessage());
        }

        return $this->success(code: Response::HTTP_NO_CONTENT);
    }

    // -------------------------------------------------------------------------
    // Extended operations
    // -------------------------------------------------------------------------

    /**
     * Toggle a boolean status column between 0 and 1.
     *
     * @throws Throwable
     */
    public function changeStatus(int|string $id, string $column = 'status'): JsonResource|JsonResponse
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

            return $this->error($e->getMessage());
        }

        return new $this->resource($model);
    }

    /**
     * Update a specific fillable column with the request value.
     *
     * @throws Throwable
     */
    public function updateColumn(int|string $id, string $column = 'status'): JsonResource|JsonResponse
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

            return $this->error($e->getMessage());
        }

        return new $this->resource($model);
    }

    /**
     * Restore a single soft-deleted resource.
     *
     * @throws Throwable
     */
    public function restore(int|string $id): JsonResource|JsonResponse
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

            return $this->error($e->getMessage());
        }

        return new $this->resource($model);
    }

    /**
     * Restore all soft-deleted records.
     *
     * @throws Throwable
     */
    public function restoreAll(): JsonResponse
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

            return $this->error($e->getMessage());
        }

        return $this->success(code: Response::HTTP_NO_CONTENT);
    }

    /**
     * Permanently delete a soft-deleted resource.
     *
     * @throws Throwable
     */
    public function permanentDelete(int|string $id): JsonResponse
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

            return $this->error($e->getMessage());
        }

        return $this->success(code: Response::HTTP_NO_CONTENT);
    }

    // -------------------------------------------------------------------------
    // Lifecycle hooks - override in child controller or define on the model
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
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * @param  class-string<Model>  $modelClass
     *
     * @throws Exception
     */
    private function resolveModel(string $modelClass): Model
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new Exception("[{$modelClass}] must extend ".Model::class);
        }

        return new $modelClass;
    }

    /**
     * Resolve a FormRequest and return only fillable data.
     *
     * @param  class-string<FormRequest>  $requestClass
     * @return array<string, mixed>
     */
    private function resolveValidatedData(string $requestClass): array
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
     * @param  class-string<FormRequest>  $requestClass
     * @return class-string<FormRequest>
     *
     * @throws Exception
     */
    private function resolveFormRequest(string $requestClass, string $paramName): string
    {
        if (! is_subclass_of($requestClass, FormRequest::class)) {
            throw new Exception("[{$requestClass}] ({$paramName}) must extend ".FormRequest::class);
        }

        return $requestClass;
    }

    /**
     * @param  class-string<JsonResource>  $resourceClass
     * @return class-string<JsonResource>
     *
     * @throws Exception
     */
    private function resolveResource(string $resourceClass): string
    {
        if (! is_subclass_of($resourceClass, JsonResource::class)) {
            throw new Exception("[{$resourceClass}] must extend ".JsonResource::class);
        }

        return $resourceClass;
    }

    private function registerPermissionMiddleware(): void
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

        $this->middleware('permission:'.CrudAction::View->value."-{$slug}")->only(['index', 'show']);
        $this->middleware('permission:'.CrudAction::Store->value."-{$slug}")->only(['store']);
        $this->middleware('permission:'.CrudAction::Update->value."-{$slug}")->only(['update', 'updateColumn']);
        $this->middleware('permission:'.CrudAction::Delete->value."-{$slug}")->only(['destroy', 'delete', 'permanentDelete']);
        $this->middleware('permission:'.CrudAction::ChangeStatus->value."-{$slug}")->only(['changeStatus']);
        $this->middleware('permission:'.CrudAction::Restore->value."-{$slug}")->only(['restore', 'restoreAll']);
    }

    /**
     * @param  array<int, string>|array<string, scalar|array<scalar>|\Closure>  $scopes
     */
    private function findModel(int|string $id, array $scopes = []): Model
    {
        $query = $this->model::query();

        if ($scopes !== []) {
            $this->applyScopes($query, $scopes);
        }

        return $query->findOrFail($id);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<int, string>|array<string, scalar|array<scalar>|\Closure>  $scopes
     * @return Builder<Model>
     */
    private function applyScopes(Builder $query, array $scopes): Builder
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

            $scopeMethod = 'scope'.ucfirst($scope);

            if (method_exists($query->getModel(), $scopeMethod) || method_exists($query->getModel(), $scope)) {
                $query->{$scope}(...$args);
            }
        }

        return $query;
    }

    /**
     * @param  Builder<Model>  $query
     * @param  array<string, string>  $aggregates
     * @return Builder<Model>
     */
    private function applyAggregates(Builder $query, array $aggregates): Builder
    {
        foreach ($aggregates as $relation => $column) {
            $query->withAggregate($relation, $column);
        }

        return $query;
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function applySearch(Builder $query): void
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
     * @param  Builder<Model>  $query
     */
    private function paginateQuery(Builder $query): mixed
    {
        return match ($this->paginationType) {
            PaginationType::LengthAware => $query->paginates(),
            PaginationType::Simple => $query->simplePaginates(),
            PaginationType::Cursor => $query->cursorPaginates(),
            PaginationType::None => $query->get(),
        };
    }

    /**
     * @throws Exception
     */
    private function assertFillableColumn(Model $model, string $column): void
    {
        if (! Schema::hasColumn($model->getTable(), $column)) {
            throw new Exception("Column [{$column}] does not exist on table [{$model->getTable()}].");
        }

        if (! in_array($column, $model->getFillable(), true)) {
            throw new Exception("Column [{$column}] is not fillable on model [".get_class($model).'].');
        }
    }
}
