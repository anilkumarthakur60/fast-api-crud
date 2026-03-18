<?php

namespace Anil\FastApiCrud\Controller;

use Anil\FastApiCrud\Traits\ApiResponder;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Throwable;

/**
 * Base controller providing standard CRUD operations for Eloquent models.
 *
 * Supports: index, show, store, update, destroy, bulk delete, changeStatus,
 * updateColumn, restore, restoreAll, permanentDelete.
 *
 * Each operation supports lifecycle hooks on the model (beforeCreate, afterCreate, etc.)
 * and configurable scopes per operation.
 */
class BaseController extends Controller
{
    use ApiResponder;
    use AuthorizesRequests;
    use ValidatesRequests;

    /**
     * Whether to paginate the index results.
     */
    public bool $paginate = true;

    /**
     * Scopes applied to the index query.
     *
     * Supports simple names ['active'] and parameterized ['status' => 1].
     *
     * @var array<int, string>|array<string, scalar|array<scalar>>|array<string, \Closure>
     */
    public array $scopes = [];

    /**
     * Scopes applied to the show (single resource) query.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>>|array<string, \Closure>
     */
    public array $loadScopes = [];

    /**
     * Eager load relationships in index.
     *
     * @var array<array-key, string|array<string, string|int|float|bool>|(\Closure(\Illuminate\Database\Eloquent\Relations\Relation<*,*,*>): mixed)|array<array-key, string|(\Closure(\Illuminate\Database\Eloquent\Relations\Relation<*,*,*>): mixed)>>
     */
    public array $with = [];

    /**
     * Relationships to count in index.
     *
     * @var array<string>
     */
    public array $withCount = [];

    /**
     * Aggregate functions to apply in index. Format: ['relation' => 'column'] or ['relation' => ['column', 'fn']].
     *
     * @var array<string, string>
     */
    public array $withAggregate = [];

    /**
     * Eager load relationships in show.
     *
     * @var array<array-key, string|array<string, string|int|float|bool>|(\Closure(\Illuminate\Database\Eloquent\Relations\Relation<*,*,*>): mixed)|array<array-key, string|(\Closure(\Illuminate\Database\Eloquent\Relations\Relation<*,*,*>): mixed)>>
     */
    public array $load = [];

    /**
     * Relationships to count in show.
     *
     * @var array<string>
     */
    public array $loadCount = [];

    /**
     * Aggregate functions to apply in show.
     *
     * @var array<string, string>
     */
    public array $loadAggregate = [];

    /**
     * Force permanent deletion instead of soft delete.
     */
    public bool $forceDelete = false;

    /**
     * Scopes applied when finding a record for deletion.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>>|array<string, \Closure>
     */
    public array $deleteScopes = [];

    /**
     * Scopes applied when finding a record for status change or column update.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>>|array<string, \Closure>
     */
    public array $columnScopes = [];

    /**
     * Scopes applied when finding a trashed record for restore.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>>|array<string, \Closure>
     */
    public array $restoreScopes = [];

    /**
     * Scopes applied when finding a record for update.
     *
     * @var array<int, string>|array<string, scalar|array<scalar>>|array<string, \Closure>
     */
    public array $updateScopes = [];

    public Model $model;

    /** @var class-string<FormRequest> */
    protected string $storeRequest;

    /** @var class-string<FormRequest> */
    protected string $updateRequest;

    /** @var class-string<JsonResource> */
    protected string $resource;

    /**
     * @param  class-string<Model>  $model
     * @param  class-string<FormRequest>  $storeRequest
     * @param  class-string<FormRequest>  $updateRequest
     * @param  class-string<JsonResource>  $resource
     *
     * @throws ReflectionException
     * @throws Exception
     */
    public function __construct(string $model, string $storeRequest, string $updateRequest, string $resource)
    {
        $this->validateModel($model);
        $this->validateRequest($storeRequest, 'StoreRequest');
        $this->validateRequest($updateRequest, 'UpdateRequest');
        $this->validateResource($resource);
        $this->setupPermissions();
    }

    /**
     * @param  class-string<Model>  $modelClass
     *
     * @throws Exception
     */
    protected function validateModel(string $modelClass): void
    {
        if (! is_subclass_of($modelClass, Model::class)) {
            throw new Exception('Model is not instance of Model', ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
        $this->model = resolve($modelClass);
    }

    /**
     * @param  class-string<FormRequest>  $request
     *
     * @throws Exception
     */
    protected function validateRequest(string $request, string $requestName): void
    {
        if (! is_subclass_of($request, FormRequest::class)) {
            throw new Exception("{$requestName} is not instance of FormRequest", ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
        if ($requestName === 'StoreRequest') {
            $this->storeRequest = $request;
        } else {
            $this->updateRequest = $request;
        }
    }

    /**
     * @param  class-string<JsonResource>  $resource
     *
     * @throws Exception
     */
    protected function validateResource(string $resource): void
    {
        if (! is_subclass_of($resource, JsonResource::class)) {
            throw new Exception('Resource is not instance of JsonResource', ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
        $this->resource = $resource;
    }

    /**
     * Register Spatie permission middleware if the model defines getPermissionSlug()
     * and fast-api.permissions.enabled is true.
     */
    protected function setupPermissions(): void
    {
        if (! config('fast-api.permissions.enabled', true)) {
            return;
        }

        $permissionSlug = null;
        if (method_exists($this->model, 'getPermissionSlug')) {
            $permissionSlug = $this->model->getPermissionSlug();
        }

        if (! $permissionSlug) {
            return;
        }

        $this->middleware("permission:view-{$permissionSlug}")->only(['index', 'show']);
        $this->middleware("permission:store-{$permissionSlug}")->only(['store']);
        $this->middleware("permission:update-{$permissionSlug}")->only(['update', 'updateColumn']);
        $this->middleware("permission:delete-{$permissionSlug}")->only(['destroy', 'delete', 'permanentDelete']);
        $this->middleware("permission:change-status-{$permissionSlug}")->only(['changeStatus']);
        $this->middleware("permission:restore-{$permissionSlug}")->only(['restore', 'restoreAll']);
    }

    /**
     * List all records, applying scopes, eager loads, counts, aggregates, and pagination.
     */
    public function index(): AnonymousResourceCollection
    {
        /** @var Builder<Model> $query */
        $query = $this->model::query()->initializer();

        if (! empty($this->with)) {
            $query->with($this->with);
        }

        if (! empty($this->withCount)) {
            $query->withCount($this->withCount);
        }

        if (! empty($this->withAggregate)) {
            $this->applyAggregates($query, $this->withAggregate);
        }

        if (! empty($this->scopes)) {
            $this->applyScopes($query, $this->scopes);
        }

        if ($this->paginate) {
            return $this->resource::collection($query->paginates());
        }

        return $this->resource::collection($query->get());
    }

    /**
     * Display the specified resource.
     */
    public function show(int|string $id): JsonResource|JsonResponse
    {
        /** @var Builder<Model> $query */
        $query = $this->model::query()->initializer();

        if (! empty($this->load)) {
            $query->with($this->load);
        }

        if (! empty($this->loadCount)) {
            $query->withCount($this->loadCount);
        }

        if (! empty($this->loadAggregate)) {
            $this->applyAggregates($query, $this->loadAggregate);
        }

        if (! empty($this->loadScopes)) {
            $this->applyScopes($query, $this->loadScopes);
        }

        $model = $query->findOrFail($id);

        return new $this->resource($model);
    }

    /**
     * Store a newly created resource. Returns 201 Created on success.
     *
     * @throws Throwable
     */
    public function store(): JsonResponse
    {
        $data = resolve($this->storeRequest)->safe()->only((new $this->model)->getFillable());

        try {
            DB::beginTransaction();
            $model = $this->model::create($data);
            $this->afterCreate($model);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return (new $this->resource($model))
            ->toResponse(request())
            ->setStatusCode(ResponseAlias::HTTP_CREATED);
    }

    /**
     * Update the specified resource.
     *
     *
     * @throws Throwable
     */
    public function update(int|string $id): JsonResource|JsonResponse
    {
        $data = resolve($this->updateRequest)->safe()->only((new $this->model)->getFillable());
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

        return $this->success(code: ResponseAlias::HTTP_NO_CONTENT);
    }

    /**
     * Bulk delete records by passing an array of IDs in 'delete_rows'.
     *
     * @throws Throwable
     */
    public function delete(): JsonResponse
    {
        $keyName = (new $this->model)->getKeyName();

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

        return $this->success(code: ResponseAlias::HTTP_NO_CONTENT);
    }

    /**
     * Toggle a boolean status column between 0 and 1.
     *
     *
     * @throws Throwable
     */
    public function changeStatus(int|string $id, string $column = 'status'): JsonResource|JsonResponse
    {
        $model = $this->findModel($id, $this->columnScopes);
        $this->validateColumn($model, $column);

        try {
            DB::beginTransaction();
            $this->beforeStatusChange($model);
            $model->update([$column => $model->{$column} === 1 ? 0 : 1]);
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
     *
     * @throws Throwable
     */
    public function updateColumn(int|string $id, string $column = 'status'): JsonResource|JsonResponse
    {
        $model = $this->findModel($id, $this->columnScopes);
        $this->validateColumn($model, $column);

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
     *
     * @throws Throwable
     */
    public function restore(int|string $id): JsonResource|JsonResponse
    {
        /** @var Builder<Model> $query */
        $query = $this->model::query()->initializer()->onlyTrashed();

        if (! empty($this->restoreScopes)) {
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
            $this->model::query()->initializer()->onlyTrashed()->restore();
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }

        return $this->success(code: ResponseAlias::HTTP_NO_CONTENT);
    }

    /**
     * Permanently delete a soft-deleted resource.
     *
     *
     * @throws Throwable
     */
    public function permanentDelete(int|string $id): JsonResponse
    {
        $model = $this->model::query()->initializer()->onlyTrashed()->findOrFail($id);

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

        return $this->success(code: ResponseAlias::HTTP_NO_CONTENT);
    }

    // -------------------------------------------------------------------------
    // Lifecycle hooks – override in child controller or define on the model
    // -------------------------------------------------------------------------

    protected function afterCreate(Model $model): Model
    {
        if (method_exists($model, 'afterCreate')) {
            $model->afterCreate();
        }

        return $model;
    }

    protected function beforeUpdate(Model $model): Model
    {
        if (method_exists($model, 'beforeUpdate')) {
            $model->beforeUpdate();
        }

        return $model;
    }

    protected function afterUpdate(Model $model): Model
    {
        if (method_exists($model, 'afterUpdate')) {
            $model->afterUpdate();
        }

        return $model;
    }

    protected function beforeDelete(Model $model): Model
    {
        if (method_exists($model, 'beforeDelete')) {
            $model->beforeDelete();
        }

        return $model;
    }

    protected function afterDelete(Model $model): Model
    {
        if (method_exists($model, 'afterDelete')) {
            $model->afterDelete();
        }

        return $model;
    }

    protected function beforeStatusChange(Model $model): Model
    {
        if (method_exists($model, 'beforeStatusChange')) {
            $model->beforeStatusChange();
        }

        return $model;
    }

    protected function afterStatusChange(Model $model): Model
    {
        if (method_exists($model, 'afterStatusChange')) {
            $model->afterStatusChange();
        }

        return $model;
    }

    protected function beforeColumnUpdate(Model $model): Model
    {
        if (method_exists($model, 'beforeColumnUpdate')) {
            $model->beforeColumnUpdate();
        }

        return $model;
    }

    protected function afterColumnUpdate(Model $model): Model
    {
        if (method_exists($model, 'afterColumnUpdate')) {
            $model->afterColumnUpdate();
        }

        return $model;
    }

    protected function beforeRestore(Model $model): Model
    {
        if (method_exists($model, 'beforeRestore')) {
            $model->beforeRestore();
        }

        return $model;
    }

    protected function afterRestore(Model $model): Model
    {
        if (method_exists($model, 'afterRestore')) {
            $model->afterRestore();
        }

        return $model;
    }

    protected function beforeForceDelete(Model $model): Model
    {
        if (method_exists($model, 'beforeForceDelete')) {
            $model->beforeForceDelete();
        }

        return $model;
    }

    protected function afterForceDelete(Model $model): Model
    {
        if (method_exists($model, 'afterForceDelete')) {
            $model->afterForceDelete();
        }

        return $model;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Find a model by primary key with optional scopes applied.
     *
     * @param  array<int, string>|array<string, scalar|array<scalar>>|array<string, \Closure>  $scopes
     */
    protected function findModel(int|string $id, array $scopes = []): Model
    {
        $query = $this->model::query();

        if (! empty($scopes)) {
            $this->applyScopes($query, $scopes);
        }

        return $query->findOrFail($id);
    }

    /**
     * Apply named and parameterized scopes to a query.
     *
     * Supports:
     *   ['active']           → $query->active()
     *   ['status' => 1]      → $query->status(1)
     *   ['range' => [1, 10]] → $query->range(1, 10)
     *
     * @param  Builder<Model>  $query
     * @param  array<int, string>|array<string, scalar|array<scalar>>|array<string, \Closure>  $scopes
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
    protected function applyAggregates(Builder $query, array $aggregates): Builder
    {
        foreach ($aggregates as $relation => $column) {
            $query->withAggregate($relation, $column);
        }

        return $query;
    }

    /**
     * Assert that a column exists in the model's table and is fillable.
     *
     * @throws Exception
     */
    protected function validateColumn(Model $model, string $column): void
    {
        if (! Schema::hasColumn($model->getTable(), $column)) {
            throw new Exception("{$column} column does not exist in the database.");
        }

        if (! in_array($column, Schema::getColumnListing($model->getTable()), true)) {
            throw new Exception("{$column} column not found in table");
        }
    }
}
